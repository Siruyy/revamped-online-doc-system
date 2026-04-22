<?php

namespace App\Services;

use App\Events\PaymentSubmitted;
use App\Models\Payment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentService
{
    public function uploadReceipt(Payment $payment, UploadedFile $receipt, string $paymentMethod, ?string $referenceNumber): Payment
    {
        if (in_array($payment->status, ['approved', 'pending_approval'], true)) {
            throw new \RuntimeException('This payment cannot be updated in its current review state.');
        }

        $extension = strtolower($receipt->getClientOriginalExtension());
        $path = "payment-receipts/{$payment->user_id}/".Str::uuid().".{$extension}";
        Storage::disk('local')->put($path, $receipt->getContent());

        $payment->update([
            'receipt_path' => $path,
            'payment_method' => $paymentMethod,
            'reference_number' => $referenceNumber,
            'status' => 'pending_approval',
            'submitted_at' => now(),
            'denial_reason' => null,
        ]);

        ActivityLogger::log(
            'payment_submitted',
            "User {$payment->user->email} uploaded payment receipt.",
            $payment->user,
            $payment->user,
            ['payment_id' => $payment->id]
        );

        PaymentSubmitted::dispatch($payment->id, $payment->user_id);

        return $payment->refresh();
    }
}
