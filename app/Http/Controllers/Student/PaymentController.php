<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UploadPaymentRequest;
use App\Models\Payment;
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
            ->with('documentRequest:id,reference_no,document_type_id,status')
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'pending_approval', 'denied'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Student/Payments/Index', [
            'payments' => $payments,
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
