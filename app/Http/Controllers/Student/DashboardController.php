<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
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
            'announcements' => $announcements,
            'faqs' => $faqs,
            'notifications' => $notifications,
        ]);
    }
}
