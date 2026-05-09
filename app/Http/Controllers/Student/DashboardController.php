<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\DocumentRequest;
use App\Models\Faq;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $student = request()->user();

        $activeRequests = $student->documentRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        $pendingPayments = $student->payments()
            ->whereIn('status', ['pending', 'pending_approval', 'denied'])
            ->count();

        $latestClearance = $student->clearances()
            ->latest('id')
            ->first();

        $latestRequest = DocumentRequest::query()
            ->with(['documentType:id,name,category,release_channel', 'payments:id,document_request_id,status,total_amount,receipt_path', 'claimSlip', 'requirements:id,document_request_id,requirement_key,label,status,file_path'])
            ->where('user_id', $student->id)
            ->latest()
            ->first();

        $nextAction = $this->computeNextAction($latestRequest);

        $announcements = Announcement::query()
            ->whereIn('audience', ['all', 'student'])
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('pinned')
            ->latest('published_at')
            ->limit(3)
            ->get(['id', 'title', 'body', 'pinned', 'published_at']);

        $faqs = Faq::query()
            ->whereIn('role', ['all', 'student'])
            ->orderBy('sort_order')
            ->limit(5)
            ->get(['id', 'question', 'answer']);

        $notifications = $student->notifications()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? class_basename($notification->type),
                'message' => $notification->data['message'] ?? ($notification->data['title'] ?? 'Notification'),
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ]);

        return Inertia::render('Student/Dashboard', [
            'stats' => [
                'active_requests' => $activeRequests,
                'pending_payments' => $pendingPayments,
                'clearance_status' => $latestClearance?->overall_status ?? 'none',
            ],
            'latestRequest' => $latestRequest,
            'nextAction' => $nextAction,
            'announcements' => $announcements,
            'faqs' => $faqs,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Determine the student's next best action given their latest request.
     *
     * @return array{
     *   title:string, description:string, cta_label:?string, cta_href:?string, tone:string
     * }
     */
    private function computeNextAction(?DocumentRequest $request): array
    {
        if (! $request) {
            return [
                'title' => 'Start a new document request',
                'description' => 'Request a Transcript of Records, Certification, or any academic record in a few guided steps.',
                'cta_label' => 'Submit a Request',
                'cta_href' => route('student.requests.create'),
                'tone' => 'primary',
            ];
        }

        $payment = $request->payments->first();

        if ($request->status === 'pending') {
            return [
                'title' => 'Awaiting admin review',
                'description' => "Your {$request->documentType->name} request is in the queue. We'll email you the moment it moves forward.",
                'cta_label' => 'View request',
                'cta_href' => route('student.requests.show', $request),
                'tone' => 'info',
            ];
        }

        if ($request->status === 'approved' && (! $payment || ! $payment->receipt_path)) {
            return [
                'title' => 'Pay the Cashier & upload your receipt',
                'description' => 'Your request is approved. Pay at the Accounting Office, then upload your official receipt so processing can start.',
                'cta_label' => 'Upload receipt',
                'cta_href' => route('student.payments.index'),
                'tone' => 'warning',
            ];
        }

        $missingRequirement = $request->requirements->firstWhere('status', '!=', 'validated');
        if ($missingRequirement) {
            return [
                'title' => 'Complete your attachments',
                'description' => "Please submit: {$missingRequirement->label}.",
                'cta_label' => 'Open request',
                'cta_href' => route('student.requests.show', $request),
                'tone' => 'warning',
            ];
        }

        if ($request->processing_stage === 'ready_for_pickup') {
            return [
                'title' => 'Ready for pickup',
                'description' => 'Bring a valid ID and your claim slip on the date indicated. If someone else will claim, they need your SPA / authorization letter.',
                'cta_label' => 'View claim slip',
                'cta_href' => route('student.requests.show', $request),
                'tone' => 'success',
            ];
        }

        if ($request->status === 'completed') {
            return [
                'title' => 'Request released',
                'description' => "Your {$request->documentType->name} has been released. You may submit a new request anytime.",
                'cta_label' => 'Submit another',
                'cta_href' => route('student.requests.create'),
                'tone' => 'success',
            ];
        }

        return [
            'title' => 'Processing in progress',
            'description' => 'The Office of the Registrar is working on your request. Expected release: '
                .($request->expected_release_on?->format('M d, Y') ?? 'TBD').'.',
            'cta_label' => 'Open request',
            'cta_href' => route('student.requests.show', $request),
            'tone' => 'info',
        ];
    }
}
