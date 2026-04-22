<?php

namespace Database\Seeders;

use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Message;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'fullname' => 'SVCI Admin',
            'email' => 'admin@example.com',
        ]);

        User::factory()->teacher()->create(['fullname' => 'Teacher Signer']);
        User::factory()->dean()->create(['fullname' => 'Dean Signer']);
        User::factory()->accounting()->create(['fullname' => 'Accounting Signer']);
        User::factory()->sao()->create(['fullname' => 'SAO Signer']);

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
