<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ?string $reason = null) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your registration was not approved')
            ->greeting("Hello {$notifiable->fullname},")
            ->line('We are unable to approve your registration at this time.');

        if ($this->reason) {
            $mail->line("Reason: {$this->reason}");
        }

        return $mail->line('You may contact support for guidance and re-apply if needed.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'registration_rejected',
            'reason' => $this->reason,
        ];
    }
}
