<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestStageUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $documentRequestId,
        public ?int $studentId,
        public string $processingStage,
        public string $status,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        if ($this->studentId === null) {
            return [new PrivateChannel('role.admin')];
        }

        return [new PrivateChannel('user.'.$this->studentId)];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'document_request_id' => $this->documentRequestId,
            'student_id' => $this->studentId,
            'processing_stage' => $this->processingStage,
            'status' => $this->status,
        ];
    }
}
