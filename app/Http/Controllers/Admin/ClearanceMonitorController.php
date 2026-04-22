<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clearance;
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
                'documentRequest:id,reference_no,status',
            ])
            ->when($request->string('overall_status')->toString(), fn ($query, $status) => $query->where('overall_status', $status))
            ->when($request->string('course')->toString(), fn ($query, $course) => $query->whereHas('user', fn ($q) => $q->where('course', $course)))
            ->when($request->string('year')->toString(), fn ($query, $year) => $query->whereHas('user', fn ($q) => $q->where('year_level', $year)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Clearances/Index', [
            'clearances' => $clearances,
            'filters' => [
                'overall_status' => $request->string('overall_status')->toString(),
                'course' => $request->string('course')->toString(),
                'year' => $request->string('year')->toString(),
            ],
        ]);
    }

    public function show(Clearance $clearance): Response
    {
        $clearance->load([
            'user:id,fullname,email,course,year_level,student_id',
            'documentRequest:id,reference_no,status,processing_stage,purpose',
            'teacherSigner:id,fullname',
            'deanSigner:id,fullname',
            'accountingSigner:id,fullname',
            'saoSigner:id,fullname',
        ]);

        return Inertia::render('Admin/Clearances/Show', [
            'clearance' => $clearance,
        ]);
    }
}
