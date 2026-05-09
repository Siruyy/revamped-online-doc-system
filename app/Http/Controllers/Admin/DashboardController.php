<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClaimSlip;
use App\Models\DocumentRequest;
use App\Models\Payment;
use App\Models\RequestRequirement;
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

        $overdueCount = DocumentRequest::query()
            ->whereIn('status', ['approved'])
            ->whereNotNull('expected_release_on')
            ->whereDate('expected_release_on', '<', today())
            ->count();

        $dueTodayCount = DocumentRequest::query()
            ->whereIn('status', ['approved'])
            ->whereDate('expected_release_on', today())
            ->count();

        $missingRequirementsCount = RequestRequirement::query()
            ->where('status', 'missing')
            ->whereHas('documentRequest', fn ($q) => $q->whereIn('status', ['pending', 'approved']))
            ->count();

        $readyForPickup = DocumentRequest::query()
            ->where('processing_stage', 'ready_for_pickup')
            ->count();

        $pendingQueue = DocumentRequest::query()
            ->with(['user:id,fullname,course,year_level,academic_status', 'documentType:id,name,category,processing_days', 'requirements:id,document_request_id,status'])
            ->where('status', 'pending')
            ->latest()
            ->limit(10)
            ->get();

        $overdueRequests = DocumentRequest::query()
            ->with(['user:id,fullname,course', 'documentType:id,name'])
            ->where('status', 'approved')
            ->whereNotNull('expected_release_on')
            ->whereDate('expected_release_on', '<', today())
            ->orderBy('expected_release_on')
            ->limit(8)
            ->get();

        $claimToday = ClaimSlip::query()
            ->with(['user:id,fullname', 'documentRequest:id,reference_no,document_type_id', 'documentRequest.documentType:id,name'])
            ->where('state', 'ready')
            ->whereDate('claim_date', today())
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'requestCounts' => $requestCounts,
            'paymentCounts' => $paymentCounts,
            'todaySubmissions' => $todaySubmissions,
            'overdueCount' => $overdueCount,
            'dueTodayCount' => $dueTodayCount,
            'missingRequirementsCount' => $missingRequirementsCount,
            'readyForPickup' => $readyForPickup,
            'pendingQueue' => $pendingQueue,
            'overdueRequests' => $overdueRequests,
            'claimToday' => $claimToday,
        ]);
    }
}
