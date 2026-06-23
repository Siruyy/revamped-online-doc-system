<?php

namespace App\Policies;

use App\Models\DocumentRequest;
use App\Models\RequestRequirement;
use App\Models\User;

class RequestRequirementPolicy
{
    public function view(User $user, RequestRequirement $requirement): bool
    {
        if (in_array($user->role, ['admin', 'superadmin'], true)) {
            return true;
        }

        /** @var DocumentRequest|null $documentRequest */
        $documentRequest = $requirement->documentRequest;

        return $user->role === 'student'
            && $documentRequest !== null
            && $documentRequest->user_id === $user->id;
    }
}
