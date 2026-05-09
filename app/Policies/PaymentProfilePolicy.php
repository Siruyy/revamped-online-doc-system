<?php

namespace App\Policies;

use App\Models\PaymentProfile;
use App\Models\User;

class PaymentProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'superadmin'], true);
    }

    public function manage(User $user): bool
    {
        return in_array($user->role, ['admin', 'superadmin'], true);
    }

    public function viewQr(User $user, PaymentProfile $paymentProfile): bool
    {
        if (in_array($user->role, ['admin', 'superadmin'], true)) {
            return true;
        }

        return $user->role === 'student' && $paymentProfile->is_active;
    }
}
