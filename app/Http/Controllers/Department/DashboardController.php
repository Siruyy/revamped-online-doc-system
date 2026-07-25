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
        $isDynamicOnly = in_array($role, ['accounting', 'principal'], true);
        $columns = $isDynamicOnly
            ? [
                'label' => $role === 'principal' ? 'BEC Principal' : 'Accounting Office',
                'status' => 'status',
                'signed_at' => 'signed_at',
            ]
            : ClearanceSignatories::columns($role);
        $statusColumn = $columns['status'];
        $signedAtColumn = $columns['signed_at'];

        $pendingCount = Clearance::query()
            ->where(function ($query) use ($user, $role, $isDynamicOnly, $statusColumn) {
                $query->whereHas('steps', function ($steps) use ($user, $role) {
                    $steps->where('office_code', $role)->where('status', 'pending');

                    if ($role === 'dean' && $user->academicDepartment) {
                        $steps->where('department_code', $user->academicDepartment->code);
                    }
                });

                if (! $isDynamicOnly) {
                    $query->orWhere(fn ($legacy) => $legacy->whereDoesntHave('steps')->where($statusColumn, 'pending'));
                }
            })
            ->count();

        $signedTodayCount = Clearance::query()
            ->where(function ($query) use ($user, $role, $isDynamicOnly, $statusColumn, $signedAtColumn) {
                $query->whereHas('steps', function ($steps) use ($user, $role) {
                    $steps->where('office_code', $role)
                        ->where('status', 'cleared')
                        ->whereDate('signed_at', today());

                    if ($role === 'dean' && $user->academicDepartment) {
                        $steps->where('department_code', $user->academicDepartment->code);
                    }
                });

                if (! $isDynamicOnly) {
                    $query->orWhere(fn ($legacy) => $legacy->whereDoesntHave('steps')
                        ->where($statusColumn, 'cleared')
                        ->whereDate($signedAtColumn, today()));
                }
            })
            ->count();

        $deniedCount = Clearance::query()
            ->where(function ($query) use ($user, $role, $isDynamicOnly, $statusColumn) {
                $query->whereHas('steps', function ($steps) use ($user, $role) {
                    $steps->where('office_code', $role)->where('status', 'needs_action');

                    if ($role === 'dean' && $user->academicDepartment) {
                        $steps->where('department_code', $user->academicDepartment->code);
                    }
                });

                if (! $isDynamicOnly) {
                    $query->orWhere(fn ($legacy) => $legacy->whereDoesntHave('steps')->where($statusColumn, 'denied'));
                }
            })
            ->count();

        $pendingLatest = Clearance::query()
            ->with([
                'user:id,fullname,course,year_level,student_id',
                'documentRequest:id,reference_no,requester_name,requester_student_id,requester_course,requester_year_level',
            ])
            ->where(function ($query) use ($user, $role, $isDynamicOnly, $statusColumn) {
                $query->whereHas('steps', function ($steps) use ($user, $role) {
                    $steps->where('office_code', $role)->where('status', 'pending');

                    if ($role === 'dean' && $user->academicDepartment) {
                        $steps->where('department_code', $user->academicDepartment->code);
                    }
                });

                if (! $isDynamicOnly) {
                    $query->orWhere(fn ($legacy) => $legacy->whereDoesntHave('steps')->where($statusColumn, 'pending'));
                }
            })
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
