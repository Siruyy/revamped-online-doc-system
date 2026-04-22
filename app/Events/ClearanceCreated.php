<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClearanceCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $clearanceId,
        public int $studentId,
        public ?int $documentRequestId,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('role.department.teacher'),
            new PrivateChannel('role.department.dean'),
            new PrivateChannel('role.department.accounting'),
            new PrivateChannel('role.department.sao'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'clearance_id' => $this->clearanceId,
            'student_id' => $this->studentId,
            'document_request_id' => $this->documentRequestId,
        ];
    }
}
