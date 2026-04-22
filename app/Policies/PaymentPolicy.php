<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'superadmin'], true);
    }

    public function view(User $user, Payment $payment): bool
    {
        if (in_array($user->role, ['admin', 'superadmin'], true)) {
            return true;
        }

        return $user->role === 'student' && $payment->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'student' && $user->status === 'active';
    }

    public function update(User $user, Payment $payment): bool
    {
        return $this->upload($user, $payment);
    }

    public function upload(User $user, Payment $payment): bool
    {
        return $user->role === 'student'
            && $payment->user_id === $user->id
            && $payment->status === 'pending';
    }

    public function approve(User $user, Payment $payment): bool
    {
        return in_array($user->role, ['admin', 'superadmin'], true);
    }

    public function deny(User $user, Payment $payment): bool
    {
        return in_array($user->role, ['admin', 'superadmin'], true);
    }
}
