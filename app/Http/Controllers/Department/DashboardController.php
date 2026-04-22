<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\Clearance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $role = $user->role;

        [$statusColumn, $signedAtColumn] = match ($role) {
            'teacher' => ['teacher_status', 'teacher_signed_at'],
            'dean' => ['dean_status', 'dean_signed_at'],
            'accounting' => ['accounting_status', 'accounting_signed_at'],
            'sao' => ['sao_status', 'sao_signed_at'],
            default => ['teacher_status', 'teacher_signed_at'],
        };

        $pendingCount = Clearance::query()->where($statusColumn, 'pending')->count();
        $signedTodayCount = Clearance::query()
            ->where($statusColumn, 'cleared')
            ->whereDate($signedAtColumn, today())
            ->count();
        $deniedCount = Clearance::query()->where($statusColumn, 'denied')->count();

        $pendingLatest = Clearance::query()
            ->with([
                'user:id,fullname,course,year_level,student_id',
                'documentRequest:id,reference_no',
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
        ]);
    }
}
