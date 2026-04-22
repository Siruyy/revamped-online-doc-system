<?php

namespace Database\Seeders;

use App\Models\User;
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

        User::updateOrCreate(
            ['email' => $email],
            [
                'fullname' => $fullname,
                'password' => Hash::make($password),
                'role' => 'superadmin',
                'status' => 'active',
                'email_verified_at' => now(),
                'approved_at' => now(),
            ]
        );
    }
}
