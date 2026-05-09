<?php

namespace App\Services\Policy;

use App\Models\ClaimSlip;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

class ClaimSlipService
{
    public function __construct(private SlaCalculator $sla) {}

    /**
     * Generate or refresh the claim slip for a request that reached
     * `ready_for_pickup`. Idempotent.
     */
    public function issueForRequest(DocumentRequest $request, User $actor): ClaimSlip
    {
        return DB::transaction(function () use ($request, $actor): ClaimSlip {
            $claimDate = $this->sla->addWorkingDays(now(), 1)->toDateString();
            $channel = $request->documentType->release_channel ?? 'registrar_window_9';

            $slip = ClaimSlip::query()->updateOrCreate(
                ['document_request_id' => $request->id],
                [
                    'user_id' => $request->user_id,
                    'release_channel' => $channel,
                    'claim_date' => $claimDate,
                    'state' => 'ready',
                ]
            );

            ActivityLogger::log(
                'claim_slip_issued',
                "Claim slip {$slip->claim_number} issued for request {$request->reference_no}.",
                $actor,
                $request->user,
                ['claim_slip_id' => $slip->id, 'document_request_id' => $request->id]
            );

            return $slip->refresh();
        });
    }

    public function markReleased(
        ClaimSlip $slip,
        User $releaser,
        string $claimantName,
        string $claimantIdReference,
        bool $isProxy = false,
        ?string $authorizationType = null,
        ?string $notes = null,
    ): ClaimSlip {
        return DB::transaction(function () use ($slip, $releaser, $claimantName, $claimantIdReference, $isProxy, $authorizationType, $notes): ClaimSlip {
            if ($slip->state === 'released') {
                return $slip;
            }

            $slip->update([
                'state' => 'released',
                'claimant_name' => $claimantName,
                'claimant_id_reference' => $claimantIdReference,
                'is_proxy_release' => $isProxy,
                'authorization_type' => $authorizationType,
                'notes' => $notes,
                'released_by' => $releaser->id,
                'released_at' => now(),
            ]);

            $slip->documentRequest()->update([
                'status' => 'completed',
                'processing_stage' => 'released',
                'released_at' => now(),
            ]);

            ActivityLogger::log(
                'claim_slip_released',
                "Claim slip {$slip->claim_number} released to {$claimantName} by {$releaser->email}.",
                $releaser,
                $slip->user,
                ['claim_slip_id' => $slip->id]
            );

            return $slip->refresh();
        });
    }

    public function voidSlip(ClaimSlip $slip, User $actor, ?string $reason = null): ClaimSlip
    {
        $slip->update([
            'state' => 'void',
            'notes' => trim(($slip->notes ? $slip->notes."\n" : '').'Voided: '.($reason ?? 'No reason given.')),
        ]);

        ActivityLogger::log(
            'claim_slip_voided',
            "Claim slip {$slip->claim_number} voided by {$actor->email}.",
            $actor,
            $slip->user,
            ['claim_slip_id' => $slip->id, 'reason' => $reason]
        );

        return $slip->refresh();
    }
}
