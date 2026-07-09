<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clearance;
use App\Support\ClearanceSignatories;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClearanceMonitorController extends Controller
{
    public function index(Request $request): Response
    {
        $clearances = Clearance::query()
            ->with([
                'user:id,fullname,course,year_level,student_id',
                'documentRequest:id,reference_no,status,requester_name,requester_student_id,requester_course,requester_year_level',
            ])
            ->when($request->string('overall_status')->toString(), fn ($query, $status) => $query->where('overall_status', $status))
            ->when($request->string('course')->toString(), function ($query, $course) {
                $query->where(function ($inner) use ($course) {
                    $inner->whereHas('user', fn ($q) => $q->where('course', $course))
                        ->orWhereHas('documentRequest', fn ($q) => $q->where('requester_course', $course));
                });
            })
            ->when($request->string('year')->toString(), function ($query, $year) {
                $query->where(function ($inner) use ($year) {
                    $inner->whereHas('user', fn ($q) => $q->where('year_level', $year))
                        ->orWhereHas('documentRequest', fn ($q) => $q->where('requester_year_level', $year));
                });
            })
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('user', function ($q) use ($search) {
                        $q->where('fullname', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%");
                    })->orWhereHas('documentRequest', function ($q) use ($search) {
                        $q->where('requester_name', 'like', "%{$search}%")
                            ->orWhere('requester_student_id', 'like', "%{$search}%")
                            ->orWhere('reference_no', 'like', "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Clearances/Index', [
            'clearances' => $clearances,
            'signatories' => ClearanceSignatories::definitions(),
            'filters' => [
                'overall_status' => $request->string('overall_status')->toString(),
                'course' => $request->string('course')->toString(),
                'year' => $request->string('year')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function show(Clearance $clearance): Response
    {
        $clearance->load([
            'user:id,fullname,email,course,year_level,student_id',
            'documentRequest:id,reference_no,status,processing_stage,purpose,requester_name,requester_email,requester_student_id,requester_course,requester_year_level',
            ...collect(ClearanceSignatories::signerRelations())
                ->map(fn (string $relation): string => "{$relation}:id,fullname")
                ->all(),
        ]);

        return Inertia::render('Admin/Clearances/Show', [
            'clearance' => $clearance,
            'signatories' => ClearanceSignatories::definitions(),
        ]);
    }
}
