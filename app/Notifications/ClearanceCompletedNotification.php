<?php

namespace App\Notifications;

use App\Models\Clearance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClearanceCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Clearance $clearance) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your clearance is complete')
            ->line('All departments have cleared your clearance request.')
            ->line('You can download your clearance certificate from the student portal when ready.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'clearance_completed',
            'title' => 'Clearance complete',
            'message' => 'Your clearance has been completed. You may download your PDF from the clearance page.',
            'clearance_id' => $this->clearance->id,
        ];
    }
}
