<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DocumentTypeSeeder::class,
            SuperAdminSeeder::class,
        ]);

        if (! app()->environment('production')) {
            $this->call([
                DemoDataSeeder::class,
            ]);
        }

        // Create the dummy superadmin account
        \App\Models\User::updateOrCreate(
            ['email' => 'superadmin.dummy@gmail.com'],
            [
                'fullname' => 'Superadmin Dummy',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'superadmin',
                'status' => 'active',
                'email_verified_at' => now(),
                'approved_at' => now(),
            ]
        );
    }
}
