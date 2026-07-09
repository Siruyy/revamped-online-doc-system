<?php

namespace App\Events;

use App\Support\ClearanceSignatories;
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
        return collect(ClearanceSignatories::roles())
            ->map(fn (string $role): PrivateChannel => new PrivateChannel("role.department.{$role}"))
            ->all();
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
