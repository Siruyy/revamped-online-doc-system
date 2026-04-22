<?php

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function (User $user, string $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('role.admin', function (User $user) {
    return in_array($user->role, ['admin', 'superadmin'], true);
});

Broadcast::channel('role.superadmin', function (User $user) {
    return $user->role === 'superadmin';
});

Broadcast::channel('role.department.{role}', function (User $user, string $role) {
    $departmentRoles = ['teacher', 'dean', 'accounting', 'sao'];

    return in_array($user->role, $departmentRoles, true) && $user->role === $role;
});

/*
| Chat channels are wired in Phase 08. Authorize when a message id is used as
| the channel id and the current user is sender or receiver of that message.
*/
Broadcast::channel('chat.{messageId}', function (User $user, string $messageId) {
    return Message::query()
        ->whereKey((int) $messageId)
        ->where(function ($query) use ($user) {
            $query->where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id);
        })
        ->exists();
});
