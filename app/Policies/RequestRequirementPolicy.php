<?php

namespace App\Policies;

use App\Models\Clearance;
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

        $requirement->loadMissing('documentRequest.clearances');

        /** @var DocumentRequest|null $documentRequest */
        $documentRequest = $requirement->documentRequest;

        if ($documentRequest === null) {
            return false;
        }

        if ($user->role === 'student') {
            return $documentRequest->user_id === $user->id;
        }

        if (! $user->isDepartment() || $documentRequest->user_id !== null) {
            return false;
        }

        return $documentRequest->clearances->contains(
            fn (Clearance $clearance): bool => $user->can('view', $clearance)
        );
    }
}
