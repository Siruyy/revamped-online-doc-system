<?php

namespace Database\Seeders;

use App\Models\AcademicDepartment;
use App\Models\User;
use App\Support\ClearanceSignatories;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClearanceSignatorySeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) env('SIGNATORY_DEFAULT_PASSWORD', 'password');

        foreach (ClearanceSignatories::SIGNATORIES as $role => $signatory) {
            User::query()->updateOrCreate(
                ['email' => $signatory['seeded_email']],
                [
                    'fullname' => $signatory['label'],
                    'password' => Hash::make($password),
                    'role' => $role,
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'course' => null,
                    'year_level' => null,
                    'student_id' => null,
                    'contact_number' => null,
                    'approved_at' => now(),
                ]
            );
        }

        User::query()->updateOrCreate(
            ['email' => 'accounting@svci.test'],
            [
                'fullname' => 'Accounting Office',
                'password' => Hash::make($password),
                'role' => 'accounting',
                'status' => 'active',
                'email_verified_at' => now(),
                'approved_at' => now(),
            ],
        );

        foreach (AcademicDepartment::query()->where('is_active', true)->get() as $department) {
            User::query()->updateOrCreate(
                ['email' => 'dean.'.strtolower($department->code).'@svci.test'],
                [
                    'fullname' => "{$department->code} Dean",
                    'password' => Hash::make($password),
                    'role' => 'dean',
                    'academic_department_id' => $department->id,
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'approved_at' => now(),
                ],
            );
        }
    }
}
