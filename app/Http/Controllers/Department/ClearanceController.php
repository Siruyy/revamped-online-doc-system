<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\DenyClearanceRequest;
use App\Http\Requests\Department\SignClearanceRequest;
use App\Models\Clearance;
use App\Services\ClearanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClearanceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Clearance::class);

        $user = $request->user();
        $statusColumn = match ($user->role) {
            'teacher' => 'teacher_status',
            'dean' => 'dean_status',
            'accounting' => 'accounting_status',
            'sao' => 'sao_status',
            default => 'teacher_status',
        };

        $status = $request->string('status')->toString() ?: 'pending';

        $clearances = Clearance::query()
            ->with([
                'user:id,fullname,course,year_level,student_id',
                'documentRequest:id,reference_no,status',
            ])
            ->where($statusColumn, $status)
            ->when($request->string('course')->toString(), fn ($q, $course) => $q->whereHas('user', fn ($inner) => $inner->where('course', $course)))
            ->when($request->string('year')->toString(), fn ($q, $year) => $q->whereHas('user', fn ($inner) => $inner->where('year_level', $year)))
            ->when($request->string('search')->toString(), function ($q, $search) {
                $q->whereHas('user', function ($inner) use ($search) {
                    $inner->where('fullname', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Department/Clearances/Index', [
            'clearances' => $clearances,
            'filters' => [
                'status' => $status,
                'course' => $request->string('course')->toString(),
                'year' => $request->string('year')->toString(),
                'search' => $request->string('search')->toString(),
            ],
            'departmentStatusColumn' => $statusColumn,
        ]);
    }

    public function show(Request $request, Clearance $clearance): Response
    {
        $this->authorize('view', $clearance);

        $clearance->load([
            'user:id,fullname,email,course,year_level,student_id,contact_number',
            'documentRequest:id,reference_no,status,processing_stage,purpose',
            'teacherSigner:id,fullname',
            'deanSigner:id,fullname',
            'accountingSigner:id,fullname',
            'saoSigner:id,fullname',
        ]);

        return Inertia::render('Department/Clearances/Show', [
            'clearance' => $clearance,
            'department' => $request->user()->role,
        ]);
    }

    public function sign(SignClearanceRequest $request, Clearance $clearance, ClearanceService $clearanceService): RedirectResponse
    {
        $department = $request->user()->role;
        $this->authorize('signOwnDepartment', $clearance);

        try {
            $clearanceService->signFor(
                $clearance,
                $request->user(),
                $department,
                $request->validated('remarks')
            );
        } catch (\Throwable $exception) {
            return back()->withErrors(['sign' => $exception->getMessage()]);
        }

        return back()->with('status', 'Clearance marked as cleared.');
    }

    public function deny(DenyClearanceRequest $request, Clearance $clearance, ClearanceService $clearanceService): RedirectResponse
    {
        $department = $request->user()->role;
        $this->authorize('rejectDepartment', $clearance);

        try {
            $clearanceService->denyFor(
                $clearance,
                $request->user(),
                $department,
                $request->validated('remarks')
            );
        } catch (\Throwable $exception) {
            return back()->withErrors(['deny' => $exception->getMessage()]);
        }

        return back()->with('status', 'Clearance denied for your department.');
    }
}
