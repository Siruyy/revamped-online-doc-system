<?php

namespace App\Policies;

use App\Models\User;

class SchoolBrandingPolicy
{
    public function manage(User $user): bool
    {
        return in_array($user->role, ['admin', 'superadmin'], true);
    }
}
