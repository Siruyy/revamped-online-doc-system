<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\Payment;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $requestCounts = [
            'pending' => DocumentRequest::query()->where('status', 'pending')->count(),
            'approved' => DocumentRequest::query()->where('status', 'approved')->count(),
            'denied' => DocumentRequest::query()->where('status', 'denied')->count(),
            'completed' => DocumentRequest::query()->where('status', 'completed')->count(),
        ];

        $paymentCounts = [
            'pending' => Payment::query()->where('status', 'pending')->count(),
            'pending_approval' => Payment::query()->where('status', 'pending_approval')->count(),
            'approved' => Payment::query()->where('status', 'approved')->count(),
            'denied' => Payment::query()->where('status', 'denied')->count(),
        ];

        $todaySubmissions = DocumentRequest::query()
            ->whereDate('created_at', today())
            ->count();

        $pendingQueue = DocumentRequest::query()
            ->with(['user:id,fullname,course,year_level', 'documentType:id,name'])
            ->where('status', 'pending')
            ->latest()
            ->limit(10)
            ->get(['id', 'reference_no', 'user_id', 'document_type_id', 'created_at']);

        return Inertia::render('Admin/Dashboard', [
            'requestCounts' => $requestCounts,
            'paymentCounts' => $paymentCounts,
            'todaySubmissions' => $todaySubmissions,
            'pendingQueue' => $pendingQueue,
        ]);
    }
}
