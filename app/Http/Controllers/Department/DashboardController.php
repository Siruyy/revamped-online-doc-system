<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\Clearance;
use App\Support\ClearanceSignatories;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $role = $user->role;

        $columns = ClearanceSignatories::columns($role);
        $statusColumn = $columns['status'];
        $signedAtColumn = $columns['signed_at'];

        $pendingCount = Clearance::query()->where($statusColumn, 'pending')->count();
        $signedTodayCount = Clearance::query()
            ->where($statusColumn, 'cleared')
            ->whereDate($signedAtColumn, today())
            ->count();
        $deniedCount = Clearance::query()->where($statusColumn, 'denied')->count();

        $pendingLatest = Clearance::query()
            ->with([
                'user:id,fullname,course,year_level,student_id',
                'documentRequest:id,reference_no,requester_name,requester_student_id,requester_course,requester_year_level',
            ])
            ->where($statusColumn, 'pending')
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Department/Dashboard', [
            'stats' => [
                'pending' => $pendingCount,
                'signed_today' => $signedTodayCount,
                'denied' => $deniedCount,
            ],
            'pendingLatest' => $pendingLatest,
            'department' => $role,
            'currentSignatory' => $columns,
        ]);
    }
}
