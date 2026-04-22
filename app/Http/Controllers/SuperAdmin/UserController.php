<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\RegistrationApprovedNotification;
use App\Notifications\RegistrationRejectedNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function pending(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('SuperAdmin/Users/Pending', [
            'users' => User::query()
                ->where('role', 'student')
                ->where('status', 'pending')
                ->latest()
                ->get(['id', 'fullname', 'email', 'course', 'year_level', 'student_id', 'created_at']),
        ]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        $this->authorize('approve', $user);

        $user->update([
            'status' => 'active',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $user->notify(new RegistrationApprovedNotification());

        ActivityLogger::log(
            'registration_approved',
            "SuperAdmin {$request->user()->email} approved {$user->email}.",
            $request->user(),
            $user
        );

        return back()->with('status', 'User approved successfully.');
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $this->authorize('reject', $user);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $user->notify(new RegistrationRejectedNotification($validated['reason'] ?? null));

        ActivityLogger::log(
            'registration_rejected',
            "SuperAdmin {$request->user()->email} rejected {$user->email}.",
            $request->user(),
            $user,
            ['reason' => $validated['reason'] ?? null]
        );

        return back()->with('status', 'User rejected successfully.');
    }
}
