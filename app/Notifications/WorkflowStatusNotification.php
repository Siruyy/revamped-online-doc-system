<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class WorkflowStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private readonly array $data) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $extra = array_intersect_key($this->data, array_flip([
            'action',
            'clearance_id',
            'department',
            'document_request_id',
            'overall_status',
            'payment_id',
            'processing_stage',
            'reason',
            'status',
            'student_id',
        ]));

        return [
            'type' => $this->stringOrDefault($this->data['type'] ?? null, 'workflow_status'),
            'title' => $this->stringOrDefault($this->data['title'] ?? null, 'Workflow update'),
            'message' => $this->stringOrDefault($this->data['message'] ?? null, 'Your workflow status was updated.'),
            'url' => is_string($this->data['url'] ?? null) ? $this->data['url'] : null,
            ...$extra,
        ];
    }

    private function stringOrDefault(mixed $value, string $default): string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $default;
    }
}
