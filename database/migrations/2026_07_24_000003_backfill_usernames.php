<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->select(['id', 'email'])
            ->whereNull('username')
            ->orderBy('id')
            ->chunkById(200, function ($users): void {
                foreach ($users as $user) {
                    $base = $this->baseUsername((string) $user->email, (int) $user->id);
                    $candidate = $base;
                    $suffix = 1;

                    while (DB::table('users')->where('username', $candidate)->exists()) {
                        $ending = '_'.$suffix++;
                        $candidate = Str::limit($base, 30 - strlen($ending), '').$ending;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['username' => $candidate]);
                }
            });
    }

    public function down(): void
    {
        // Usernames are retained when rolling this data migration back.
    }

    private function baseUsername(string $email, int $userId): string
    {
        $localPart = Str::before(Str::lower($email), '@');
        $username = preg_replace('/[^a-z0-9._-]+/', '_', $localPart) ?? '';
        $username = trim($username, '._-');
        $username = trim(Str::limit($username, 30, ''), '._-');

        if (strlen($username) < 3) {
            $username = 'user_'.$userId;
        }

        return $username;
    }
};
