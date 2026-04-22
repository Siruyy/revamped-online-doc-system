<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeSuperAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'svci:make-superadmin {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or promote a user to SuperAdmin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $fullname = $this->ask('Full name', 'SVCI SuperAdmin');
        $password = $this->secret('Password (min 8 chars)');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email'], 'password' => ['required', 'string', 'min:8']]
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
                'role' => 'superadmin',
                'status' => 'active',
                'email_verified_at' => now(),
                'approved_at' => now(),
            ]
        );

        $this->info("SuperAdmin ready: {$email}");

        return self::SUCCESS;
    }
}
