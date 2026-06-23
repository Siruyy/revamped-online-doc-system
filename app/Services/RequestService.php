<?php

namespace App\Services;

use App\Events\RequestApproved;
use App\Events\RequestDenied;
use App\Events\RequestStageUpdated;
use App\Events\RequestSubmitted;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\RequestRequirement;
use App\Models\User;
use App\Notifications\RequestCancelledNotification;
use App\Notifications\WorkflowStatusNotification;
use App\Services\Policy\ClaimSlipService;
use App\Services\Policy\RequestRulesEngine;
use App\Services\Policy\SlaCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RequestService
{
    public function __construct(
        private RequestRulesEngine $rules,
        private SlaCalculator $sla,
        private ClaimSlipService $claimSlips,
    ) {}

    /**
     * Create a new document request with one or more items (policy-initial flow).
     *
     * Spec shape:
     * {
     *   items: [{document_type_id: int, copies: int}],
     *   purpose: string,
     *   intake_mode?: string,
     *   extra_data?: array,
     *   context?: {
     *     transfer_exception_approved?: bool,
     *     has_cno?: bool,
     *     has_external_notice?: bool,
     *     special_class_eligibility?: array
     *   }
     * }
     *
     * @param  array<string, mixed>  $spec
     * @return array{request: DocumentRequest, payment: Payment}
     */
    public function createMultiItemRequest(User $user, array $spec): array
    {
        return DB::transaction(function () use ($user, $spec): array {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            if ($user->documentRequests()->whereIn('status', ['pending', 'approved'])->exists()) {
                throw new \RuntimeException('You still have an active request in progress.');
            }

            $items = $spec['items'] ?? [];

            if (empty($items)) {
                throw new \InvalidArgumentException('At least one document type must be selected.');
            }

            $context = [
                'transfer_exception_approved' => (bool) ($spec['context']['transfer_exception_approved'] ?? false),
                'has_cno' => (bool) ($spec['context']['has_cno'] ?? false),
                'has_external_notice' => (bool) ($spec['context']['has_external_notice'] ?? false),
                'special_class_eligibility' => (array) ($spec['context']['special_class_eligibility'] ?? []),
            ];

            // Pre-validate all items before creating anything.
            $resolvedItems = [];

            foreach ($items as $itemSpec) {
                $typeId = (int) ($itemSpec['document_type_id'] ?? 0);
                $copies = max(1, (int) ($itemSpec['copies'] ?? 1));

                $type = DocumentType::query()
                    ->where('id', $typeId)
                    ->where('is_active', true)
                    ->firstOrFail();

                $errors = $this->rules->validateEligibility($user, $type, $context);

                if ($errors) {
                    throw new \RuntimeException(implode(' ', $errors));
                }

                $pageCount = max(1, (int) ($type->default_page_count ?: 1));
                $feePerPage = (float) $type->fee;
                $lineTotal = $this->computeLineTotal($type, $pageCount, $copies);

                $resolvedItems[] = [
                    'type' => $type,
                    'copies' => $copies,
                    'page_count' => $pageCount,
                    'fee_per_page' => $feePerPage,
                    'line_total' => $lineTotal,
                ];
            }

            $totalFee = array_sum(array_column($resolvedItems, 'line_total'));

            /** @var DocumentType $primaryType */
            $primaryType = $resolvedItems[0]['type'];

            $requiresHdReturn = collect($resolvedItems)->contains(
                fn ($i) => $i['type']->hasFlag('requires_hd_return')
            );

            $request = DocumentRequest::query()->create([
                'user_id' => $user->id,
                'document_type_id' => $primaryType->id,
                'quantity' => array_sum(array_column($resolvedItems, 'copies')),
                'page_count' => null,
                'fee_snapshot' => $totalFee,
                'status' => 'pending',
                'processing_stage' => 'not_started',
                'intake_mode' => $spec['intake_mode'] ?? 'online',
                'purpose' => $spec['purpose'] ?? null,
                'extra_data' => $spec['extra_data'] ?? null,
                'requires_hd_return' => $requiresHdReturn,
                'transfer_exception_requested' => $user->isTransferred(),
            ]);

            // Create item lines.
            foreach ($resolvedItems as $itemData) {
                DocumentRequestItem::query()->create([
                    'document_request_id' => $request->id,
                    'document_type_id' => $itemData['type']->id,
                    'copies' => $itemData['copies'],
                    'page_count_snapshot' => $itemData['page_count'],
                    'fee_per_page_snapshot' => $itemData['fee_per_page'],
                    'line_total' => $itemData['line_total'],
                ]);

                // Seed requirements from each item type.
                $this->seedRequirements($request, $itemData['type']);
            }

            // Payment record is created at submission (status=pending).
            // Upload remains locked until admin approves the request.
            $payment = Payment::query()->create([
                'user_id' => $user->id,
                'document_request_id' => $request->id,
                'total_amount' => $totalFee,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            ActivityLogger::log(
                'request_submitted',
                "User {$user->email} submitted a {$primaryType->name} request ({$request->reference_no}).",
                $user,
                $user,
                ['document_request_id' => $request->id, 'payment_id' => $payment->id, 'items' => count($resolvedItems)]
            );

            RequestSubmitted::dispatch([$request->id], $payment->id, $user->id);

            $this->notifyActiveRoles(['admin', 'superadmin'], [
                'type' => 'request_submitted',
                'title' => 'New document request',
                'message' => "{$user->fullname} submitted a document request.",
                'document_request_ids' => [$request->id],
                'payment_id' => $payment->id,
                'student_id' => $user->id,
            ]);

            return ['request' => $request->refresh(), 'payment' => $payment->refresh()];
        });
    }

    /**
     * Legacy batch entry-point. Kept so earlier pages and tests keep working.
     *
     * @param  array<int, int|string>  $documentIds
     * @return array{requests: Collection<int, DocumentRequest>, payment: Payment, payments: Collection<int, Payment>}
     */
    public function createRequestBatch(User $user, array $documentIds, ?string $purpose = null): array
    {
        return DB::transaction(function () use ($user, $documentIds, $purpose): array {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            if ($user->documentRequests()->whereIn('status', ['pending', 'approved'])->exists()) {
                throw new \RuntimeException('You still have an active request in progress.');
            }

            $documentTypes = DocumentType::query()
                ->whereIn('id', $documentIds)
                ->where('is_active', true)
                ->get();

            if ($documentTypes->count() !== count($documentIds)) {
                throw new \InvalidArgumentException('One or more selected document types are invalid.');
            }

            $createdRequests = collect();
            $createdPayments = collect();

            foreach ($documentTypes as $documentType) {
                $errors = $this->rules->validateEligibility($user, $documentType);

                if ($errors) {
                    throw new \RuntimeException(implode(' ', $errors));
                }

                $fee = $this->rules->computeFee($documentType);

                $request = DocumentRequest::query()->create([
                    'user_id' => $user->id,
                    'document_type_id' => $documentType->id,
                    'quantity' => 1,
                    'fee_snapshot' => $fee,
                    'status' => 'pending',
                    'processing_stage' => 'not_started',
                    'purpose' => $purpose,
                    'intake_mode' => 'online',
                    'requires_hd_return' => $documentType->hasFlag('requires_hd_return'),
                ]);

                $this->seedRequirements($request, $documentType);

                $createdRequests->push($request);

                $createdPayments->push(Payment::query()->create([
                    'user_id' => $user->id,
                    'document_request_id' => $request->id,
                    'total_amount' => $fee,
                    'status' => 'pending',
                    'submitted_at' => now(),
                ]));
            }

            $firstPayment = $createdPayments->first();

            if (! $firstPayment instanceof Payment) {
                throw new \RuntimeException('Payment creation failed unexpectedly for the submitted document requests.');
            }

            ActivityLogger::log(
                'request_submitted',
                "User {$user->email} submitted ".count($documentIds).' document request(s).',
                $user,
                $user,
                [
                    'document_request_ids' => $createdRequests->pluck('id')->all(),
                    'payment_id' => $firstPayment->id,
                    'payment_ids' => $createdPayments->pluck('id')->all(),
                ]
            );

            RequestSubmitted::dispatch($createdRequests->pluck('id')->all(), $firstPayment->id, $user->id);

            $this->notifyActiveRoles(['admin', 'superadmin'], [
                'type' => 'request_submitted',
                'title' => 'New document request',
                'message' => "{$user->fullname} submitted document requests.",
                'document_request_ids' => $createdRequests->pluck('id')->all(),
                'payment_id' => $firstPayment->id,
                'payment_ids' => $createdPayments->pluck('id')->all(),
                'student_id' => $user->id,
            ]);

            return [
                'requests' => $createdRequests,
                'payment' => $firstPayment,
                'payments' => $createdPayments,
            ];
        });
    }

    /**
     * Policy-aware single-type wizard submission. Delegates to createMultiItemRequest.
     *
     * @param  array<string, mixed>  $spec
     * @return array{request: DocumentRequest, payment: Payment}
     */
    public function createPolicyAwareRequest(User $user, array $spec): array
    {
        return $this->createMultiItemRequest($user, [
            'items' => [[
                'document_type_id' => $spec['document_type_id'],
                'copies' => max(1, (int) ($spec['quantity'] ?? 1)),
            ]],
            'purpose' => $spec['purpose'] ?? null,
            'intake_mode' => $spec['intake_mode'] ?? 'online',
            'extra_data' => $spec['extra_data'] ?? null,
            'context' => [
                'transfer_exception_approved' => $spec['transfer_exception_approved'] ?? false,
                'has_cno' => $spec['has_cno'] ?? false,
                'has_external_notice' => $spec['has_external_notice'] ?? false,
                'special_class_eligibility' => $spec['special_class_eligibility'] ?? [],
            ],
        ]);
    }

    /**
     * Policy-initial flow: admin approves request before payment.
     * Approval unlocks the student's payment upload step.
     */
    public function approveRequest(DocumentRequest $documentRequest, User $admin): DocumentRequest
    {
        if ($documentRequest->user_id === null || $documentRequest->intake_mode === 'public') {
            throw new \RuntimeException('Use package approval for public requests.');
        }

        if ($documentRequest->status !== 'pending') {
            throw new \RuntimeException('Only pending requests can be approved.');
        }

        $now = now();
        $slaDays = $documentRequest->documentType->processing_days ?: 3;

        $canStartClock = ! $documentRequest->requires_hd_return || $documentRequest->hd_received_at;

        $updates = [
            'status' => 'approved',
            'processing_stage' => 'processing',
            'approved_by' => $admin->id,
            'approved_at' => $now,
            'denial_reason' => null,
        ];

        if ($canStartClock) {
            $updates['sla_start_at'] = $now;
            $updates['expected_release_on'] = $this->sla->expectedReleaseOn($now, $slaDays)->toDateString();
        }

        $documentRequest->update($updates);

        ActivityLogger::log(
            'request_approved',
            "Admin {$admin->email} approved request {$documentRequest->reference_no}.",
            $admin,
            $documentRequest->user,
            ['document_request_id' => $documentRequest->id]
        );

        RequestApproved::dispatch($documentRequest->id, $documentRequest->user_id, $admin->id);

        User::query()->findOrFail($documentRequest->user_id)->notify(new WorkflowStatusNotification([
            'type' => 'request_approved',
            'title' => 'Request approved',
            'message' => "Your request {$documentRequest->reference_no} was approved.",
            'document_request_id' => $documentRequest->id,
        ]));

        return $documentRequest->refresh();
    }

    public function denyRequest(DocumentRequest $documentRequest, User $admin, string $reason): DocumentRequest
    {
        if ($documentRequest->user_id === null || $documentRequest->intake_mode === 'public') {
            throw new \RuntimeException('Use package denial for public requests.');
        }

        if (! in_array($documentRequest->status, ['pending', 'approved'], true)) {
            throw new \RuntimeException('This request can no longer be denied.');
        }

        $documentRequest->update([
            'status' => 'denied',
            'processing_stage' => 'not_started',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'denial_reason' => $reason,
        ]);

        ActivityLogger::log(
            'request_denied',
            "Admin {$admin->email} denied request {$documentRequest->reference_no}.",
            $admin,
            $documentRequest->user,
            ['document_request_id' => $documentRequest->id, 'reason' => $reason]
        );

        RequestDenied::dispatch(
            $documentRequest->id,
            $documentRequest->user_id,
            $admin->id,
            $reason
        );

        User::query()->findOrFail($documentRequest->user_id)->notify(new WorkflowStatusNotification([
            'type' => 'request_denied',
            'title' => 'Request denied',
            'message' => "Your request {$documentRequest->reference_no} was denied.",
            'document_request_id' => $documentRequest->id,
            'reason' => $reason,
        ]));

        return $documentRequest->refresh();
    }

    public function approvePublicRequestPackage(DocumentRequest $documentRequest, User $admin, PaymentService $payments): DocumentRequest
    {
        return DB::transaction(function () use ($documentRequest, $admin, $payments): DocumentRequest {
            /** @var DocumentRequest $locked */
            $locked = DocumentRequest::query()
                ->with(['documentType', 'requirements', 'payments'])
                ->whereKey($documentRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->user_id !== null || $locked->intake_mode !== 'public') {
                throw new \RuntimeException('Only public requests can use package approval.');
            }

            if ($locked->status !== 'pending') {
                throw new \RuntimeException('Only pending requests can be approved.');
            }

            if ($locked->requirements->contains(fn (RequestRequirement $requirement): bool => $requirement->status !== 'validated')) {
                throw new \RuntimeException('All requirements must be validated before approval.');
            }

            /** @var Payment|null $payment */
            $payment = $locked->payments->sortByDesc('submitted_at')->first();

            if (! $payment instanceof Payment || $payment->status !== 'pending_approval') {
                throw new \RuntimeException('A pending approval payment is required before approval.');
            }

            $now = now();
            $slaDays = $locked->documentType->processing_days ?: 3;
            $canStartClock = ! $locked->requires_hd_return || $locked->hd_received_at;
            $updates = [
                'status' => 'approved',
                'processing_stage' => 'processing',
                'approved_by' => $admin->id,
                'approved_at' => $now,
                'denial_reason' => null,
            ];

            if ($canStartClock) {
                $updates['sla_start_at'] = $now;
                $updates['expected_release_on'] = $this->sla->expectedReleaseOn($now, $slaDays)->toDateString();
            }

            $locked->update($updates);
            $payments->approve($payment, $admin);

            RequestApproved::dispatch($locked->id, $locked->user_id, $admin->id);

            $this->notifyPublicRequestor($locked, [
                'type' => 'request_approved',
                'title' => 'Your document request was approved',
                'message' => "Your request {$locked->reference_no} and payment receipt were approved. Track the request for processing updates.",
                'url' => route('track-document', ['reference_no' => $locked->reference_no]),
                'document_request_id' => $locked->id,
                'status' => 'approved',
            ]);

            ActivityLogger::log(
                'public_request_package_approved',
                "Admin {$admin->email} approved public request package {$locked->reference_no}.",
                $admin,
                null,
                ['document_request_id' => $locked->id, 'payment_id' => $payment->id]
            );

            return $locked->refresh();
        });
    }

    public function denyPublicRequestPackage(DocumentRequest $documentRequest, User $admin, string $reason, PaymentService $payments): DocumentRequest
    {
        return DB::transaction(function () use ($documentRequest, $admin, $reason, $payments): DocumentRequest {
            /** @var DocumentRequest $locked */
            $locked = DocumentRequest::query()
                ->with('payments')
                ->whereKey($documentRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->user_id !== null || $locked->intake_mode !== 'public') {
                throw new \RuntimeException('Only public requests can use package denial.');
            }

            if (! in_array($locked->status, ['pending', 'approved'], true)) {
                throw new \RuntimeException('This request can no longer be denied.');
            }

            $locked->update([
                'status' => 'denied',
                'processing_stage' => 'not_started',
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'denial_reason' => $reason,
            ]);

            $locked->payments
                ->where('status', 'pending_approval')
                ->each(function (Payment $payment) use ($admin, $reason, $payments): void {
                    $payments->deny($payment, $admin, $reason);
                });

            RequestDenied::dispatch($locked->id, $locked->user_id, $admin->id, $reason);

            $this->notifyPublicRequestor($locked, [
                'type' => 'request_denied',
                'title' => 'Your document request was denied',
                'message' => "Your request {$locked->reference_no} was denied. Reason: {$reason}",
                'url' => route('track-document', ['reference_no' => $locked->reference_no]),
                'document_request_id' => $locked->id,
                'status' => 'denied',
                'reason' => $reason,
            ]);

            ActivityLogger::log(
                'public_request_package_denied',
                "Admin {$admin->email} denied public request package {$locked->reference_no}.",
                $admin,
                null,
                ['document_request_id' => $locked->id, 'reason' => $reason]
            );

            return $locked->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notifyPublicRequestor(DocumentRequest $documentRequest, array $data): void
    {
        if (! is_string($documentRequest->requester_email) || $documentRequest->requester_email === '') {
            return;
        }

        Notification::route('mail', $documentRequest->requester_email)
            ->notify(new WorkflowStatusNotification($data));
    }

    public function validateRequirement(DocumentRequest $documentRequest, RequestRequirement $requirement, User $admin): RequestRequirement
    {
        $this->ensureRequirementBelongsToRequest($documentRequest, $requirement);

        $requirement->update([
            'status' => 'validated',
            'notes' => null,
            'validated_by' => $admin->id,
            'validated_at' => now(),
        ]);

        ActivityLogger::log(
            'requirement_validated',
            "Admin {$admin->email} validated requirement {$requirement->requirement_key} for {$documentRequest->reference_no}.",
            $admin,
            $documentRequest->user,
            [
                'document_request_id' => $documentRequest->id,
                'requirement_id' => $requirement->id,
                'requirement_key' => $requirement->requirement_key,
            ]
        );

        if ($documentRequest->user_id !== null) {
            User::query()->findOrFail($documentRequest->user_id)->notify(new WorkflowStatusNotification([
                'type' => 'requirement_validated',
                'title' => 'Requirement validated',
                'message' => "Your {$requirement->label} requirement was validated.",
                'document_request_id' => $documentRequest->id,
                'status' => 'validated',
                'url' => route('student.requests.show', $documentRequest),
            ]));
        }

        return $requirement->refresh();
    }

    public function rejectRequirement(DocumentRequest $documentRequest, RequestRequirement $requirement, User $admin, string $notes): RequestRequirement
    {
        $this->ensureRequirementBelongsToRequest($documentRequest, $requirement);

        $requirement->update([
            'status' => 'rejected',
            'notes' => $notes,
            'validated_by' => $admin->id,
            'validated_at' => now(),
        ]);

        ActivityLogger::log(
            'requirement_rejected',
            "Admin {$admin->email} rejected requirement {$requirement->requirement_key} for {$documentRequest->reference_no}.",
            $admin,
            $documentRequest->user,
            [
                'document_request_id' => $documentRequest->id,
                'requirement_id' => $requirement->id,
                'requirement_key' => $requirement->requirement_key,
                'notes' => $notes,
            ]
        );

        if ($documentRequest->user_id !== null) {
            User::query()->findOrFail($documentRequest->user_id)->notify(new WorkflowStatusNotification([
                'type' => 'requirement_rejected',
                'title' => 'Requirement needs revision',
                'message' => "Your {$requirement->label} requirement needs revision.",
                'document_request_id' => $documentRequest->id,
                'status' => 'rejected',
                'url' => route('student.requests.show', $documentRequest),
            ]));
        }

        return $requirement->refresh();
    }

    public function cancelRequest(DocumentRequest $documentRequest, User $student): DocumentRequest
    {
        if ($documentRequest->user_id !== $student->id) {
            throw new \RuntimeException('Only the request owner can cancel this request.');
        }

        $hasUploadedReceipt = $documentRequest->payments()
            ->whereNotNull('receipt_path')
            ->exists();

        if ($hasUploadedReceipt) {
            throw new \RuntimeException('This request cannot be cancelled because a receipt was already uploaded.');
        }

        $documentRequest->update([
            'status' => 'cancelled',
            'processing_stage' => 'not_started',
        ]);

        $admins = User::query()
            ->whereIn('role', ['admin', 'superadmin'])
            ->where('status', 'active')
            ->get();

        Notification::send($admins, new RequestCancelledNotification($documentRequest, $student));

        ActivityLogger::log(
            'request_cancelled',
            "User {$student->email} cancelled request {$documentRequest->reference_no}.",
            $student,
            $student,
            ['document_request_id' => $documentRequest->id]
        );

        return $documentRequest->refresh();
    }

    public function updateStage(DocumentRequest $documentRequest, User $admin, string $stage): DocumentRequest
    {
        $allowedStages = ['processing', 'ready_for_pickup', 'released'];

        if (! in_array($stage, $allowedStages, true)) {
            throw new \InvalidArgumentException('Invalid processing stage.');
        }

        if ($documentRequest->status !== 'approved') {
            throw new \RuntimeException('Only approved requests can move between processing stages.');
        }

        $this->ensureStageGatesArePassed($documentRequest, $stage);

        $updates = ['processing_stage' => $stage];

        if ($stage === 'released') {
            $updates['status'] = 'completed';
            $updates['released_at'] = now();
        }

        $documentRequest->update($updates);

        ActivityLogger::log(
            'request_stage_updated',
            "Admin {$admin->email} updated request {$documentRequest->reference_no} stage to {$stage}.",
            $admin,
            $documentRequest->user,
            ['document_request_id' => $documentRequest->id, 'stage' => $stage]
        );

        $documentRequest->refresh();

        if ($stage === 'ready_for_pickup') {
            $this->claimSlips->issueForRequest($documentRequest, $admin);
        }

        RequestStageUpdated::dispatch(
            $documentRequest->id,
            $documentRequest->user_id,
            $documentRequest->processing_stage,
            $documentRequest->status,
        );

        if ($documentRequest->user_id !== null) {
            User::query()->findOrFail($documentRequest->user_id)->notify(new WorkflowStatusNotification([
                'type' => 'request_stage_updated',
                'title' => 'Request status updated',
                'message' => "Your request {$documentRequest->reference_no} moved to {$documentRequest->processing_stage}.",
                'document_request_id' => $documentRequest->id,
                'processing_stage' => $documentRequest->processing_stage,
                'status' => $documentRequest->status,
            ]));
        }

        return $documentRequest;
    }

    private function ensureStageGatesArePassed(DocumentRequest $documentRequest, string $stage): void
    {
        if ($stage === 'processing') {
            return;
        }

        $stageLabel = str_replace('_', ' ', $stage);

        if (! $documentRequest->payments()->where('status', 'approved')->exists()) {
            throw new \RuntimeException("Approve payment before moving this request to {$stageLabel}.");
        }

        if ($this->requestRequiresClearance($documentRequest)
            && ! $documentRequest->clearances()->where('overall_status', 'completed')->exists()) {
            throw new \RuntimeException("Complete clearance before moving this request to {$stageLabel}.");
        }
    }

    private function ensureRequirementBelongsToRequest(DocumentRequest $documentRequest, RequestRequirement $requirement): void
    {
        if ($requirement->document_request_id !== $documentRequest->id) {
            throw new \RuntimeException('Requirement does not belong to this document request.');
        }
    }

    private function requestRequiresClearance(DocumentRequest $documentRequest): bool
    {
        $items = $documentRequest->items()->with('documentType')->get();

        if ($items->isNotEmpty()) {
            return $items->contains(
                fn (DocumentRequestItem $item): bool => $item->documentType instanceof DocumentType
                    && $item->documentType->requiresClearance()
            );
        }

        $documentType = $documentRequest->documentType;

        return $documentType instanceof DocumentType ? $documentType->requiresClearance() : true;
    }

    public function pauseSla(DocumentRequest $request, User $admin, string $reason): DocumentRequest
    {
        if (! in_array($reason, array_keys(config('policy.sla.pause_reasons', [])), true)) {
            throw new \InvalidArgumentException('Invalid SLA pause reason.');
        }

        $request->update([
            'sla_paused_at' => now(),
            'sla_resumed_at' => null,
            'sla_pause_reason' => $reason,
        ]);

        ActivityLogger::log(
            'request_sla_paused',
            "Admin {$admin->email} paused SLA for {$request->reference_no} ({$reason}).",
            $admin,
            $request->user,
            ['document_request_id' => $request->id, 'reason' => $reason]
        );

        return $request->refresh();
    }

    public function resumeSla(DocumentRequest $request, User $admin): DocumentRequest
    {
        if (! $request->sla_paused_at) {
            return $request;
        }

        $pausedAt = CarbonImmutable::parse($request->sla_paused_at);

        $pausedForSeconds = (int) ceil($pausedAt->diffInSeconds(now()));

        $expectedRelease = $request->expected_release_on ? CarbonImmutable::parse($request->expected_release_on) : null;

        $newExpected = $expectedRelease
            ? $expectedRelease->copy()->addSeconds($pausedForSeconds)
            : null;

        $request->update([
            'sla_paused_at' => null,
            'sla_resumed_at' => now(),
            'sla_pause_reason' => null,
            'expected_release_on' => $newExpected?->toDateString(),
        ]);

        ActivityLogger::log(
            'request_sla_resumed',
            "Admin {$admin->email} resumed SLA for {$request->reference_no}.",
            $admin,
            $request->user,
            ['document_request_id' => $request->id]
        );

        return $request->refresh();
    }

    public function markHonorableDismissalReceived(DocumentRequest $request, User $admin): DocumentRequest
    {
        if (! $request->requires_hd_return) {
            throw new \RuntimeException('This request does not wait for a Honorable Dismissal return.');
        }

        $now = now();
        $slaDays = $request->documentType->processing_days ?: 14;

        $request->update([
            'hd_received_at' => $now,
            'sla_start_at' => $request->sla_start_at ?: $now,
            'expected_release_on' => $this->sla->expectedReleaseOn($now, $slaDays)->toDateString(),
        ]);

        ActivityLogger::log(
            'request_hd_received',
            "Admin {$admin->email} marked Honorable Dismissal returned for {$request->reference_no}.",
            $admin,
            $request->user,
            ['document_request_id' => $request->id]
        );

        return $request->refresh();
    }

    /**
     * Compute line total: fee_per_page × page_count × copies.
     */
    public function computeLineTotal(DocumentType $type, int $pageCount, int $copies): float
    {
        return round((float) $type->fee * $pageCount * $copies, 2);
    }

    protected function seedRequirements(DocumentRequest $request, DocumentType $type): void
    {
        $catalog = config('policy.requirements', []);

        foreach ((array) $type->requirements as $key) {
            $label = $catalog[$key]['label'] ?? $key;
            RequestRequirement::query()->updateOrCreate(
                ['document_request_id' => $request->id, 'requirement_key' => $key],
                [
                    'label' => $label,
                    'status' => 'missing',
                ]
            );
        }
    }

    /**
     * @param  array<int, string>  $roles
     * @param  array<string, mixed>  $data
     */
    private function notifyActiveRoles(array $roles, array $data): void
    {
        Notification::send(
            User::query()->whereIn('role', $roles)->where('status', 'active')->get(),
            new WorkflowStatusNotification($data),
        );
    }
}
