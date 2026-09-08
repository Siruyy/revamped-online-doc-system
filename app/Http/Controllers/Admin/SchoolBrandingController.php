<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSchoolBrandingRequest;
use App\Models\SchoolBranding;
use App\Services\SchoolBrandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolBrandingController extends Controller
{
    public function index(SchoolBrandingService $branding): Response
    {
        $this->authorize('manage', SchoolBranding::class);

        return Inertia::render('Admin/Settings/SchoolBranding', ['logoUrl' => $branding->logoDataUri()]);
    }

    public function update(UpdateSchoolBrandingRequest $request, SchoolBrandingService $branding): RedirectResponse
    {
        $branding->saveLogo($request->file('logo'), $request->user());

        return back()->with('status', 'School branding logo saved.');
    }

    public function destroy(Request $request, SchoolBrandingService $branding): RedirectResponse
    {
        $this->authorize('manage', SchoolBranding::class);
        $branding->saveLogo(null, $request->user());

        return back()->with('status', 'School branding logo removed.');
    }
}
