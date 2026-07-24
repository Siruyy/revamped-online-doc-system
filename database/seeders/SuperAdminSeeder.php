<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Usernames;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('SUPERADMIN_EMAIL');
        $password = env('SUPERADMIN_PASSWORD');
        $fullname = env('SUPERADMIN_FULLNAME', 'SVCI SuperAdmin');

        if (! $email || ! $password) {
            $this->command?->warn('Skipping SuperAdminSeeder: SUPERADMIN_EMAIL or SUPERADMIN_PASSWORD not set.');

            return;
        }

        $existingUserId = User::withTrashed()->where('email', $email)->value('id');
        $username = env('SUPERADMIN_USERNAME');
        $username = $username
            ? Usernames::normalize((string) $username)
            : Usernames::uniqueFromEmail($email, $existingUserId);

        User::updateOrCreate(
            ['email' => $email],
            [
                'fullname' => $fullname,
                'username' => $username,
                'password' => Hash::make($password),
                'role' => 'superadmin',
                'status' => 'active',
                'email_verified_at' => now(),
                'approved_at' => now(),
            ]
        );
    }
}
