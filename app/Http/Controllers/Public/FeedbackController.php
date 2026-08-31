<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\RequestFeedback;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $this->verifyAccess($request, $documentRequest);

        if ($documentRequest->processing_stage !== 'released' || $documentRequest->status !== 'completed') {
            return back()->withErrors(['feedback' => 'Feedback is available after the request has been released.']);
        }

        if ($documentRequest->feedback()->exists()) {
            return back()->with('status', 'Feedback has already been submitted for this request.');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'service_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comments' => ['nullable', 'string', 'max:1000'],
            'suggestions' => ['nullable', 'string', 'max:1000'],
        ]);

        RequestFeedback::query()->create([
            'document_request_id' => $documentRequest->id,
            'rating' => $validated['rating'],
            'service_rating' => $validated['service_rating'] ?? null,
            'comments' => $validated['comments'] ?? null,
            'suggestions' => $validated['suggestions'] ?? null,
            'submitted_at' => now(),
        ]);

        ActivityLogger::log(
            'public_request_feedback_submitted',
            "Feedback was submitted for public request {$documentRequest->reference_no}.",
            null,
            null,
            ['document_request_id' => $documentRequest->id],
        );

        return back()->with('status', 'Thank you for sharing your feedback.');
    }

    private function verifyAccess(Request $request, DocumentRequest $documentRequest): void
    {
        $candidate = hash('sha256', strtoupper(trim((string) $request->input('access_code'))));
        abort_unless(
            $documentRequest->intake_mode === 'public'
            && $documentRequest->tracking_access_hash
            && hash_equals($documentRequest->tracking_access_hash, $candidate),
            403,
        );
    }
}
