<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class Usernames
{
    public static function normalize(?string $username): string
    {
        return Str::lower(trim((string) $username));
    }

    /**
     * @return array<int, mixed>
     */
    public static function rules(?int $ignoreUserId = null): array
    {
        $unique = Rule::unique(User::class, 'username');

        if ($ignoreUserId !== null) {
            $unique->ignore($ignoreUserId);
        }

        return [
            'required',
            'string',
            'min:3',
            'max:30',
            'regex:/\A[a-z0-9][a-z0-9._-]*[a-z0-9]\z/',
            $unique,
        ];
    }

    public static function uniqueFromEmail(string $email, ?int $ignoreUserId = null): string
    {
        $localPart = Str::before(Str::lower($email), '@');
        $base = preg_replace('/[^a-z0-9._-]+/', '_', $localPart) ?? '';
        $base = trim($base, '._-');
        $base = trim(Str::limit($base, 30, ''), '._-');

        if (strlen($base) < 3) {
            $base = 'user_'.($base !== '' ? $base : 'account');
        }

        $candidate = $base;
        $suffix = 1;

        while (User::withTrashed()
            ->where('username', $candidate)
            ->when($ignoreUserId !== null, fn ($query) => $query->where('id', '!=', $ignoreUserId))
            ->exists()) {
            $ending = '_'.$suffix++;
            $candidate = Str::limit($base, 30 - strlen($ending), '').$ending;
        }

        return $candidate;
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'username.regex' => 'The username may only contain lowercase letters, numbers, dots, hyphens, and underscores, and must start and end with a letter or number.',
        ];
    }
}
