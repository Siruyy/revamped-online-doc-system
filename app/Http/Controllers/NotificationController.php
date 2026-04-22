<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->when($request->string('read')->toString() === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($request->string('read')->toString() === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->latest()
            ->paginate(15)
            ->through(fn ($notification) => [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? class_basename($notification->type),
                'message' => $notification->data['message'] ?? ($notification->data['title'] ?? 'Notification'),
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ])
            ->withQueryString();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filters' => [
                'read' => $request->string('read')->toString(),
            ],
            'routePrefix' => match ($request->user()->role) {
                'admin' => 'admin',
                'superadmin' => 'superadmin',
                'teacher', 'dean', 'accounting', 'sao' => 'department',
                default => 'student',
            },
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
