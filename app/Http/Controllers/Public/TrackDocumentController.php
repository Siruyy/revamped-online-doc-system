<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\TrackDocumentRequest;
use App\Models\ClaimSlip;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use App\Models\DocumentType;
use App\Models\Payment;
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
                'payments:id,document_request_id,total_amount,status,submitted_at',
                'clearances:id,document_request_id,overall_status',
                'claimSlip:id,document_request_id,claim_number,claim_date,state',
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
            'stage_label' => $this->stageLabel($documentRequest),
            'stage_description' => $this->stageDescription($documentRequest, $clearance),
            'timeline' => $this->timelinePayload($documentRequest),
            'submitted_at' => $documentRequest->created_at?->toDateString(),
            'expected_release_on' => $this->formatDate($documentRequest->expected_release_on),
            'next_step' => $this->nextStep($documentRequest, $clearance, $claimSlip),
            'documents' => $this->documentsPayload($documentRequest),
            'payment' => $payment ? [
                'status' => $payment->status,
                'total_amount' => $this->formatCurrency($payment->total_amount),
            ] : null,
            'clearance' => $clearance ? [
                'overall_status' => $clearance->overall_status,
            ] : null,
        ];

        if ($documentRequest->status === 'denied') {
            $payload['denial_reason'] = $documentRequest->denial_reason;
        }

        if ($claimSlip && in_array($claimSlip->state, ['ready', 'released'], true)) {
            $payload['claim_slip'] = [
                'claim_number' => $claimSlip->claim_number,
                'claim_date' => $this->formatDate($claimSlip->claim_date),
            ];
        }

        return $payload;
    }

    private function stageLabel(DocumentRequest $documentRequest): string
    {
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
                if (! $item instanceof DocumentRequestItem) {
                    continue;
                }

                /** @var DocumentType|null $documentType */
                $documentType = $item->documentType;

                $documents[] = [
                    'name' => $documentType?->name,
                    'copies' => (int) $item->copies,
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
}
