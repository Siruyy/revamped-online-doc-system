<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateSuperadmin extends Command
{
    protected $signature = 'user:create-superadmin {email} {name} {password}';
    protected $description = 'Create a superadmin user';

    public function handle()
    {
        $user = User::create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => bcrypt($this->argument('password')),
            'email_verified_at' => now(),
            'role' => 'superadmin',
        ]);
        $this->info("Created superadmin: {$user->id} - {$user->email}");
    }
}
