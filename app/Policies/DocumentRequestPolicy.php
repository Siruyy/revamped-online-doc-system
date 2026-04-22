<?php

namespace App\Policies;

use App\Models\DocumentRequest;
use App\Models\User;

class DocumentRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'superadmin'], true);
    }

    public function view(User $user, DocumentRequest $documentRequest): bool
    {
        if (in_array($user->role, ['admin', 'superadmin'], true)) {
            return true;
        }

        return $user->role === 'student' && $documentRequest->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'student' && $user->status === 'active';
    }

    public function update(User $user, DocumentRequest $documentRequest): bool
    {
        return $this->cancel($user, $documentRequest);
    }

    public function delete(User $user, DocumentRequest $documentRequest): bool
    {
        return $user->role === 'superadmin';
    }

    public function cancel(User $user, DocumentRequest $documentRequest): bool
    {
        return $user->role === 'student'
            && $documentRequest->user_id === $user->id
            && $documentRequest->status === 'pending';
    }

    public function approve(User $user, DocumentRequest $documentRequest): bool
    {
        return in_array($user->role, ['admin', 'superadmin'], true);
    }

    public function deny(User $user, DocumentRequest $documentRequest): bool
    {
        return in_array($user->role, ['admin', 'superadmin'], true);
    }

    public function updateStage(User $user, DocumentRequest $documentRequest): bool
    {
        return in_array($user->role, ['admin', 'superadmin'], true);
    }
}
