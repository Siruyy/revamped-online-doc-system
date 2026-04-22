<?php

namespace App\Http\Controllers\Auth;

use App\Events\RegistrationSubmitted as RegistrationSubmittedEvent;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\RegistrationSubmittedNotification;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'course' => 'required|string|max:150',
            'year_level' => 'required|integer|min:1|max:8',
            'student_id' => 'required|string|max:100|unique:'.User::class.',student_id',
            'contact_number' => 'required|string|max:30',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'fullname' => $request->string('name')->toString(),
            'email' => $request->email,
            'course' => $request->course,
            'year_level' => $request->year_level,
            'student_id' => $request->student_id,
            'contact_number' => $request->contact_number,
            'role' => 'student',
            'status' => 'pending',
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        $superAdmins = User::query()->where('role', 'superadmin')->get();
        Notification::send($superAdmins, new RegistrationSubmittedNotification($user));

        RegistrationSubmittedEvent::dispatch(
            $user->id,
            $user->fullname,
            $user->email,
            (string) $user->student_id,
        );

        ActivityLogger::log(
            'registration_submitted',
            "User {$user->email} submitted a new registration request.",
            $user,
            $user
        );

        return redirect()->route('registration.pending');
    }
}
