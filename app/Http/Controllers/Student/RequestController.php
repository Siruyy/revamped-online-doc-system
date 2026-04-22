<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreRequestRequest;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\User;
use App\Notifications\RequestCancelledNotification;
use App\Services\ActivityLogger;
use App\Services\RequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class RequestController extends Controller
{
    public function index(Request $request): Response
    {
        $student = $request->user();

        $requests = DocumentRequest::query()
            ->with(['documentType:id,name,category', 'payments:id,document_request_id,status,total_amount'])
            ->where('user_id', $student->id)
            ->when($request->string('status')->toString(), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->string('from')->toString(), function ($query, $from) {
                $query->whereDate('created_at', '>=', $from);
            })
            ->when($request->string('to')->toString(), function ($query, $to) {
                $query->whereDate('created_at', '<=', $to);
            })
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('documentType', fn ($docQuery) => $docQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Student/Requests/Index', [
            'requests' => $requests,
            'filters' => [
                'status' => $request->string('status')->toString(),
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $student = $request->user();
        $pendingRequestExists = DocumentRequest::query()
            ->where('user_id', $student->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        $documentTypes = DocumentType::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'category', 'fee', 'processing_days'])
            ->groupBy('category');

        return Inertia::render('Student/Requests/Create', [
            'documentTypeGroups' => $documentTypes,
            'pendingRequestExists' => $pendingRequestExists,
        ]);
    }

    public function store(StoreRequestRequest $request, RequestService $requestService): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $result = $requestService->createRequestBatch(
                $request->user(),
                $validated['document_ids'],
                $validated['purpose'] ?? null
            );
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'document_ids' => $exception->getMessage(),
            ]);
        }

        /** @var DocumentRequest $firstRequest */
        $firstRequest = $result['requests']->first();

        return redirect()
            ->route('student.requests.show', $firstRequest)
            ->with('status', 'Your document request has been submitted.');
    }

    public function show(Request $request, DocumentRequest $documentRequest): Response
    {
        $this->authorize('view', $documentRequest);

        abort_unless($documentRequest->user_id === $request->user()->id, 403);

        $documentRequest->load([
            'documentType:id,name,category,fee,processing_days',
            'payments',
            'clearances.teacherSigner:id,fullname',
            'clearances.deanSigner:id,fullname',
            'clearances.accountingSigner:id,fullname',
            'clearances.saoSigner:id,fullname',
        ]);

        return Inertia::render('Student/Requests/Show', [
            'request' => $documentRequest,
        ]);
    }

    public function cancel(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $this->authorize('cancel', $documentRequest);

        abort_unless($documentRequest->user_id === $request->user()->id, 403);

        $hasUploadedReceipt = $documentRequest->payments()
            ->whereNotNull('receipt_path')
            ->exists();

        if ($hasUploadedReceipt) {
            return back()->withErrors([
                'request' => 'This request cannot be cancelled because a receipt was already uploaded.',
            ]);
        }

        $documentRequest->update([
            'status' => 'cancelled',
            'processing_stage' => 'not_started',
        ]);

        $admins = User::query()
            ->whereIn('role', ['admin', 'superadmin'])
            ->where('status', 'active')
            ->get();

        Notification::send($admins, new RequestCancelledNotification($documentRequest, $request->user()));

        ActivityLogger::log(
            'request_cancelled',
            "User {$request->user()->email} cancelled request {$documentRequest->reference_no}.",
            $request->user(),
            $request->user(),
            ['document_request_id' => $documentRequest->id]
        );

        return back()->with('status', 'Request cancelled successfully.');
    }
}
