<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Announcements/Index', [
            'announcements' => Announcement::query()
                ->with('author:id,fullname')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'audience' => ['required', 'in:all,student,staff'],
            'pinned' => ['required', 'boolean'],
            'is_published' => ['required', 'boolean'],
        ]);

        $announcement = Announcement::query()->create([
            'author_id' => $request->user()->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'audience' => $validated['audience'],
            'pinned' => $validated['pinned'],
            'published_at' => $validated['is_published'] ? now() : null,
        ]);

        ActivityLogger::log(
            'announcement_created',
            "Admin {$request->user()->email} created announcement {$announcement->title}.",
            $request->user()
        );

        return back()->with('status', 'Announcement created successfully.');
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'audience' => ['required', 'in:all,student,staff'],
            'pinned' => ['required', 'boolean'],
            'is_published' => ['required', 'boolean'],
        ]);

        $announcement->update([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'audience' => $validated['audience'],
            'pinned' => $validated['pinned'],
            'published_at' => $validated['is_published'] ? ($announcement->published_at ?? now()) : null,
        ]);

        ActivityLogger::log(
            'announcement_updated',
            "Admin {$request->user()->email} updated announcement {$announcement->title}.",
            $request->user()
        );

        return back()->with('status', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        ActivityLogger::log(
            'announcement_deleted',
            "Admin {$request->user()->email} deleted announcement {$announcement->title}.",
            $request->user()
        );

        return back()->with('status', 'Announcement deleted successfully.');
    }
}
