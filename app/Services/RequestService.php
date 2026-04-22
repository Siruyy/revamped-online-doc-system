<?php

namespace App\Services;

use App\Events\RequestApproved;
use App\Events\RequestDenied;
use App\Events\RequestStageUpdated;
use App\Events\RequestSubmitted;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RequestService
{
    /**
     * @param  array<int, int|string>  $documentIds
     * @return array{requests: Collection<int, DocumentRequest>, payment: Payment}
     */
    public function createRequestBatch(User $user, array $documentIds, ?string $purpose = null): array
    {
        return DB::transaction(function () use ($user, $documentIds, $purpose): array {
            // Lock the user row so duplicate concurrent submissions cannot bypass active-request checks.
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

            foreach ($documentTypes as $documentType) {
                $createdRequests->push(DocumentRequest::query()->create([
                    'user_id' => $user->id,
                    'document_type_id' => $documentType->id,
                    'status' => 'pending',
                    'processing_stage' => 'not_started',
                    'purpose' => $purpose,
                ]));
            }

            /** @var DocumentRequest $firstRequest */
            $firstRequest = $createdRequests->first();

            $payment = Payment::query()->create([
                'user_id' => $user->id,
                'document_request_id' => $firstRequest->id,
                'total_amount' => $documentTypes->sum('fee'),
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

            return [
                'requests' => $createdRequests,
                'payment' => $payment,
            ];
        });
    }

    public function approveRequest(DocumentRequest $documentRequest, User $admin): DocumentRequest
    {
        if ($documentRequest->status !== 'pending') {
            throw new \RuntimeException('Only pending requests can be approved.');
        }

        $documentRequest->update([
            'status' => 'approved',
            'processing_stage' => 'processing',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'denial_reason' => null,
        ]);

        ActivityLogger::log(
            'request_approved',
            "Admin {$admin->email} approved request {$documentRequest->reference_no}.",
            $admin,
            $documentRequest->user,
            ['document_request_id' => $documentRequest->id]
        );

        RequestApproved::dispatch($documentRequest->id, $documentRequest->user_id, $admin->id);

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

        RequestStageUpdated::dispatch(
            $documentRequest->id,
            $documentRequest->user_id,
            $documentRequest->processing_stage,
            $documentRequest->status,
        );

        return $documentRequest;
    }
}
