<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\RequestRequirement;
use App\Services\PaymentService;
use App\Services\PublicRequestWorkflowService;
use App\Services\RequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RequestController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DocumentRequest::class);

        $requests = DocumentRequest::query()
            ->with([
                'user:id,fullname,course,year_level,student_id,academic_status',
                'documentType:id,name,category,processing_days',
                'payments:id,document_request_id,status,total_amount',
                'requirements:id,document_request_id,status',
            ])
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('course')->toString(), function ($query, $course) {
                $query->where(function ($inner) use ($course) {
                    $inner->whereHas('user', fn ($q) => $q->where('course', $course))
                        ->orWhere('requester_course', $course);
                });
            })
            ->when($request->string('year')->toString(), function ($query, $year) {
                $query->where(function ($inner) use ($year) {
                    $inner->whereHas('user', fn ($q) => $q->where('year_level', $year))
                        ->orWhere('requester_year_level', $year);
                });
            })
            ->when($request->string('document_type')->toString(), fn ($query, $typeId) => $query->where('document_type_id', $typeId))
            ->when($request->string('from')->toString(), fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($request->string('to')->toString(), fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($q) => $q->where('fullname', 'like', "%{$search}%")->orWhere('student_id', 'like', "%{$search}%"))
                        ->orWhere('requester_name', 'like', "%{$search}%")
                        ->orWhere('requester_student_id', 'like', "%{$search}%")
                        ->orWhere('requester_course', 'like', "%{$search}%")
                        ->orWhereHas('documentType', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Requests/Index', [
            'requests' => $requests,
            'filters' => [
                'status' => $request->string('status')->toString(),
                'course' => $request->string('course')->toString(),
                'year' => $request->string('year')->toString(),
                'document_type' => $request->string('document_type')->toString(),
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function show(DocumentRequest $documentRequest): Response
    {
        $this->authorize('view', $documentRequest);

        $documentRequest->load([
            'user:id,fullname,email,course,year_level,student_id,contact_number,academic_status',
            'documentType',
            'items.documentType:id,code,name,category,fee,fee_formula,default_page_count',
            'payments',
            'clearances.steps.signer:id,fullname',
            'requirements',
            'claimSlip',
        ]);

        $batchRequests = $documentRequest->user_id === null
            ? collect()
            : DocumentRequest::query()
                ->with(['documentType:id,name,category'])
                ->where('user_id', $documentRequest->user_id)
                ->whereDate('created_at', $documentRequest->created_at->toDateString())
                ->where('id', '!=', $documentRequest->id)
                ->orderBy('id')
                ->get();

        return Inertia::render('Admin/Requests/Show', [
            'request' => $documentRequest,
            'batchRequests' => $batchRequests,
            'policy' => [
                'sla_pause_reasons' => config('policy.sla.pause_reasons', []),
                'release_channels' => config('policy.release_channels', []),
                'requirements_catalog' => config('policy.requirements', []),
            ],
        ]);
    }

    public function approve(DocumentRequest $documentRequest, RequestService $requestService): RedirectResponse
    {
        $this->authorize('approve', $documentRequest);

        try {
            $requestService->approveRequest($documentRequest, request()->user());
        } catch (\Throwable $exception) {
            return back()->withErrors(['request' => $exception->getMessage()]);
        }

        return back()->with('status', 'Request approved successfully.');
    }

    public function deny(Request $request, DocumentRequest $documentRequest, RequestService $requestService): RedirectResponse
    {
        $this->authorize('deny', $documentRequest);

        $validated = $request->validate([
            'denial_reason' => ['required', 'string', 'max:500'],
        ]);
        $reason = trim(strip_tags($validated['denial_reason']));

        if ($reason === '') {
            return back()->withErrors(['denial_reason' => 'A denial reason is required.'])->withInput();
        }

        try {
            $requestService->denyRequest($documentRequest, $request->user(), $reason);
        } catch (\Throwable $exception) {
            if (str_contains($exception->getMessage(), 'package denial')) {
                return back()->withErrors(['request' => $exception->getMessage()]);
            }

            return back()->withErrors(['denial_reason' => $exception->getMessage()]);
        }

        return back()->with('status', 'Request denied successfully.');
    }

    public function approveWithPayment(DocumentRequest $documentRequest, RequestService $requestService, PaymentService $paymentService): RedirectResponse
    {
        $this->authorize('approve', $documentRequest);

        try {
            $requestService->approvePublicRequestPackage($documentRequest, request()->user(), $paymentService);
        } catch (\RuntimeException $exception) {
            return back()->withErrors([$this->packageErrorKey($exception->getMessage()) => $exception->getMessage()]);
        }

        return back()->with('status', 'Request and payment approved successfully.');
    }

    public function evaluate(Request $request, DocumentRequest $documentRequest, PublicRequestWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('approve', $documentRequest);
        $validated = $request->validate([
            'shipping_fee' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'quote_notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.page_count' => ['required', 'integer', 'min:1', 'max:500'],
            'items.*.base_amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.authentication_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.documentary_stamp_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.evaluation_notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $workflow->evaluate($documentRequest, $request->user(), $validated);
        } catch (\Throwable $exception) {
            return back()->withErrors(['evaluation' => $exception->getMessage()]);
        }

        return back()->with('status', 'Quote locked and clearance routing started.');
    }

    public function signRegistrarClearance(
        Request $request,
        DocumentRequest $documentRequest,
        PublicRequestWorkflowService $workflow,
    ): RedirectResponse {
        $this->authorize('updateStage', $documentRequest);
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);
        $step = $documentRequest->clearances()
            ->firstOrFail()
            ->steps()
            ->where('office_code', 'registrar')
            ->firstOrFail();

        try {
            $workflow->signStep($step, $request->user(), $validated['remarks'] ?? null);
        } catch (\Throwable $exception) {
            return back()->withErrors(['registrar_clearance' => $exception->getMessage()]);
        }

        return back()->with('status', 'Registrar clearance signed.');
    }

    public function denyWithPayment(Request $request, DocumentRequest $documentRequest, RequestService $requestService, PaymentService $paymentService): RedirectResponse
    {
        $this->authorize('deny', $documentRequest);

        $validated = $request->validate([
            'denial_reason' => ['required', 'string', 'max:500'],
        ]);
        $reason = trim(strip_tags($validated['denial_reason']));

        if ($reason === '') {
            return back()->withErrors(['denial_reason' => 'A denial reason is required.'])->withInput();
        }

        try {
            $requestService->denyPublicRequestPackage($documentRequest, $request->user(), $reason, $paymentService);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['request' => $exception->getMessage()]);
        }

        return back()->with('status', 'Request and payment denied successfully.');
    }

    public function updateStage(Request $request, DocumentRequest $documentRequest, RequestService $requestService): RedirectResponse
    {
        $this->authorize('updateStage', $documentRequest);

        $validated = $request->validate([
            'processing_stage' => ['required', 'in:processing,ready_for_pickup,released'],
        ]);

        try {
            $requestService->updateStage($documentRequest, $request->user(), $validated['processing_stage']);
        } catch (\Throwable $exception) {
            return back()->withErrors(['processing_stage' => $exception->getMessage()]);
        }

        return back()->with('status', 'Request stage updated successfully.');
    }

    public function validateRequirement(DocumentRequest $documentRequest, RequestRequirement $requirement, RequestService $requestService): RedirectResponse
    {
        $this->authorize('updateStage', $documentRequest);
        abort_unless($requirement->document_request_id === $documentRequest->id, 404);

        try {
            $requestService->validateRequirement($documentRequest, $requirement, request()->user());
        } catch (\Throwable $exception) {
            return back()->withErrors(['requirement' => $exception->getMessage()]);
        }

        return back()->with('status', 'Requirement validated.');
    }

    public function rejectRequirement(Request $request, DocumentRequest $documentRequest, RequestRequirement $requirement, RequestService $requestService): RedirectResponse
    {
        $this->authorize('updateStage', $documentRequest);
        abort_unless($requirement->document_request_id === $documentRequest->id, 404);

        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:500'],
        ]);

        try {
            $requestService->rejectRequirement($documentRequest, $requirement, $request->user(), $validated['notes']);
        } catch (\Throwable $exception) {
            return back()->withErrors(['notes' => $exception->getMessage()]);
        }

        return back()->with('status', 'Requirement marked for revision.');
    }

    public function pauseSla(Request $request, DocumentRequest $documentRequest, RequestService $service): RedirectResponse
    {
        $this->authorize('updateStage', $documentRequest);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:100'],
        ]);

        try {
            $service->pauseSla($documentRequest, $request->user(), $validated['reason']);
        } catch (\Throwable $e) {
            return back()->withErrors(['reason' => $e->getMessage()]);
        }

        return back()->with('status', 'SLA paused.');
    }

    public function resumeSla(DocumentRequest $documentRequest, RequestService $service): RedirectResponse
    {
        $this->authorize('updateStage', $documentRequest);
        $service->resumeSla($documentRequest, request()->user());

        return back()->with('status', 'SLA resumed.');
    }

    public function release(Request $request, DocumentRequest $documentRequest, RequestService $service): RedirectResponse
    {
        $this->authorize('updateStage', $documentRequest);

        try {
            $service->updateStage($documentRequest, $request->user(), 'released');
        } catch (\Throwable $exception) {
            return back()->withErrors(['processing_stage' => $exception->getMessage()]);
        }

        return back()->with('status', 'Request marked as released.');
    }

    private function packageErrorKey(string $message): string
    {
        return str_contains($message, 'payment') ? 'payment'
            : (str_contains($message, 'requirement') ? 'requirement' : 'request');
    }
}
