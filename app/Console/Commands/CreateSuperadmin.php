<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Usernames;
use Illuminate\Console\Command;

class CreateSuperadmin extends Command
{
    protected $signature = 'user:create-superadmin {email} {name} {password} {--username=}';

    protected $description = 'Create a superadmin user';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $username = $this->option('username')
            ? Usernames::normalize((string) $this->option('username'))
            : Usernames::uniqueFromEmail($email);

        $validator = validator(
            ['email' => $email, 'username' => $username],
            ['email' => ['required', 'email', 'unique:users,email'], 'username' => Usernames::rules()],
            Usernames::messages(),
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $this->argument('name'),
            'username' => $username,
            'email' => $email,
            'password' => bcrypt($this->argument('password')),
            'email_verified_at' => now(),
            'role' => 'superadmin',
            'status' => 'active',
            'approved_at' => now(),
        ]);
        $this->info("Created superadmin: {$user->id} - {$user->email}");

        return self::SUCCESS;
    }
}
