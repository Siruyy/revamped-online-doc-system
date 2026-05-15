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

        $statusColumn = $this->departmentStatusColumn($user);

        if ($statusColumn) {
            return in_array($clearance->overall_status, ['in_progress', 'completed', 'denied'], true)
                && $clearance->{$statusColumn} !== null;
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
        if ($clearance->overall_status !== 'in_progress') {
            return false;
        }

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
        $allowedColumn = $this->departmentColumn($user);

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
        $column = $this->departmentColumn($user);

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
        if ($clearance->overall_status !== 'completed') {
            return false;
        }

        if (in_array($user->role, ['admin', 'superadmin'], true)) {
            return true;
        }

        return $user->role === 'student' && $clearance->user_id === $user->id;
    }

    private function departmentColumn(User $user): ?string
    {
        return match ($user->role) {
            'teacher' => 'teacher',
            'dean' => 'dean',
            'accounting' => 'accounting',
            'sao' => 'sao',
            default => null,
        };
    }

    private function departmentStatusColumn(User $user): ?string
    {
        $column = $this->departmentColumn($user);

        return $column ? "{$column}_status" : null;
    }
}
