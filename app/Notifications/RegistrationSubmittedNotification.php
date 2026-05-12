<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly User $registeredUser) {}

    /**
     * Get the notification's delivery channels.
     *
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

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New student registration requires approval')
            ->greeting('Hello SuperAdmin,')
            ->line("{$this->registeredUser->fullname} just registered and is waiting for account approval.")
            ->line("Email: {$this->registeredUser->email}")
            ->action('Review Pending Users', route('superadmin.users.pending'))
            ->line('Please approve or reject this account from the SuperAdmin panel.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'registration_submitted',
            'title' => 'New registration submitted',
            'message' => "{$this->registeredUser->fullname} is waiting for account approval.",
            'url' => route('superadmin.users.pending'),
            'user_id' => $this->registeredUser->id,
            'fullname' => $this->registeredUser->fullname,
            'email' => $this->registeredUser->email,
        ];
    }
}
