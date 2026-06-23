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
            'submitted_at' => $documentRequest->created_at?->toDateString(),
            'expected_release_on' => $this->formatDate($documentRequest->expected_release_on),
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
