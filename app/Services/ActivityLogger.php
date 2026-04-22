<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public static function log(
        string $action,
        string $description,
        ?User $actor = null,
        ?User $affectedUser = null,
        ?array $metadata = null
    ): void {
        ActivityLog::create([
            'user_id' => $actor?->id,
            'affected_user_id' => $affectedUser?->id,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
