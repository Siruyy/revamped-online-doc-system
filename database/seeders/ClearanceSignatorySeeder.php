<?php

namespace Database\Seeders;

use App\Models\AcademicDepartment;
use App\Models\User;
use App\Support\ClearanceSignatories;
use App\Support\Usernames;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClearanceSignatorySeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) env('SIGNATORY_DEFAULT_PASSWORD');

        if (strlen($password) < 16) {
            throw new \RuntimeException('SIGNATORY_DEFAULT_PASSWORD must be configured with at least 16 characters.');
        }

        foreach (ClearanceSignatories::SIGNATORIES as $role => $signatory) {
            $existingUserId = User::withTrashed()->where('email', $signatory['seeded_email'])->value('id');

            User::query()->updateOrCreate(
                ['email' => $signatory['seeded_email']],
                [
                    'fullname' => $signatory['label'],
                    'username' => Usernames::uniqueFromEmail($signatory['seeded_email'], $existingUserId),
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

        $accountingEmail = 'accounting@svci.test';
        $accountingUserId = User::withTrashed()->where('email', $accountingEmail)->value('id');

        User::query()->updateOrCreate(
            ['email' => $accountingEmail],
            [
                'fullname' => 'Accounting Office',
                'username' => Usernames::uniqueFromEmail($accountingEmail, $accountingUserId),
                'password' => Hash::make($password),
                'role' => 'accounting',
                'status' => 'active',
                'email_verified_at' => now(),
                'approved_at' => now(),
            ],
        );

        $principalEmail = 'principal@svci.test';
        $principalUserId = User::withTrashed()->where('email', $principalEmail)->value('id');

        User::query()->updateOrCreate(
            ['email' => $principalEmail],
            [
                'fullname' => 'BEC Principal',
                'username' => Usernames::uniqueFromEmail($principalEmail, $principalUserId),
                'password' => Hash::make($password),
                'role' => 'principal',
                'status' => 'active',
                'email_verified_at' => now(),
                'approved_at' => now(),
            ],
        );

        foreach (AcademicDepartment::query()->where('is_active', true)->get() as $department) {
            $email = 'dean.'.strtolower($department->code).'@svci.test';
            $existingUserId = User::withTrashed()->where('email', $email)->value('id');

            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'fullname' => "{$department->code} Dean",
                    'username' => Usernames::uniqueFromEmail($email, $existingUserId),
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
