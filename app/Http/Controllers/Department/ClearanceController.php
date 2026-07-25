<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\DenyClearanceRequest;
use App\Http\Requests\Department\SignClearanceRequest;
use App\Models\Clearance;
use App\Services\ClearanceService;
use App\Services\PublicRequestWorkflowService;
use App\Support\ClearanceSignatories;
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
        $isDynamicOnly = in_array($user->role, ['accounting', 'principal'], true);
        $currentSignatory = $isDynamicOnly
            ? [
                'label' => $user->role === 'principal' ? 'BEC Principal' : 'Accounting Office',
                'status' => 'status',
                'signed_at' => 'signed_at',
            ]
            : ClearanceSignatories::columns($user->role);
        $statusColumn = $currentSignatory['status'];

        $status = $request->string('status')->toString() ?: 'pending';

        $clearances = Clearance::query()
            ->with([
                'user:id,fullname,course,year_level,student_id',
                'documentRequest:id,reference_no,status,requester_name,requester_student_id,requester_course,requester_year_level',
            ])
            ->where(function ($query) use ($user, $status, $statusColumn, $isDynamicOnly) {
                $query->whereHas('steps', function ($steps) use ($user, $status) {
                    $steps->where('office_code', $user->role)->where('status', $status);

                    if ($user->role === 'dean' && $user->academicDepartment) {
                        $steps->where('department_code', $user->academicDepartment->code);
                    }
                });

                if (! $isDynamicOnly) {
                    $query->orWhere(fn ($legacy) => $legacy->whereDoesntHave('steps')->where($statusColumn, $status));
                }
            })
            ->when($request->string('course')->toString(), function ($q, $course) {
                $q->where(function ($inner) use ($course) {
                    $inner->whereHas('user', fn ($userQuery) => $userQuery->where('course', $course))
                        ->orWhereHas('documentRequest', fn ($requestQuery) => $requestQuery->where('requester_course', $course));
                });
            })
            ->when($request->string('year')->toString(), function ($q, $year) {
                $q->where(function ($inner) use ($year) {
                    $inner->whereHas('user', fn ($userQuery) => $userQuery->where('year_level', $year))
                        ->orWhereHas('documentRequest', fn ($requestQuery) => $requestQuery->where('requester_year_level', $year));
                });
            })
            ->when($request->string('search')->toString(), function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('fullname', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%");
                    })->orWhereHas('documentRequest', function ($requestQuery) use ($search) {
                        $requestQuery->where('requester_name', 'like', "%{$search}%")
                            ->orWhere('requester_student_id', 'like', "%{$search}%")
                            ->orWhere('reference_no', 'like', "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $clearances->getCollection()->each(function (Clearance $clearance) use ($user): void {
            $step = $clearance->steps()->where('office_code', $user->role)
                ->when($user->role === 'dean' && $user->academicDepartment, fn ($query) => $query->where('department_code', $user->academicDepartment->code))
                ->first();
            $clearance->setAttribute('current_step_status', $step?->status);
        });

        return Inertia::render('Department/Clearances/Index', [
            'clearances' => $clearances,
            'filters' => [
                'status' => $status,
                'course' => $request->string('course')->toString(),
                'year' => $request->string('year')->toString(),
                'search' => $request->string('search')->toString(),
            ],
            'departmentStatusColumn' => $statusColumn,
            'currentSignatory' => $currentSignatory,
            'signatories' => ClearanceSignatories::definitions(),
        ]);
    }

    public function show(Request $request, Clearance $clearance): Response
    {
        $this->authorize('view', $clearance);

        $clearance->load([
            'user:id,fullname,email,course,year_level,student_id,contact_number',
            'documentRequest:id,reference_no,status,processing_stage,purpose,requester_name,requester_email,requester_contact_number,requester_student_id,requester_course,requester_year_level',
            'documentRequest.requirements:id,document_request_id,requirement_key,label,status,notes,file_path',
            ...collect(ClearanceSignatories::signerRelations())
                ->map(fn (string $relation): string => "{$relation}:id,fullname")
                ->all(),
            'steps.signer:id,fullname',
        ]);

        $currentStep = $clearance->steps->first(function ($step) use ($request) {
            if ($step->office_code !== $request->user()->role) {
                return false;
            }

            return $step->office_code !== 'dean'
                || ! $step->department_code
                || $request->user()->academicDepartment?->code === $step->department_code;
        });
        $currentSignatory = $currentStep
            ? ['label' => $currentStep->label, 'status' => 'status', 'signed_at' => 'signed_at']
            : (
                in_array($request->user()->role, ['accounting', 'principal'], true)
                    ? [
                        'label' => $request->user()->role === 'principal' ? 'BEC Principal' : 'Accounting Office',
                        'status' => 'status',
                        'signed_at' => 'signed_at',
                    ]
                    : ClearanceSignatories::columns($request->user()->role)
            );

        return Inertia::render('Department/Clearances/Show', [
            'clearance' => $clearance,
            'department' => $request->user()->role,
            'currentSignatory' => $currentSignatory,
            'signatories' => ClearanceSignatories::definitions(),
            'currentStep' => $currentStep,
        ]);
    }

    public function sign(SignClearanceRequest $request, Clearance $clearance, ClearanceService $clearanceService, PublicRequestWorkflowService $workflow): RedirectResponse
    {
        $department = $request->user()->role;
        $this->authorize('signOwnDepartment', $clearance);

        try {
            $step = $clearance->steps()->where('office_code', $department)
                ->when($department === 'dean' && $request->user()->academicDepartment, fn ($query) => $query->where('department_code', $request->user()->academicDepartment->code))
                ->first();
            $step
                ? $workflow->signStep($step, $request->user(), $request->validated('remarks'))
                : $clearanceService->signFor($clearance, $request->user(), $department, $request->validated('remarks'));
        } catch (\Throwable $exception) {
            return back()->withErrors(['sign' => $exception->getMessage()]);
        }

        return back()->with('status', 'Clearance marked as cleared.');
    }

    public function deny(DenyClearanceRequest $request, Clearance $clearance, ClearanceService $clearanceService, PublicRequestWorkflowService $workflow): RedirectResponse
    {
        $department = $request->user()->role;
        $this->authorize('rejectDepartment', $clearance);

        try {
            $step = $clearance->steps()->where('office_code', $department)
                ->when($department === 'dean' && $request->user()->academicDepartment, fn ($query) => $query->where('department_code', $request->user()->academicDepartment->code))
                ->first();
            $step
                ? $workflow->requestAction($step, $request->user(), $request->validated('remarks'))
                : $clearanceService->denyFor($clearance, $request->user(), $department, $request->validated('remarks'));
        } catch (\Throwable $exception) {
            return back()->withErrors(['deny' => $exception->getMessage()]);
        }

        return back()->with('status', 'The requestor was asked to provide corrected information.');
    }
}
