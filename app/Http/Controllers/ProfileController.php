<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\ClearanceSignatories;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Laravel\Facades\Image;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render($this->resolveProfileView($request->user()->role), [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        ActivityLogger::log(
            'profile_updated',
            "User {$user->email} updated profile details.",
            $user,
            $user
        );

        return Redirect::route($this->profileEditRouteName($user));
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
        ]);

        $user = $request->user();
        $avatarFile = $validated['avatar'];

        try {
            $encoded = Image::read($avatarFile->getRealPath())
                ->cover(512, 512)
                ->toJpeg(85);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'avatar' => 'The uploaded avatar image could not be processed.',
            ]);
        }

        $avatarPath = "avatars/{$user->id}/".Str::uuid().'.jpg';

        Storage::disk('public')->put($avatarPath, (string) $encoded);

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update(['avatar_path' => $avatarPath]);

        ActivityLogger::log(
            'avatar_updated',
            "User {$user->email} updated avatar.",
            $user,
            $user
        );

        return Redirect::route($this->profileEditRouteName($user))->with('status', 'Avatar updated successfully.');
    }

    public function updateSignature(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! ClearanceSignatories::isSignatoryRole($user->role)) {
            abort(403);
        }

        $validated = $request->validate([
            'signature' => ['required', 'image', 'mimes:png', 'mimetypes:image/png', 'max:1024', 'dimensions:max_width=4096,max_height=4096'],
        ]);

        $signatureFile = $validated['signature'];
        $signaturePath = $signatureFile->store("signatures/{$user->id}", 'local');

        if ($user->signature_path) {
            Storage::disk('local')->delete($user->signature_path);
        }

        $user->update(['signature_path' => $signaturePath]);

        ActivityLogger::log(
            'signature_updated',
            "Department officer {$user->email} updated signature.",
            $user,
            $user
        );

        return Redirect::route($this->profileEditRouteName($user))->with('status', 'Signature updated successfully.');
    }

    private function profileEditRouteName(User $user): string
    {
        return match ($user->role) {
            'student' => 'student.profile.edit',
            'admin' => 'admin.profile.edit',
            'dean', 'president', 'librarian', 'student_affairs', 'alumni', 'guidance' => 'department.profile.edit',
            'superadmin' => 'superadmin.profile.edit',
            default => 'profile.edit',
        };
    }

    private function resolveProfileView(string $role): string
    {
        return match ($role) {
            'student' => 'Student/Profile',
            'admin' => 'Admin/Profile',
            'dean', 'president', 'librarian', 'student_affairs', 'alumni', 'guidance' => 'Department/Profile',
            'superadmin' => 'SuperAdmin/Profile',
            default => 'Profile/Edit',
        };
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
