<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $userCountsByRole = User::query()
            ->select('role', DB::raw('count(*) as c'))
            ->groupBy('role')
            ->pluck('c', 'role')
            ->all();

        $userCountsByStatus = User::query()
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $pendingRegistrations = User::query()
            ->where('role', 'student')
            ->where('status', 'pending')
            ->count();

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

        $clearanceCounts = Clearance::query()
            ->select('overall_status', DB::raw('count(*) as c'))
            ->groupBy('overall_status')
            ->pluck('c', 'overall_status')
            ->all();

        $recentActivity = ActivityLog::query()
            ->with(['user:id,fullname,email', 'affectedUser:id,fullname,email'])
            ->latest('created_at')
            ->limit(20)
            ->get();

        $activeDashboardRequests = DocumentRequest::query()
            ->with(['user:id,fullname,course,year_level,academic_status', 'documentType:id,name,category,processing_days'])
            ->where('status', 'approved')
            ->withExists(['clearances as has_completed_clearance' => fn ($query) => $query->where('overall_status', 'completed')])
            ->where(function ($query) {
                $query->whereIn('processing_stage', ['processing', 'ready_for_pickup'])
                    ->orWhereHas('clearances', fn ($clearanceQuery) => $clearanceQuery->where('overall_status', 'completed'));
            })
            ->latest()
            ->limit(20)
            ->get();

        $clearedForProcessing = $activeDashboardRequests
            ->filter(fn (DocumentRequest $request): bool => (bool) $request->getAttribute('has_completed_clearance'))
            ->take(10)
            ->values();

        $ongoingRequests = $activeDashboardRequests
            ->filter(fn (DocumentRequest $request): bool => in_array($request->processing_stage, ['processing', 'ready_for_pickup'], true))
            ->take(10)
            ->values();

        return Inertia::render('SuperAdmin/Dashboard', [
            'userCountsByRole' => $userCountsByRole,
            'userCountsByStatus' => $userCountsByStatus,
            'pendingRegistrations' => $pendingRegistrations,
            'requestCounts' => $requestCounts,
            'paymentCounts' => $paymentCounts,
            'clearanceCounts' => $clearanceCounts,
            'clearedForProcessing' => $clearedForProcessing,
            'ongoingRequests' => $ongoingRequests,
            'recentActivity' => $recentActivity,
        ]);
    }
}
