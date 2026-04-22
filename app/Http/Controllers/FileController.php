<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function paymentReceipt(Payment $payment): StreamedResponse
    {
        $this->authorize('view', $payment);

        abort_if(
            empty($payment->receipt_path)
                || ! str_starts_with($payment->receipt_path, "payment-receipts/{$payment->user_id}/")
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
                || ! str_starts_with($clearance->pdf_path, 'pdfs/clearance/')
                || ! Storage::disk('local')->exists($clearance->pdf_path),
            404
        );

        return Storage::disk('local')->download($clearance->pdf_path);
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
