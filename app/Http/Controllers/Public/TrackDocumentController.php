<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\TrackDocumentRequest;
use App\Models\ClaimSlip;
use App\Models\Clearance;
use App\Models\ClearanceStep;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\PaymentProfile;
use App\Models\RequestRequirement;
use App\Support\PublicRequestOptions;
use DateTimeInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrackDocumentController extends Controller
{
    public function create(Request $request): Response
    {
        $reference = substr($request->string('reference_no')->toString(), 0, 20);

        return Inertia::render('Public/TrackDocument', [
            'reference' => preg_match('/^REQ-[0-9]{4}-[0-9]{6}$/', $reference) ? $reference : '',
        ]);
    }

    public function show(TrackDocumentRequest $request): Response
    {
        $reference = $request->validated('reference_no');
        $documentRequest = DocumentRequest::query()
            ->where('reference_no', $reference)
            ->with([
                'items.documentType:id,name',
                'documentType:id,name',
                'payments:id,document_request_id,total_amount,status,submitted_at,payment_method,reference_number,denial_reason,receipt_path',
                'clearances:id,document_request_id,overall_status',
                'clearances.steps:id,clearance_id,office_code,label,sequence,status,remarks',
                'requirements:id,document_request_id,requirement_key,label,status,notes',
                'claimSlip:id,document_request_id,claim_number,claim_date,state,release_channel',
                'feedback:id,document_request_id,rating,service_rating,comments,suggestions,submitted_at',
            ])
            ->first();

        return Inertia::render('Public/TrackResult', [
            'reference_no' => $reference,
            'notFound' => $documentRequest === null,
            'result' => $documentRequest ? $this->trackingPayload($documentRequest) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function trackingPayload(DocumentRequest $documentRequest): array
    {
        /** @var Payment|null $payment */
        $payment = $documentRequest->payments->sortByDesc('submitted_at')->first();
        /** @var Clearance|null $clearance */
        $clearance = $documentRequest->clearances->sortByDesc('id')->first();
        /** @var ClaimSlip|null $claimSlip */
        $claimSlip = $documentRequest->claimSlip;

        $payload = [
            'reference_no' => $documentRequest->reference_no,
            'status' => $documentRequest->status,
            'processing_stage' => $documentRequest->processing_stage,
            'workflow_stage' => $documentRequest->workflow_stage,
            'stage_label' => $this->stageLabel($documentRequest),
            'stage_description' => $this->stageDescription($documentRequest, $clearance),
            'timeline' => $this->timelinePayload($documentRequest),
            'submitted_at' => $documentRequest->created_at?->toDateString(),
            'expected_release_on' => $this->formatDate($documentRequest->expected_release_on),
            'fulfillment_method' => $documentRequest->fulfillment_method,
            'delivery_provider' => $documentRequest->delivery_provider,
            'courier_name' => $documentRequest->courier_name,
            'courier_tracking_number' => $documentRequest->courier_tracking_number,
            'release_channel' => $documentRequest->release_channel
                ? config('policy.release_channels.'.$documentRequest->release_channel, $documentRequest->release_channel)
                : null,
            'next_step' => $this->nextStep($documentRequest, $clearance, $claimSlip),
            'documents' => $this->documentsPayload($documentRequest),
            'payment' => $payment ? [
                'status' => $payment->status,
                'total_amount' => $this->formatCurrency($payment->total_amount),
                'payment_method' => $payment->payment_method,
                'reference_number' => $payment->reference_number,
                'denial_reason' => $payment->status === 'denied' ? $payment->denial_reason : null,
            ] : null,
            'quote' => [
                'is_locked' => $documentRequest->quote_total !== null,
                'document_subtotal' => $this->formatCurrency($documentRequest->items->sum('line_total')),
                'shipping_fee' => $this->formatCurrency($documentRequest->shipping_fee),
                'grand_total' => $this->formatCurrency(
                    $documentRequest->quote_total
                        ?? ((float) $documentRequest->items->sum('line_total') + (float) $documentRequest->shipping_fee),
                ),
            ],
            'payment_methods' => PublicRequestOptions::PAYMENT_METHODS,
            'payment_profile' => $this->paymentProfilePayload($documentRequest),
            'feedback_submitted' => $documentRequest->feedback !== null,
            'clearance' => $clearance ? [
                'overall_status' => $clearance->overall_status,
                'steps' => $clearance->steps->map(fn (ClearanceStep $step): array => [
                    'label' => $step->label,
                    'status' => $step->status,
                    'remarks' => $step->status === 'needs_action' ? $step->remarks : null,
                ])->values(),
            ] : null,
            'action_requirements' => $documentRequest->requirements
                ->where('status', 'rejected')
                ->map(fn (RequestRequirement $requirement): array => [
                    'id' => $requirement->id,
                    'label' => $requirement->label,
                    'notes' => $requirement->notes,
                ])->values(),
            'payment_open' => $documentRequest->workflow_stage === 'awaiting_payment',
            'registrar_contact' => [
                'phone' => '09515388282',
                'email' => 'registrarsoffice@svc.edu.ph',
            ],
        ];

        if ($documentRequest->status === 'denied') {
            $payload['denial_reason'] = $documentRequest->denial_reason;
        }

        if ($claimSlip && in_array($claimSlip->state, ['ready', 'released'], true)) {
            $payload['claim_slip'] = [
                'claim_number' => $claimSlip->claim_number,
                'claim_date' => $this->formatDate($claimSlip->claim_date),
                'release_channel' => config('policy.release_channels.'.$claimSlip->release_channel, $claimSlip->release_channel),
            ];
        }

        return $payload;
    }

    private function stageLabel(DocumentRequest $documentRequest): string
    {
        if ($documentRequest->intake_mode === 'public' && $documentRequest->workflow_stage !== 'submitted') {
            return [
                'registrar_review' => 'Registrar review',
                'clearance' => 'Clearance',
                'awaiting_payment' => 'Payment',
                'payment_review' => 'Payment validation',
                'processing' => 'Processing',
                'ready' => 'Ready for release',
                'released' => 'Released',
            ][$documentRequest->workflow_stage] ?? 'Submitted';
        }

        if ($documentRequest->processing_stage === 'ready_for_pickup') {
            return 'Ready for pickup';
        }

        if ($documentRequest->processing_stage === 'released') {
            return 'Released';
        }

        if ($documentRequest->processing_stage === 'processing') {
            return 'Processing';
        }

        return 'Staff review';
    }

    private function stageDescription(DocumentRequest $documentRequest, ?Clearance $clearance): string
    {
        if ($documentRequest->intake_mode === 'public' && $documentRequest->workflow_stage !== 'submitted') {
            return [
                'registrar_review' => 'The registrar is checking document details and preparing the final amount.',
                'clearance' => 'Required offices sign in parallel; Accounting validates last. Any correction will appear below.',
                'awaiting_payment' => 'Clearance is complete. Upload the payment receipt using your private access code.',
                'payment_review' => 'Accounting is validating the submitted payment receipt.',
                'processing' => 'The registrar is preparing the requested documents.',
                'ready' => 'The documents are ready. Bring a valid ID and the claim slip.',
                'released' => 'The request has been released.',
            ][$documentRequest->workflow_stage] ?? 'The request was received.';
        }

        if ($documentRequest->status === 'denied') {
            return 'The request was reviewed and could not be approved as submitted.';
        }

        if ($documentRequest->processing_stage === 'ready_for_pickup') {
            return 'Bring your reference number and follow the registrar pickup instructions.';
        }

        if ($documentRequest->processing_stage === 'released') {
            return 'The request has been released. Keep the reference number for your records.';
        }

        if ($clearance && $clearance->overall_status !== 'completed') {
            return 'School staff are completing the required clearance steps internally.';
        }

        if ($documentRequest->processing_stage === 'processing') {
            return 'Registrar staff are preparing the requested document.';
        }

        return 'Office staff are checking the submitted requirements and payment receipt.';
    }

    /**
     * @return list<array{key: string, label: string, description: string, state: string}>
     */
    private function timelinePayload(DocumentRequest $documentRequest): array
    {
        if ($documentRequest->intake_mode === 'public' && $documentRequest->workflow_stage !== 'submitted') {
            $stages = [
                ['key' => 'submitted', 'label' => 'Submitted', 'description' => 'Request and initial requirements received.'],
                ['key' => 'registrar_review', 'label' => 'Registrar review', 'description' => 'Items, pages, authentication, and delivery are quoted.'],
                ['key' => 'clearance', 'label' => 'Clearance', 'description' => 'Required offices sign in parallel; Accounting validates last.'],
                ['key' => 'awaiting_payment', 'label' => 'Payment', 'description' => 'Receipt upload opens after clearance.'],
                ['key' => 'processing', 'label' => 'Process', 'description' => 'Registrar prepares the records.'],
                ['key' => 'ready', 'label' => 'Ready', 'description' => 'Claim slip and release date are available.'],
                ['key' => 'released', 'label' => 'Released', 'description' => 'Documents have been released.'],
            ];
            $order = ['submitted', 'registrar_review', 'clearance', 'awaiting_payment', 'payment_review', 'processing', 'ready', 'released'];
            $current = array_search($documentRequest->workflow_stage, $order, true);
            $current = $current === false ? 0 : $current;

            return array_map(function (array $stage) use ($order, $current): array {
                $index = array_search($stage['key'], $order, true);

                if ($stage['key'] === 'awaiting_payment' && $current === 4) {
                    return [...$stage, 'state' => 'active'];
                }

                return [...$stage, 'state' => $index < $current ? 'complete' : ($index === $current ? 'active' : 'upcoming')];
            }, $stages);
        }

        $stages = [
            [
                'key' => 'submitted',
                'label' => 'Submitted',
                'description' => 'The request package was received.',
            ],
            [
                'key' => 'staff_review',
                'label' => 'Staff review',
                'description' => 'Staff check requirements, receipt, and request details.',
            ],
            [
                'key' => 'processing',
                'label' => 'Processing',
                'description' => 'The requested document is being prepared.',
            ],
            [
                'key' => 'ready_for_pickup',
                'label' => 'Ready for pickup',
                'description' => 'Pickup instructions are available when the document is ready.',
            ],
            [
                'key' => 'released',
                'label' => 'Released',
                'description' => 'The document has been released.',
            ],
        ];

        $currentIndex = match ($documentRequest->processing_stage) {
            'processing' => 2,
            'ready_for_pickup' => 3,
            'released' => 4,
            default => 1,
        };

        if ($documentRequest->status === 'denied') {
            $currentIndex = 1;
        }

        return array_map(function (array $stage, int $index) use ($currentIndex, $documentRequest): array {
            $state = 'upcoming';

            if ($index < $currentIndex) {
                $state = 'complete';
            } elseif ($index === $currentIndex) {
                $state = $documentRequest->status === 'denied' && $stage['key'] === 'staff_review'
                    ? 'denied'
                    : 'active';
            }

            return [
                ...$stage,
                'state' => $state,
            ];
        }, $stages, array_keys($stages));
    }

    private function nextStep(DocumentRequest $documentRequest, ?Clearance $clearance, ?ClaimSlip $claimSlip): string
    {
        if ($documentRequest->intake_mode === 'public' && $documentRequest->workflow_stage !== 'submitted') {
            return [
                'registrar_review' => 'No payment is due yet. Keep your reference number and private access code.',
                'clearance' => 'Monitor each clearance office here. Upload a correction only if an office requests one.',
                'awaiting_payment' => 'Enter your private access code and upload the payment receipt below.',
                'payment_review' => 'Wait for accounting validation. A rejected receipt can be replaced on this page.',
                'processing' => 'No action is needed while the registrar prepares the documents.',
                'ready' => $documentRequest->fulfillment_method === 'delivery'
                    ? 'Your documents are ready for courier delivery. Keep the tracking number and stay available at the delivery address.'
                    : 'Download the claim slip and bring it with one valid ID.',
                'released' => 'Keep the reference number for your records.',
            ][$documentRequest->workflow_stage] ?? 'Keep checking this page for updates.';
        }

        if ($documentRequest->status === 'denied') {
            return 'This request was denied. Review the reason shown here and contact the registrar if you need help resubmitting.';
        }

        if ($documentRequest->processing_stage === 'released') {
            return 'This request has been released. Keep the reference number for your records.';
        }

        if ($claimSlip && $claimSlip->state === 'ready') {
            return 'Your document is ready for pickup. Bring this reference number and any claim instructions from the registrar.';
        }

        if ($clearance && $clearance->overall_status !== 'completed') {
            return 'Department clearance is being handled by school staff. No separate student account or clearance upload is needed.';
        }

        if ($documentRequest->processing_stage === 'processing') {
            return 'Your document is being processed by the registrar. Keep checking this page for pickup updates.';
        }

        return 'Your request package is under staff review. Keep this reference number and check this page for updates.';
    }

    /**
     * @return list<array{name: string|null, copies: int, line_total: string}>
     */
    private function documentsPayload(DocumentRequest $request): array
    {
        if ($request->items->isNotEmpty()) {
            $documents = [];

            foreach ($request->items as $item) {
                /** @var DocumentType|null $documentType */
                $documentType = $item->documentType;

                $documents[] = [
                    'name' => $documentType?->name,
                    'copies' => (int) $item->copies,
                    'base_amount' => $this->formatCurrency($item->base_amount),
                    'authentication_amount' => $this->formatCurrency($item->authentication_amount),
                    'documentary_stamp_amount' => $this->formatCurrency($item->documentary_stamp_amount),
                    'line_total' => $this->formatCurrency($item->line_total),
                ];
            }

            return $documents;
        }

        /** @var DocumentType|null $documentType */
        $documentType = $request->documentType;

        return [[
            'name' => $documentType?->name,
            'copies' => (int) ($request->quantity ?? 1),
            'base_amount' => $this->formatCurrency($request->fee_snapshot),
            'authentication_amount' => '0.00',
            'documentary_stamp_amount' => '0.00',
            'line_total' => $this->formatCurrency($request->fee_snapshot ?? 0),
        ]];
    }

    private function formatCurrency(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function formatDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return is_string($value) ? $value : null;
    }

    /** @return array<string, mixed>|null */
    private function paymentProfilePayload(DocumentRequest $request): ?array
    {
        if (! $request->quote_total && ! $request->payments->contains(fn (Payment $payment): bool => $payment->receipt_path !== null)) {
            return null;
        }

        $profile = PaymentProfile::active();

        return $profile ? [
            'bank_name' => $profile->bank_name,
            'account_name' => $profile->account_name,
            'account_number' => $profile->account_number,
            'instructions' => $profile->instructions,
            'qr_url' => $profile->qr_path ? route('public.files.payment-qr', $profile->id) : null,
        ] : null;
    }
}
