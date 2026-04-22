<?php

namespace App\Providers;

use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\Payment;
use App\Models\User;
use App\Policies\ClearancePolicy;
use App\Policies\DocumentRequestPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(DocumentRequest::class, DocumentRequestPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Clearance::class, ClearancePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::before(function (User $user) {
            return $user->role === 'superadmin' ? true : null;
        });
    }
}
