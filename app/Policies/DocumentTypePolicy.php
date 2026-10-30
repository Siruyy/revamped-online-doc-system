<?php

namespace App\Policies;

use App\Models\DocumentType;
use App\Models\User;

class DocumentTypePolicy
{
    public function viewPrices(User $user): bool
    {
        return in_array($user->role, ['admin', 'superadmin', 'accounting'], true);
    }

    public function updatePrice(User $user, DocumentType $documentType): bool
    {
        return $this->viewPrices($user);
    }
}
