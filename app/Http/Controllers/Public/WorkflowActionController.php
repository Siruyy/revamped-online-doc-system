<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\RequestRequirement;
use App\Services\ActivityLogger;
use App\Services\PaymentService;
use App\Support\FileUploadLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorkflowActionController extends Controller
{
    public function uploadRequirement(
        Request $request,
        DocumentRequest $documentRequest,
        RequestRequirement $requirement,
    ): RedirectResponse {
        $this->verifyAccess($request, $documentRequest);
        abort_unless($requirement->document_request_id === $documentRequest->id, 404);

        $validated = $request->validate([
            'access_code' => ['required', 'string', 'max:32'],
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'mimetypes:image/jpeg,image/png,application/pdf',
                'max:'.FileUploadLimits::publicIntakeMaxFileKilobytes(),
            ],
        ]);

        $path = $this->store($validated['file'], "request-requirements/public/{$documentRequest->id}");

        if ($requirement->file_path) {
            Storage::disk('local')->delete($requirement->file_path);
        }

        $requirement->update([
            'file_path' => $path,
            'status' => 'submitted',
            'notes' => null,
        ]);
        $requirement->documentRequest->clearances()
            ->with('steps')
            ->get()
            ->flatMap->steps
            ->where('status', 'needs_action')
            ->each->update(['status' => 'pending', 'signed_by' => null, 'signed_at' => null]);

        ActivityLogger::log(
            'public_requirement_resubmitted',
            "A requestor resubmitted {$requirement->label} for {$documentRequest->reference_no}.",
            null,
            null,
            ['document_request_id' => $documentRequest->id, 'requirement_id' => $requirement->id],
        );

        return back()->with('status', 'The document was uploaded and returned to the reviewing office.');
    }

    public function uploadPayment(
        Request $request,
        DocumentRequest $documentRequest,
        PaymentService $paymentService,
    ): RedirectResponse {
        $this->verifyAccess($request, $documentRequest);
        $validated = $request->validate([
            'access_code' => ['required', 'string', 'max:32'],
            'payment_method' => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'receipt' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'mimetypes:image/jpeg,image/png,application/pdf',
                'max:'.FileUploadLimits::publicIntakeMaxFileKilobytes(),
            ],
        ]);

        if ($documentRequest->workflow_stage !== 'awaiting_payment') {
            return back()->withErrors(['receipt' => 'Payment is not open for this request yet.']);
        }

        $payment = $documentRequest->payments()->whereIn('status', ['pending', 'denied'])->latest()->firstOrFail();
        $path = $this->store($validated['receipt'], "payment-receipts/public/{$documentRequest->id}");

        if ($payment->receipt_path) {
            Storage::disk('local')->delete($payment->receipt_path);
        }

        $payment->update([
            'receipt_path' => $path,
            'payment_method' => $validated['payment_method'],
            'reference_number' => $validated['reference_number'] ?? null,
            'status' => 'pending_approval',
            'denial_reason' => null,
            'submitted_at' => now(),
        ]);
        $documentRequest->update(['workflow_stage' => 'payment_review']);
        $paymentService->notifyReviewers($payment);

        return back()->with('status', 'Payment receipt submitted for accounting validation.');
    }

    public function downloadClaimSlip(Request $request, DocumentRequest $documentRequest): BinaryFileResponse
    {
        $this->verifyAccess($request, $documentRequest);
        $slip = $documentRequest->claimSlip;
        abort_unless($slip && in_array($slip->state, ['ready', 'released'], true) && $slip->pdf_path, 404);
        abort_unless(Storage::disk('local')->exists($slip->pdf_path), 404);

        return response()->download(
            Storage::disk('local')->path($slip->pdf_path),
            "SVCI-Claim-Slip-{$slip->claim_number}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
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

    private function store(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $directory.'/'.Str::uuid().'.'.$extension;
        Storage::disk('local')->put($path, $file->getContent());

        return $path;
    }
}
