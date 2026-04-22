<?php

namespace App\Policies;

use App\Models\Clearance;
use App\Models\User;

class ClearancePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'superadmin', 'teacher', 'dean', 'accounting', 'sao'], true);
    }

    public function view(User $user, Clearance $clearance): bool
    {
        if (in_array($user->role, ['admin', 'superadmin'], true)) {
            return true;
        }

        if ($user->role === 'student') {
            return $clearance->user_id === $user->id;
        }

        return $this->sign($user, $clearance);
    }

    public function create(User $user): bool
    {
        return $user->role === 'student' && $user->status === 'active';
    }

    public function update(User $user, Clearance $clearance): bool
    {
        return $this->sign($user, $clearance);
    }

    public function sign(User $user, Clearance $clearance): bool
    {
        return match ($user->role) {
            'teacher' => in_array($clearance->teacher_status, ['pending', 'rejected'], true),
            'dean' => in_array($clearance->dean_status, ['pending', 'rejected'], true),
            'accounting' => in_array($clearance->accounting_status, ['pending', 'rejected'], true),
            'sao' => in_array($clearance->sao_status, ['pending', 'rejected'], true),
            default => false,
        };
    }

    public function signFor(User $user, Clearance $clearance, string $column): bool
    {
        $allowedColumn = match ($user->role) {
            'teacher' => 'teacher',
            'dean' => 'dean',
            'accounting' => 'accounting',
            'sao' => 'sao',
            default => null,
        };

        if (! $allowedColumn) {
            return false;
        }

        return $column === $allowedColumn && $this->sign($user, $clearance);
    }

    public function deny(User $user, Clearance $clearance): bool
    {
        return $this->sign($user, $clearance);
    }

    public function downloadPdf(User $user, Clearance $clearance): bool
    {
        if (in_array($user->role, ['admin', 'superadmin'], true)) {
            return true;
        }

        return $user->role === 'student' && $clearance->user_id === $user->id;
    }
}
