<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\Payment;
use App\Models\PaymentProfile;
use App\Models\RequestRequirement;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function paymentReceipt(Payment $payment): StreamedResponse
    {
        $this->authorize('view', $payment);

        $expectedPrefix = $payment->user_id !== null
            ? "payment-receipts/{$payment->user_id}/"
            : "payment-receipts/public/{$payment->document_request_id}/";

        abort_if(
            empty($payment->receipt_path)
                || ! str_starts_with($payment->receipt_path, $expectedPrefix)
                || str_contains($payment->receipt_path, '..')
                || ! Storage::disk('local')->exists($payment->receipt_path),
            404
        );

        return Storage::disk('local')->download($payment->receipt_path);
    }

    public function clearancePdf(Clearance $clearance): StreamedResponse
    {
        $this->authorize('downloadPdf', $clearance);

        abort_if(
            empty($clearance->pdf_path)
                || ! str_starts_with($clearance->pdf_path, "pdfs/clearance/{$clearance->user_id}/")
                || ! Storage::disk('local')->exists($clearance->pdf_path),
            404
        );

        return Storage::disk('local')->download($clearance->pdf_path);
    }

    public function paymentQr(PaymentProfile $paymentProfile): StreamedResponse
    {
        $this->authorize('viewQr', $paymentProfile);

        abort_if(
            empty($paymentProfile->qr_path)
                || ! Storage::disk('local')->exists($paymentProfile->qr_path),
            404
        );

        return Storage::disk('local')->download($paymentProfile->qr_path);
    }

    public function requestRequirement(RequestRequirement $requirement): StreamedResponse
    {
        $this->authorize('view', $requirement);

        $requirement->loadMissing('documentRequest');
        /** @var DocumentRequest|null $documentRequest */
        $documentRequest = $requirement->documentRequest;
        abort_unless($documentRequest !== null, 404);

        $expectedPrefix = $documentRequest->user_id !== null
            ? "request-requirements/{$documentRequest->user_id}/{$documentRequest->id}/"
            : "request-requirements/public/{$documentRequest->id}/";

        abort_if(
            empty($requirement->file_path)
                || ! str_starts_with($requirement->file_path, $expectedPrefix)
                || str_contains($requirement->file_path, '..')
                || ! Storage::disk('local')->exists($requirement->file_path),
            404
        );

        return Storage::disk('local')->download($requirement->file_path);
    }

    public function publicPaymentQr(PaymentProfile $paymentProfile): StreamedResponse
    {
        abort_unless($paymentProfile->is_active, 404);

        abort_if(
            empty($paymentProfile->qr_path)
                || ! str_starts_with($paymentProfile->qr_path, 'payment-qr/')
                || str_contains($paymentProfile->qr_path, '..')
                || ! Storage::disk('local')->exists($paymentProfile->qr_path),
            404
        );

        return Storage::disk('local')->response($paymentProfile->qr_path);
    }

    public function clearanceSupportingFile(Clearance $clearance): StreamedResponse
    {
        $this->authorize('view', $clearance);

        abort_if(
            empty($clearance->uploaded_file_path)
                || ! str_starts_with($clearance->uploaded_file_path, "clearance-files/{$clearance->user_id}/")
                || ! Storage::disk('local')->exists($clearance->uploaded_file_path),
            404
        );

        return Storage::disk('local')->download($clearance->uploaded_file_path);
    }
}
