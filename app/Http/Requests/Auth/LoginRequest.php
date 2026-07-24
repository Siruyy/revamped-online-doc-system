<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\Usernames;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'login' => Str::lower(trim((string) ($this->input('login') ?? $this->input('email')))),
        ]);
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotLockedOut();
        $this->ensureIsNotRateLimited();

        [$column, $identifier] = $this->loginIdentifier();

        if (! Auth::attempt([
            $column => $identifier,
            'password' => $this->string('password')->toString(),
        ], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->lockoutKey(), 60 * 60 * 24);

            $attemptedUser = User::query()->where($column, $identifier)->first();
            ActivityLogger::log(
                'login_failed',
                "Failed login attempt for {$identifier}.",
                null,
                $attemptedUser,
                ['ip' => $this->ip()]
            );

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();
            RateLimiter::hit($this->lockoutKey(), 60 * 60 * 24);

            throw ValidationException::withMessages([
                'login' => $this->statusMessage($user->status),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->lockoutKey());

        ActivityLogger::log('login_success', "User {$user->email} logged in successfully.", $user, $user, [
            'ip' => $this->ip(),
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));
        RateLimiter::hit($this->lockoutKey(), 60 * 60 * 24);

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotLockedOut(): void
    {
        if (! RateLimiter::tooManyAttempts($this->lockoutKey(), 10)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->lockoutKey());

        throw ValidationException::withMessages([
            'login' => "Account locked due to repeated failed logins. Try again in {$seconds} seconds.",
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate($this->string('login')->toString().'|'.$this->ip());
    }

    public function lockoutKey(): string
    {
        return 'lockout:'.$this->throttleKey();
    }

    /**
     * @return array{0: 'email'|'username', 1: string}
     */
    private function loginIdentifier(): array
    {
        $login = $this->string('login')->toString();

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return ['email', $login];
        }

        return ['username', Usernames::normalize($login)];
    }

    private function statusMessage(string $status): string
    {
        return match ($status) {
            'pending' => 'Your registration is pending SuperAdmin approval.',
            'suspended' => 'Your account is suspended. Please contact support.',
            'rejected' => 'Your registration was rejected. Please contact support.',
            default => 'Your account cannot access the system right now.',
        };
    }
}
