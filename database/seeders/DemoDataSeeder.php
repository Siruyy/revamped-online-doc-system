<?php

namespace Database\Seeders;

use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Message;
use App\Models\Payment;
use App\Models\PaymentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            User::factory()->admin()->make([
                'fullname' => 'SVCI Admin',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ])->toArray()
        );

        User::query()->updateOrCreate(
            ['email' => 'student@example.com'],
            User::factory()->student()->make([
                'fullname' => 'SVCI Student',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ])->toArray()
        );

        User::query()->updateOrCreate(
            ['email' => 'teacher@example.com'],
            User::factory()->teacher()->make([
                'fullname' => 'Teacher Signer',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ])->toArray()
        );
        User::query()->updateOrCreate(
            ['email' => 'dean@example.com'],
            User::factory()->dean()->make([
                'fullname' => 'Dean Signer',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ])->toArray()
        );
        User::query()->updateOrCreate(
            ['email' => 'accounting@example.com'],
            User::factory()->accounting()->make([
                'fullname' => 'Accounting Signer',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ])->toArray()
        );
        User::query()->updateOrCreate(
            ['email' => 'sao@example.com'],
            User::factory()->sao()->make([
                'fullname' => 'SAO Signer',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ])->toArray()
        );

        // Seed default school payment profile so students see payment instructions immediately
        PaymentProfile::query()->updateOrCreate(
            ['id' => 1],
            [
                'bank_name' => 'BDO Unibank',
                'account_name' => 'St. Vincent College Incorporated',
                'account_number' => '001-234-567-890',
                'instructions' => "1. Transfer the exact amount to the account below.\n2. Take a screenshot of your transfer confirmation.\n3. Upload the screenshot as your payment receipt on this page.\n4. Wait for admin approval (usually within 1 working day).",
                'is_active' => true,
            ]
        );

        $students = User::factory()->count(8)->student()->create();
        $documentTypes = DocumentType::query()->count() > 0
            ? DocumentType::all()
            : DocumentType::factory()->count(5)->create();

        foreach ($students as $student) {
            $type = $documentTypes->random();
            $request = DocumentRequest::factory()->for($student)->for($type)->create();

            Payment::factory()->for($student)->for($request)->pendingApproval()->create([
                'total_amount' => $type->fee,
            ]);

            Clearance::factory()->for($student)->for($request)->inProgress()->create();
        }

        Message::factory()->count(10)->create([
            'receiver_id' => $admin->id,
        ]);
    }
}
