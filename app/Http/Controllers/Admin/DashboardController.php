<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClaimSlip;
use App\Models\DocumentRequest;
use App\Models\Payment;
use App\Models\RequestRequirement;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $requestStatusCounts = DocumentRequest::query()
            ->selectRaw('status, count(*) as aggregate')
            ->whereIn('status', ['pending', 'approved', 'denied', 'completed'])
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $requestCounts = [
            'pending' => $this->countFor($requestStatusCounts, 'pending'),
            'approved' => $this->countFor($requestStatusCounts, 'approved'),
            'denied' => $this->countFor($requestStatusCounts, 'denied'),
            'completed' => $this->countFor($requestStatusCounts, 'completed'),
        ];

        $paymentStatusCounts = Payment::query()
            ->selectRaw('status, count(*) as aggregate')
            ->whereIn('status', ['pending', 'pending_approval', 'approved', 'denied'])
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $paymentCounts = [
            'pending' => $this->countFor($paymentStatusCounts, 'pending'),
            'pending_approval' => $this->countFor($paymentStatusCounts, 'pending_approval'),
            'approved' => $this->countFor($paymentStatusCounts, 'approved'),
            'denied' => $this->countFor($paymentStatusCounts, 'denied'),
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

    /**
     * @param  Collection<string, int|string>  $counts
     */
    private function countFor(Collection $counts, string $status): int
    {
        return (int) ($counts[$status] ?? 0);
    }
}
