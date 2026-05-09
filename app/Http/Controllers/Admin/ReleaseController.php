<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClaimSlip;
use App\Models\DocumentRequest;
use App\Services\Policy\ClaimSlipService;
use App\Services\RequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReleaseController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DocumentRequest::class);

        $status = $request->string('status', 'ready')->toString();

        $slips = ClaimSlip::query()
            ->with([
                'user:id,fullname,student_id,course,year_level,contact_number',
                'documentRequest:id,reference_no,document_type_id,expected_release_on',
                'documentRequest.documentType:id,name,category,release_channel',
                'releaser:id,fullname',
            ])
            ->when($status === 'ready', fn ($q) => $q->where('state', 'ready'))
            ->when($status === 'released', fn ($q) => $q->where('state', 'released'))
            ->when($status === 'void', fn ($q) => $q->where('state', 'void'))
            ->when($request->string('search')->toString(), function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('claim_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('fullname', 'like', "%{$search}%")->orWhere('student_id', 'like', "%{$search}%"))
                        ->orWhereHas('documentRequest', fn ($r) => $r->where('reference_no', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Releases/Index', [
            'slips' => $slips,
            'filters' => [
                'status' => $status,
                'search' => $request->string('search')->toString(),
            ],
            'releaseChannels' => config('policy.release_channels', []),
        ]);
    }

    public function release(Request $request, ClaimSlip $claimSlip, ClaimSlipService $service): RedirectResponse
    {
        $this->authorize('updateStage', $claimSlip->documentRequest);

        $validated = $request->validate([
            'claimant_name' => ['required', 'string', 'max:120'],
            'claimant_id_reference' => ['required', 'string', 'max:120'],
            'is_proxy_release' => ['sometimes', 'boolean'],
            'authorization_type' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (($validated['is_proxy_release'] ?? false) && empty($validated['authorization_type'])) {
            return back()->withErrors(['authorization_type' => 'For proxy releases, please record the SPA / authorization type.']);
        }

        $service->markReleased(
            $claimSlip,
            $request->user(),
            $validated['claimant_name'],
            $validated['claimant_id_reference'],
            (bool) ($validated['is_proxy_release'] ?? false),
            $validated['authorization_type'] ?? null,
            $validated['notes'] ?? null,
        );

        return back()->with('status', "Released claim slip {$claimSlip->claim_number}.");
    }

    public function void(Request $request, ClaimSlip $claimSlip, ClaimSlipService $service): RedirectResponse
    {
        $this->authorize('updateStage', $claimSlip->documentRequest);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:200']]);

        $service->voidSlip($claimSlip, $request->user(), $validated['reason']);

        return back()->with('status', 'Claim slip voided.');
    }

    public function markHonorableDismissal(DocumentRequest $documentRequest, RequestService $service): RedirectResponse
    {
        $this->authorize('updateStage', $documentRequest);

        try {
            $service->markHonorableDismissalReceived($documentRequest, request()->user());
        } catch (\Throwable $e) {
            return back()->withErrors(['hd' => $e->getMessage()]);
        }

        return back()->with('status', 'Honorable Dismissal received recorded; SLA started.');
    }
}
