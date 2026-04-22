<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();
        $status = $request->string('status')->toString();
        $course = $request->string('course')->toString();

        $requestQuery = DocumentRequest::query()
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($course, fn ($query) => $query->whereHas('user', fn ($q) => $q->where('course', $course)));

        $paymentQuery = Payment::query()
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->when($course, fn ($query) => $query->whereHas('user', fn ($q) => $q->where('course', $course)));

        $summary = [
            'requests_total' => (clone $requestQuery)->count(),
            'requests_completed' => (clone $requestQuery)->where('status', 'completed')->count(),
            'requests_denied' => (clone $requestQuery)->where('status', 'denied')->count(),
            'payments_total' => (clone $paymentQuery)->count(),
            'payments_approved' => (clone $paymentQuery)->where('status', 'approved')->count(),
            'payments_denied' => (clone $paymentQuery)->where('status', 'denied')->count(),
        ];

        return Inertia::render('Admin/Reports', [
            'summary' => $summary,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'status' => $status,
                'course' => $course,
            ],
        ]);
    }
}
