<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitClearanceFileRequest;
use App\Models\Clearance;
use App\Services\ClearanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClearanceController extends Controller
{
    public function show(Request $request): Response
    {
        $clearance = Clearance::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'teacherSigner:id,fullname',
                'deanSigner:id,fullname',
                'accountingSigner:id,fullname',
                'saoSigner:id,fullname',
                'documentRequest:id,reference_no,status,processing_stage',
            ])
            ->latest()
            ->first();

        return Inertia::render('Student/Clearance/Show', [
            'clearance' => $clearance,
        ]);
    }

    public function submit(SubmitClearanceFileRequest $request, ClearanceService $clearanceService): RedirectResponse
    {
        $clearance = Clearance::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->firstOrFail();

        $this->authorize('view', $clearance);
        try {
            $clearanceService->submitFile($clearance, $request->validated('clearance_file'));
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'clearance_file' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'Clearance file uploaded successfully.');
    }

    public function downloadPdf(Request $request): RedirectResponse
    {
        $clearance = Clearance::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->firstOrFail();

        $this->authorize('downloadPdf', $clearance);

        if (! $clearance->pdf_path) {
            return back()->withErrors([
                'clearance' => 'Clearance PDF is not available yet.',
            ]);
        }

        return redirect()->route('files.clearance-pdf', $clearance);
    }
}
