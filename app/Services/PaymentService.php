<?php

namespace App\Services;

use App\Events\PaymentApproved;
use App\Events\PaymentSubmitted;
use App\Models\Clearance;
use App\Models\Payment;
use App\Models\User;
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

    public function approve(Payment $payment, User $admin): Payment
    {
        if ($payment->status !== 'pending_approval') {
            throw new \RuntimeException('Only submitted payments can be approved.');
        }

        $payment->update([
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'denial_reason' => null,
        ]);

        if ($payment->document_request_id) {
            Clearance::query()->firstOrCreate(
                [
                    'user_id' => $payment->user_id,
                    'document_request_id' => $payment->document_request_id,
                ],
                [
                    'overall_status' => 'in_progress',
                ]
            );
        }

        ActivityLogger::log(
            'payment_approved',
            "Admin {$admin->email} approved payment #{$payment->id}.",
            $admin,
            $payment->user,
            ['payment_id' => $payment->id]
        );

        PaymentApproved::dispatch($payment->id, $payment->user_id, $admin->id);

        return $payment->refresh();
    }

    public function deny(Payment $payment, User $admin, string $reason): Payment
    {
        if ($payment->status !== 'pending_approval') {
            throw new \RuntimeException('Only submitted payments can be denied.');
        }

        $payment->update([
            'status' => 'denied',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'denial_reason' => $reason,
        ]);

        ActivityLogger::log(
            'payment_denied',
            "Admin {$admin->email} denied payment #{$payment->id}.",
            $admin,
            $payment->user,
            ['payment_id' => $payment->id, 'reason' => $reason]
        );

        return $payment->refresh();
    }
}
