<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\ClearanceSignatories;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeStaffCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'svci:make-staff {email} {role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or promote a staff user (admin or clearance signatory)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $role = (string) $this->argument('role');
        $fullname = $this->ask('Full name', 'SVCI Staff');
        $password = $this->secret('Password (min 8 chars)');
        $allowedRoles = ClearanceSignatories::roleOptions();

        $validator = Validator::make(
            ['email' => $email, 'role' => $role, 'password' => $password],
            [
                'email' => ['required', 'email'],
                'role' => ['required', 'in:'.implode(',', $allowedRoles)],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'fullname' => $fullname,
                'password' => Hash::make($password),
                'role' => $role,
                'status' => 'active',
                'email_verified_at' => now(),
                'approved_at' => now(),
            ]
        );

        $this->info("Staff account ready: {$email} ({$role})");

        return self::SUCCESS;
    }
}
