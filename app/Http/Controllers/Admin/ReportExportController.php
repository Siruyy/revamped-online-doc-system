<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\User;
use App\Services\CsvExportService;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function requests(Request $request, CsvExportService $exports): StreamedResponse
    {
        $this->authorize('viewAny', DocumentRequest::class);

        $query = $this->requestQuery($request)->with(['user:id,fullname,email,course', 'documentType:id,name'])->orderBy('id');

        return $exports->stream('requests-export.csv', $query, [
            'ID', 'Reference', 'Student', 'Email', 'Course', 'Document Type', 'Status', 'Stage', 'Fee', 'Created At',
        ], function (DocumentRequest $documentRequest): array {
            $user = $documentRequest->user instanceof User ? $documentRequest->user : null;
            $documentType = $documentRequest->documentType instanceof DocumentType ? $documentRequest->documentType : null;

            return [
                $documentRequest->id,
                $documentRequest->reference_no,
                $user?->fullname,
                $user?->email,
                $user?->course,
                $documentType?->name,
                $documentRequest->status,
                $documentRequest->processing_stage,
                $documentRequest->fee_snapshot,
                $this->formatDate($documentRequest->created_at),
            ];
        });
    }

    public function payments(Request $request, CsvExportService $exports): StreamedResponse
    {
        $this->authorize('viewAny', Payment::class);

        $query = $this->paymentQuery($request)->with(['user:id,fullname,email,course', 'documentRequest:id,reference_no'])->orderBy('id');

        return $exports->stream('payments-export.csv', $query, [
            'ID', 'Reference', 'Student', 'Email', 'Course', 'Amount', 'Method', 'Status', 'Submitted At', 'Approved At',
        ], function (Payment $payment): array {
            $user = $payment->user instanceof User ? $payment->user : null;
            $documentRequest = $payment->documentRequest instanceof DocumentRequest ? $payment->documentRequest : null;

            return [
                $payment->id,
                $documentRequest?->reference_no,
                $user?->fullname,
                $user?->email,
                $user?->course,
                $payment->total_amount,
                $payment->payment_method,
                $payment->status,
                $this->formatDate($payment->submitted_at),
                $this->formatDate($payment->approved_at),
            ];
        });
    }

    /**
     * @return Builder<DocumentRequest>
     */
    private function requestQuery(Request $request): Builder
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();

        return DocumentRequest::query()
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('course')->toString(), fn ($query, $course) => $query->whereHas('user', fn ($q) => $q->where('course', $course)));
    }

    /**
     * @return Builder<Payment>
     */
    private function paymentQuery(Request $request): Builder
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();

        return Payment::query()
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($request->string('course')->toString(), fn ($query, $course) => $query->whereHas('user', fn ($q) => $q->where('course', $course)));
    }

    private function formatDate(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d H:i:s') : null;
    }
}
