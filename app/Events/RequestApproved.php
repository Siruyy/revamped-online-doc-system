<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $documentRequestId, public int $studentId, public int $adminId) {}
}
