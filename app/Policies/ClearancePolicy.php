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

        // Department officers may view any clearance (sign actions remain scoped per column).
        if (in_array($user->role, ['teacher', 'dean', 'accounting', 'sao'], true)) {
            return true;
        }

        return false;
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
            'teacher' => $clearance->teacher_status === 'pending',
            'dean' => $clearance->dean_status === 'pending',
            'accounting' => $clearance->accounting_status === 'pending',
            'sao' => $clearance->sao_status === 'pending',
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

    /**
     * Authorize signing the column that matches the current user's department role.
     * (Gate only receives the clearance model from {@see Controller::authorize}.)
     */
    public function signOwnDepartment(User $user, Clearance $clearance): bool
    {
        $column = match ($user->role) {
            'teacher' => 'teacher',
            'dean' => 'dean',
            'accounting' => 'accounting',
            'sao' => 'sao',
            default => null,
        };

        if (! $column) {
            return false;
        }

        return $this->signFor($user, $clearance, $column);
    }

    /**
     * Department officer denies their department section (remarks required in request).
     * Named `rejectDepartment` because `deny` collides with Laravel's authorization helper naming.
     */
    public function rejectDepartment(User $user, Clearance $clearance): bool
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
