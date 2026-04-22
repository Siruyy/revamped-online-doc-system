<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'superadmin';
    }

    public function view(User $user, User $model): bool
    {
        return $user->role === 'superadmin' || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'superadmin';
    }

    public function update(User $user, User $model): bool
    {
        return $user->role === 'superadmin' || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->role === 'superadmin' && $user->id !== $model->id;
    }

    public function approve(User $user, User $model): bool
    {
        return $user->role === 'superadmin'
            && $model->role === 'student'
            && $model->status === 'pending';
    }

    public function reject(User $user, User $model): bool
    {
        return $user->role === 'superadmin'
            && $model->role === 'student'
            && $model->status === 'pending';
    }

    public function suspend(User $user, User $model): bool
    {
        return $user->role === 'superadmin' && $user->id !== $model->id;
    }
}
