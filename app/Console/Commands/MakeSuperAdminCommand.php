<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Usernames;
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
        $existingUser = User::withTrashed()->where('email', $email)->first();
        $username = $this->ask('Username', Usernames::uniqueFromEmail($email, $existingUser?->id));
        $fullname = $this->ask('Full name', 'SVCI SuperAdmin');
        $password = $this->secret('Password (min 8 chars)');

        $validator = Validator::make(
            ['email' => $email, 'username' => Usernames::normalize($username), 'password' => $password],
            [
                'email' => ['required', 'email'],
                'username' => Usernames::rules($existingUser?->id),
                'password' => ['required', 'string', 'min:8'],
            ],
            Usernames::messages(),
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'fullname' => $fullname,
                'username' => Usernames::normalize($username),
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
