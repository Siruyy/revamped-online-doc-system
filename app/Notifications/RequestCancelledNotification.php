<?php

namespace App\Notifications;

use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly DocumentRequest $documentRequest, private readonly User $student) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Student request cancelled')
            ->greeting("Hello {$notifiable->fullname},")
            ->line("{$this->student->fullname} cancelled request {$this->documentRequest->reference_no}.")
            ->line('Please review the queue for any related follow-up actions.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'request_cancelled',
            'message' => "{$this->student->fullname} cancelled request {$this->documentRequest->reference_no}.",
            'document_request_id' => $this->documentRequest->id,
            'student_id' => $this->student->id,
        ];
    }
}
