<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->with([
                'user:id,fullname,course,year_level,student_id',
                'documentRequest:id,reference_no,status,document_type_id',
                'documentRequest.documentType:id,name',
            ])
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => [
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function approve(Payment $payment, PaymentService $paymentService): RedirectResponse
    {
        $this->authorize('approve', $payment);

        try {
            $paymentService->approve($payment, request()->user());
        } catch (\Throwable $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return back()->with('status', 'Payment approved successfully.');
    }

    public function deny(Request $request, Payment $payment, PaymentService $paymentService): RedirectResponse
    {
        $this->authorize('deny', $payment);

        $validated = $request->validate([
            'denial_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $paymentService->deny($payment, $request->user(), $validated['denial_reason']);
        } catch (\Throwable $exception) {
            return back()->withErrors(['denial_reason' => $exception->getMessage()]);
        }

        return back()->with('status', 'Payment denied successfully.');
    }
}
