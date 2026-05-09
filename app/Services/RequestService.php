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
     * @return array{requests: Collection<int, DocumentRequest>, payment: Payment}
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
            $totalFee = 0.0;

            foreach ($documentTypes as $documentType) {
                $errors = $this->rules->validateEligibility($user, $documentType);

                if ($errors) {
                    throw new \RuntimeException(implode(' ', $errors));
                }

                $fee = $this->rules->computeFee($documentType);
                $totalFee += $fee;

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
            }

            /** @var DocumentRequest $firstRequest */
            $firstRequest = $createdRequests->first();

            $payment = Payment::query()->create([
                'user_id' => $user->id,
                'document_request_id' => $firstRequest->id,
                'total_amount' => $totalFee,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            ActivityLogger::log(
                'request_submitted',
                "User {$user->email} submitted ".count($documentIds).' document request(s).',
                $user,
                $user,
                ['document_request_ids' => $createdRequests->pluck('id')->all(), 'payment_id' => $payment->id]
            );

            RequestSubmitted::dispatch($createdRequests->pluck('id')->all(), $payment->id, $user->id);

            $this->notifyActiveRoles(['admin', 'superadmin'], [
                'type' => 'request_submitted',
                'title' => 'New document request',
                'message' => "{$user->fullname} submitted document requests.",
                'document_request_ids' => $createdRequests->pluck('id')->all(),
                'payment_id' => $payment->id,
                'student_id' => $user->id,
            ]);

            return [
                'requests' => $createdRequests,
                'payment' => $payment,
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

        User::query()->findOrFail($documentRequest->user_id)->notify(new WorkflowStatusNotification([
            'type' => 'request_stage_updated',
            'title' => 'Request status updated',
            'message' => "Your request {$documentRequest->reference_no} moved to {$documentRequest->processing_stage}.",
            'document_request_id' => $documentRequest->id,
            'processing_stage' => $documentRequest->processing_stage,
            'status' => $documentRequest->status,
        ]));

        return $documentRequest;
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

        $pausedFor = $pausedAt->diffInMinutes(now());

        $expectedRelease = $request->expected_release_on ? CarbonImmutable::parse($request->expected_release_on) : null;

        $newExpected = $expectedRelease
            ? $expectedRelease->copy()->addMinutes($pausedFor)
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
