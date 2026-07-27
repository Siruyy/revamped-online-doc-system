<?php

namespace App\Services;

use App\Models\Clearance;
use App\Models\ClearanceStep;
use App\Models\DocumentRequest;
use App\Models\Payment;
use App\Models\RequestRequirement;
use App\Models\User;
use App\Notifications\WorkflowStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PublicRequestWorkflowService
{
    public function __construct(private ClearanceStepNotificationService $stepNotifications) {}

    /**
     * Lock the registrar's item-by-item quote and open the correct clearance route.
     *
     * @param  array{shipping_fee?: numeric, quote_notes?: string|null, items: list<array{id: int, page_count?: int, base_amount: numeric, authentication_amount?: numeric, documentary_stamp_amount?: numeric, evaluation_notes?: string|null}>}  $quote
     */
    public function evaluate(DocumentRequest $request, User $registrar, array $quote): DocumentRequest
    {
        if ($request->workflow_stage !== 'registrar_review') {
            throw new \RuntimeException('Only requests in registrar review can be evaluated.');
        }

        return DB::transaction(function () use ($request, $registrar, $quote): DocumentRequest {
            $locked = DocumentRequest::query()->lockForUpdate()->findOrFail($request->id);
            $items = $locked->items()->with('documentType')->get()->keyBy('id');
            $quotedIds = collect($quote['items'])->pluck('id')->map(fn ($id) => (int) $id);

            if ($items->isEmpty() || $quotedIds->sort()->values()->all() !== $items->keys()->sort()->values()->all()) {
                throw new \InvalidArgumentException('Every request item must have an evaluation.');
            }

            $subtotal = 0.0;

            foreach ($quote['items'] as $line) {
                $item = $items->get((int) $line['id']);
                $base = max(0, (float) $line['base_amount']);
                $authentication = max(0, (float) ($line['authentication_amount'] ?? 0));
                $stamp = max(0, (float) ($line['documentary_stamp_amount'] ?? 0));
                $lineTotal = $base + $authentication + $stamp;

                $item->update([
                    'evaluated_page_count' => max(1, (int) ($line['page_count'] ?? 1)),
                    'page_count_snapshot' => max(1, (int) ($line['page_count'] ?? 1)),
                    'base_amount' => $base,
                    'authentication_amount' => $authentication,
                    'documentary_stamp_amount' => $stamp,
                    'line_total' => $lineTotal,
                    'evaluation_notes' => $line['evaluation_notes'] ?? null,
                ]);
                $subtotal += $lineTotal;
            }

            $shipping = max(0, (float) ($quote['shipping_fee'] ?? 0));
            $total = $subtotal + $shipping;
            $clearance = Clearance::query()->firstOrCreate(
                ['document_request_id' => $locked->id],
                ['user_id' => $locked->user_id, 'overall_status' => 'in_progress'],
            );
            $this->seedSteps($clearance, $locked);
            $firstStep = $clearance->steps()->orderBy('sequence')->first();

            if ($firstStep instanceof ClearanceStep) {
                $this->stepNotifications->notifyActionable($firstStep);
            }

            $locked->update([
                'fee_snapshot' => $total,
                'shipping_fee' => $shipping,
                'quote_total' => $total,
                'quote_notes' => $quote['quote_notes'] ?? null,
                'evaluated_by' => $registrar->id,
                'evaluated_at' => now(),
                'workflow_stage' => 'clearance',
                'approved_by' => $registrar->id,
                'approved_at' => now(),
            ]);

            ActivityLogger::log(
                'public_request_evaluated',
                "Registrar evaluated request {$locked->reference_no} for ₱".number_format($total, 2).'.',
                $registrar,
                $locked->user,
                ['document_request_id' => $locked->id],
            );
            $this->notify($locked, 'Registrar evaluation complete', "Your request {$locked->reference_no} is now in clearance. The locked quote is ₱".number_format($total, 2).'.');

            return $locked->refresh();
        });
    }

    public function signStep(ClearanceStep $step, User $officer, ?string $remarks = null): ClearanceStep
    {
        return DB::transaction(function () use ($step, $officer, $remarks): ClearanceStep {
            $locked = ClearanceStep::query()->lockForUpdate()->findOrFail($step->id);
            $this->ensureOfficerCanAct($locked, $officer);

            if ($locked->status !== 'pending') {
                throw new \RuntimeException('This clearance step is no longer pending.');
            }

            if ($locked->clearance->steps()->where('sequence', '<', $locked->sequence)->where('status', '!=', 'cleared')->exists()) {
                throw new \RuntimeException('The previous clearance office must sign first.');
            }

            $locked->update([
                'status' => 'cleared',
                'remarks' => $remarks,
                'signed_by' => $officer->id,
                'signed_at' => now(),
            ]);

            $clearance = $locked->clearance;
            $clearance->recomputeOverallStatus()->save();

            if ($clearance->overall_status === 'completed') {
                $request = $clearance->documentRequest;
                Payment::query()->firstOrCreate(
                    ['document_request_id' => $request->id],
                    [
                        'user_id' => $request->user_id,
                        'total_amount' => $request->quote_total ?? $request->fee_snapshot,
                        'status' => 'pending',
                    ],
                );
                $request->update(['workflow_stage' => 'awaiting_payment', 'status' => 'approved']);
                $this->notify($request, 'Clearance complete — payment is now open', "Clearance for {$request->reference_no} is complete. Upload your payment receipt on the tracking page.");
            } else {
                $this->notify($clearance->documentRequest, 'Clearance progress updated', "{$locked->label} cleared request {$clearance->documentRequest->reference_no}.");
                $nextStep = $clearance->steps()
                    ->where('sequence', '>', $locked->sequence)
                    ->where('status', 'pending')
                    ->orderBy('sequence')
                    ->first();

                if ($nextStep instanceof ClearanceStep) {
                    $this->stepNotifications->notifyActionable($nextStep);
                }
            }

            return $locked->refresh();
        });
    }

    public function requestAction(ClearanceStep $step, User $officer, string $remarks): ClearanceStep
    {
        return DB::transaction(function () use ($step, $officer, $remarks): ClearanceStep {
            $locked = ClearanceStep::query()->lockForUpdate()->findOrFail($step->id);
            $this->ensureOfficerCanAct($locked, $officer);

            if ($locked->status !== 'pending') {
                throw new \RuntimeException('This clearance step is no longer pending.');
            }

            $locked->update([
                'status' => 'needs_action',
                'remarks' => $remarks,
                'signed_by' => $officer->id,
                'signed_at' => now(),
            ]);
            $documentRequest = $locked->clearance->documentRequest;
            RequestRequirement::query()->updateOrCreate(
                [
                    'document_request_id' => $documentRequest->id,
                    'requirement_key' => "clearance_follow_up_{$locked->id}",
                ],
                [
                    'label' => "{$locked->label} follow-up document",
                    'status' => 'rejected',
                    'file_path' => null,
                    'notes' => $remarks,
                    'validated_by' => $officer->id,
                    'validated_at' => now(),
                ],
            );
            $this->notify($documentRequest, 'Clearance needs your action', "{$locked->label} requested a correction for {$documentRequest->reference_no}: {$remarks}");

            return $locked->refresh();
        });
    }

    private function seedSteps(Clearance $clearance, DocumentRequest $request): void
    {
        if ($clearance->steps()->exists()) {
            return;
        }

        $request->loadMissing('items.documentType');
        $codes = $request->items->pluck('documentType.code')->filter();
        $categories = $request->items->pluck('documentType.category')->filter();

        if ($request->requester_division === 'basic_education') {
            $offices = ['principal', 'accounting'];
        } elseif ($codes->every(fn (string $code): bool => str_starts_with($code, 'form_137'))) {
            $offices = ['registrar', 'accounting'];
        } elseif ($categories->every(fn (string $category): bool => $category === 'Certification')) {
            $offices = ['dean', 'accounting'];
        } else {
            $offices = ['president', 'dean', 'student_affairs', 'guidance', 'librarian', 'alumni', 'accounting'];
        }

        $labels = [
            'president' => 'Office of the President',
            'dean' => 'Program Dean',
            'student_affairs' => 'Dean of Student Affairs',
            'guidance' => 'Guidance Counselor',
            'librarian' => 'Librarian',
            'alumni' => 'SVC Alumni Officer',
            'registrar' => 'Office of the Registrar',
            'principal' => 'BEC Principal',
            'accounting' => 'Accounting Office',
        ];

        foreach ($offices as $index => $office) {
            $clearance->steps()->create([
                'office_code' => $office,
                'label' => $labels[$office],
                'sequence' => $index + 1,
                'department_code' => $office === 'dean'
                    ? $request->academic_department_code_snapshot
                    : null,
            ]);
        }
    }

    private function ensureOfficerCanAct(ClearanceStep $step, User $officer): void
    {
        $allowedRole = $step->office_code === 'registrar' ? 'admin' : $step->office_code;

        if ($officer->role !== $allowedRole && $officer->role !== 'superadmin') {
            throw new \RuntimeException('You are not assigned to this clearance office.');
        }

        if (
            $step->office_code === 'dean'
            && $step->department_code
            && $officer->role !== 'superadmin'
            && $officer->academicDepartment?->code !== $step->department_code
        ) {
            throw new \RuntimeException('This request belongs to a different academic department.');
        }
    }

    private function notify(DocumentRequest $request, string $title, string $message): void
    {
        if (! $request->requester_email) {
            return;
        }

        Notification::route('mail', $request->requester_email)->notify(new WorkflowStatusNotification([
            'type' => 'public_workflow_updated',
            'title' => $title,
            'message' => $message,
            'url' => route('track-document', ['reference_no' => $request->reference_no]),
        ]));
    }
}
