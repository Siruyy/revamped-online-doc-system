<?php

namespace App\Services;

use App\Events\ClearanceCreated;
use App\Events\PaymentApproved;
use App\Events\PaymentDenied;
use App\Events\PaymentSubmitted;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\WorkflowStatusNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Upload a payment receipt for an existing payment.
     * The associated request must already be admin-approved (policy-initial gate).
     */
    public function uploadReceipt(Payment $payment, UploadedFile $receipt, string $paymentMethod, ?string $referenceNumber): Payment
    {
        if (in_array($payment->status, ['approved', 'pending_approval'], true)) {
            throw new \RuntimeException('This payment cannot be updated in its current review state.');
        }

        // Policy-initial: student may only upload receipt after admin approves the request.
        $documentRequest = $payment->documentRequest;

        if ($documentRequest && ! in_array($documentRequest->status, ['approved', 'completed'], true)) {
            throw new \RuntimeException('Payment receipt can only be uploaded after your request has been approved by the admin.');
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

        Notification::send(
            User::query()->whereIn('role', ['admin', 'superadmin'])->where('status', 'active')->get(),
            new WorkflowStatusNotification([
                'type' => 'payment_submitted',
                'title' => 'Payment submitted',
                'message' => "{$payment->user->fullname} uploaded a payment receipt.",
                'payment_id' => $payment->id,
                'student_id' => $payment->user_id,
            ]),
        );

        return $payment->refresh();
    }

    /**
     * Admin approves a payment receipt. After approval, clearance routing begins
     * for any item types that require departmental clearance.
     */
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

        // After payment approval, start clearance routing for document types that require it.
        if ($payment->document_request_id) {
            $docRequest = $payment->documentRequest;

            if ($docRequest) {
                $this->initiateClearanceIfNeeded($docRequest, $payment);
            }
        }

        ActivityLogger::log(
            'payment_approved',
            "Admin {$admin->email} approved payment #{$payment->id}.",
            $admin,
            $payment->user,
            ['payment_id' => $payment->id]
        );

        PaymentApproved::dispatch($payment->id, $payment->user_id, $admin->id);

        $payment->user->notify(new WorkflowStatusNotification([
            'type' => 'payment_approved',
            'title' => 'Payment approved',
            'message' => 'Your payment receipt was approved.',
            'payment_id' => $payment->id,
        ]));

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

        PaymentDenied::dispatch($payment->id, $payment->user_id, $admin->id, $reason);

        $payment->user->notify(new WorkflowStatusNotification([
            'type' => 'payment_denied',
            'title' => 'Payment denied',
            'message' => 'Your payment receipt was denied.',
            'payment_id' => $payment->id,
            'reason' => $reason,
        ]));

        return $payment->refresh();
    }

    /**
     * Create clearance records for all request items that require departmental sign-off.
     * For multi-item requests, checks each item's document type; for legacy single-type
     * requests, checks the parent's documentType directly.
     */
    protected function initiateClearanceIfNeeded(
        DocumentRequest $docRequest,
        Payment $payment
    ): void {
        // Check if any item requires clearance.
        $needsClearance = false;

        $items = $docRequest->items()->with('documentType')->get();

        if ($items->isNotEmpty()) {
            $needsClearance = $items->contains(
                fn ($item) => $item->documentType?->requiresClearance() ?? false
            );
        } else {
            $needsClearance = $docRequest->documentType?->requiresClearance() ?? true;
        }

        if (! $needsClearance) {
            return;
        }

        $clearance = Clearance::query()->firstOrCreate(
            [
                'user_id' => $payment->user_id,
                'document_request_id' => $payment->document_request_id,
            ],
            [
                'overall_status' => 'in_progress',
            ]
        );

        if ($clearance->wasRecentlyCreated) {
            ClearanceCreated::dispatch(
                $clearance->id,
                $clearance->user_id,
                $clearance->document_request_id
            );

            Notification::send(
                User::query()->whereIn('role', ['teacher', 'dean', 'accounting', 'sao'])->where('status', 'active')->get(),
                new WorkflowStatusNotification([
                    'type' => 'clearance_created',
                    'title' => 'Clearance ready for review',
                    'message' => 'A student clearance is ready for department review.',
                    'clearance_id' => $clearance->id,
                    'document_request_id' => $clearance->document_request_id,
                    'student_id' => $clearance->user_id,
                ]),
            );
        }
    }
}
