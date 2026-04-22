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

        return Inertia::render('SuperAdmin/Dashboard', [
            'userCountsByRole' => $userCountsByRole,
            'userCountsByStatus' => $userCountsByStatus,
            'pendingRegistrations' => $pendingRegistrations,
            'requestCounts' => $requestCounts,
            'paymentCounts' => $paymentCounts,
            'clearanceCounts' => $clearanceCounts,
            'recentActivity' => $recentActivity,
        ]);
    }
}
