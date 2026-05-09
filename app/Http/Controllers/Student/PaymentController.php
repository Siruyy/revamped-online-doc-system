<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UploadPaymentRequest;
use App\Models\Payment;
use App\Models\PaymentProfile;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $payments = Payment::query()
            ->with([
                'documentRequest:id,reference_no,document_type_id,fee_snapshot,status',
                'documentRequest.documentType:id,name',
                'documentRequest.items.documentType:id,name',
            ])
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'pending_approval', 'denied'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $paymentProfiles = PaymentProfile::activeProfiles()
            ->map(fn ($p) => [
                'id'             => $p->id,
                'bank_name'      => $p->bank_name,
                'account_name'   => $p->account_name,
                'account_number' => $p->account_number,
                'qr_url'         => $p->qr_path ? route('files.payment-qr', $p->id) : null,
                'instructions'   => $p->instructions,
            ]);

        return Inertia::render('Student/Payments/Index', [
            'payments'        => $payments,
            'paymentProfiles' => $paymentProfiles,
            // Legacy single-profile key kept for any components still reading it
            'paymentProfile'  => $paymentProfiles->first(),
        ]);
    }

    public function upload(UploadPaymentRequest $request, Payment $payment, PaymentService $paymentService): RedirectResponse
    {
        $this->authorize('upload', $payment);
        abort_unless($payment->user_id === $request->user()->id, 403);

        $validated = $request->validated();

        $paymentService->uploadReceipt(
            $payment,
            $validated['receipt'],
            $validated['payment_method'],
            $validated['reference_number'] ?? null
        );

        return back()->with('status', 'Payment receipt uploaded successfully.');
    }
}
