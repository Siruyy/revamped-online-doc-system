<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreRequestRequest;
use App\Http\Requests\Student\StoreWizardRequest;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\PaymentProfile;
use App\Models\RequestRequirement;
use App\Services\ActivityLogger;
use App\Services\Policy\RequestRulesEngine;
use App\Services\RequestService;
use App\Support\ClearanceSignatories;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RequestController extends Controller
{
    public function index(Request $request): Response
    {
        $student = $request->user();

        $requests = DocumentRequest::query()
            ->with(['documentType:id,name,category,release_channel', 'payments:id,document_request_id,status,total_amount', 'claimSlip'])
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

    public function create(Request $request, RequestRulesEngine $rules): Response
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
            ->get([
                'id', 'code', 'name', 'description', 'category', 'fee',
                'fee_formula', 'default_page_count', 'processing_days',
                'submission_window', 'release_channel', 'offices', 'requirements', 'flags',
            ])
            ->map(function (DocumentType $type) use ($rules) {
                $spec = $rules->rulesFor($type);

                return [
                    'id' => $type->id,
                    'code' => $type->code,
                    'name' => $type->name,
                    'description' => $type->description,
                    'category' => $type->category,
                    'fee' => (float) $type->fee,
                    'fee_formula' => $type->fee_formula,
                    'default_page_count' => max(1, (int) ($type->default_page_count ?: 1)),
                    'sla_days' => $type->processing_days,
                    'submission_window' => $type->submission_window,
                    'submission_window_label' => config('policy.release_channels.'.$type->submission_window, $type->submission_window),
                    'release_channel' => $type->release_channel,
                    'release_channel_label' => config('policy.release_channels.'.$type->release_channel, $type->release_channel),
                    'offices' => collect($spec['offices'])
                        ->map(fn ($key) => [
                            'key' => $key,
                            'label' => config('policy.offices.'.$key.'.label', $key),
                        ])->values(),
                    'requirements' => collect($spec['requirements'])
                        ->map(fn ($key) => [
                            'key' => $key,
                            'label' => config('policy.requirements.'.$key.'.label', $key),
                            'hint' => config('policy.requirements.'.$key.'.hint'),
                        ])->values(),
                    'flags' => $spec['flags'],
                ];
            })
            ->groupBy('category');

        return Inertia::render('Student/Requests/Create', [
            'documentTypeGroups' => $documentTypes,
            'pendingRequestExists' => $pendingRequestExists,
            'student' => [
                'id' => $student->id,
                'fullname' => $student->fullname,
                'academic_status' => $student->academic_status,
                'is_graduate' => (bool) $student->is_graduate,
                'is_nstp' => (bool) $student->is_nstp,
            ],
            'offices' => config('policy.offices'),
            'requirementsCatalog' => config('policy.requirements'),
            'releaseChannels' => config('policy.release_channels'),
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

    public function wizardStore(StoreWizardRequest $request, RequestService $requestService): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $result = $requestService->createMultiItemRequest($request->user(), [
                'items' => $validated['items'],
                'purpose' => $validated['purpose'],
                'extra_data' => $validated['extra_data'] ?? null,
                'context' => [
                    'has_cno' => (bool) ($validated['has_cno'] ?? false),
                    'has_external_notice' => (bool) ($validated['has_external_notice'] ?? false),
                    'special_class_eligibility' => (array) ($validated['special_class_eligibility'] ?? []),
                ],
            ]);
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'items' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('student.requests.show', $result['request'])
            ->with('status', 'Your document request has been submitted. The admin will review it shortly.');
    }

    public function show(Request $request, DocumentRequest $documentRequest, RequestRulesEngine $rules): Response
    {
        $this->authorize('view', $documentRequest);

        abort_unless($documentRequest->user_id === $request->user()->id, 403);

        $documentRequest->load([
            'documentType',
            'items.documentType',
            'payments',
            ...collect(ClearanceSignatories::signerRelations())
                ->map(fn (string $relation): string => "clearances.{$relation}:id,fullname")
                ->all(),
            'requirements',
            'claimSlip',
        ]);

        $rulesSpec = $rules->rulesFor($documentRequest->documentType);
        $paymentProfile = PaymentProfile::active();

        return Inertia::render('Student/Requests/Show', [
            'request' => $documentRequest,
            'policy' => [
                'spec' => $rulesSpec,
                'requirements_catalog' => config('policy.requirements'),
                'release_channels' => config('policy.release_channels'),
                'offices' => config('policy.offices'),
                'clearance_signatories' => ClearanceSignatories::definitions(),
            ],
            'paymentProfile' => $paymentProfile ? [
                'bank_name' => $paymentProfile->bank_name,
                'account_name' => $paymentProfile->account_name,
                'account_number' => $paymentProfile->account_number,
                'qr_url' => $paymentProfile->qr_path
                    ? route('files.payment-qr', $paymentProfile->id)
                    : null,
                'instructions' => $paymentProfile->instructions,
            ] : null,
        ]);
    }

    public function cancel(Request $request, DocumentRequest $documentRequest, RequestService $requests): RedirectResponse
    {
        $this->authorize('cancel', $documentRequest);

        abort_unless($documentRequest->user_id === $request->user()->id, 403);

        try {
            $requests->cancelRequest($documentRequest, $request->user());
        } catch (\RuntimeException $exception) {
            return back()->withErrors([
                'request' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'Request cancelled successfully.');
    }

    public function uploadRequirement(Request $request, DocumentRequest $documentRequest, RequestRequirement $requirement): RedirectResponse
    {
        abort_unless($documentRequest->user_id === $request->user()->id, 403);
        abort_unless($requirement->document_request_id === $documentRequest->id, 404);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'mimetypes:application/pdf,image/png,image/jpeg', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $path = "request-requirements/{$documentRequest->user_id}/{$documentRequest->id}/".Str::uuid().".{$extension}";
        Storage::disk('local')->put($path, $file->getContent());

        $requirement->update([
            'file_path' => $path,
            'status' => 'submitted',
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        ActivityLogger::log(
            'requirement_uploaded',
            "Student {$request->user()->email} uploaded requirement '{$requirement->label}' for request {$documentRequest->reference_no}.",
            $request->user(),
            $request->user(),
            ['document_request_id' => $documentRequest->id, 'requirement_id' => $requirement->id]
        );

        return back()->with('status', 'Requirement uploaded. An admin will review it shortly.');
    }
}
