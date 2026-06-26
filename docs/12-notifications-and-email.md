# 12 — Notifications & Email

## Two Notification Channels, One System

Laravel's notifications system unifies in-app notifications and email. One Notification class can be sent via multiple channels:

```php
class RequestApprovedNotification extends Notification implements ShouldQueue
{
    public function via($notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'request.approved',
            'request_id' => $this->request->id,
            'reference_no' => $this->request->reference_no,
            'message' => "Your request for {$this->request->documentType->name} has been approved.",
            'url' => route('track-document'),
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your document request has been approved')
            ->greeting("Hi {$this->request->requester_name},")
            ->line("Your request {$this->request->reference_no} for {$this->request->documentType->name} has been approved.")
            ->action('Track Request', route('track-document'));
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'request.approved',
            'request_id' => $this->request->id,
            'message' => "Request {$this->request->reference_no} approved",
        ]);
    }
}
```

## Channels

| Channel | Purpose | Storage |
|---------|---------|---------|
| `database` | In-app notification bell | `notifications` table |
| `mail` | Email | SMTP via queue |
| `broadcast` | Real-time push | Reverb WebSocket |

Authenticated staff notifications use database and broadcast channels. Public requestors do not have an in-app notification inbox, so they receive email only when they provide an email address and otherwise rely on reference-number tracking.

## Notification Catalog

| Notification | Recipient | Channels | Trigger |
|--------------|-----------|----------|---------|
| `RegistrationSubmittedNotification` | All SuperAdmins | db + mail + broadcast | Legacy self-registration |
| `RegistrationApprovedNotification` | Authenticated student | db + mail | SuperAdmin approves legacy registration |
| `RegistrationRejectedNotification` | Authenticated student | db + mail | SuperAdmin rejects legacy registration |
| `RequestSubmittedNotification` | All Admins/SuperAdmins | db + mail + broadcast | Public requestor submits request |
| `RequestApprovedNotification` | Requestor email if present | mail | Admin approves |
| `RequestDeniedNotification` | Requestor email if present | mail | Admin denies |
| `RequestStageUpdatedNotification` | Requestor email if present | mail | Admin updates stage |
| `RequestReadyForPickupNotification` | Requestor email if present | mail | Stage = ready_for_pickup |
| `RequestReleasedNotification` | Requestor email if present | mail | Stage = released |
| `PaymentSubmittedNotification` | All Admins/SuperAdmins | db + broadcast | Receipt submitted with public request |
| `PaymentApprovedNotification` | Requestor email if present | mail | Admin approves public-request payment |
| `PaymentDeniedNotification` | Requestor email if present | mail | Admin denies public-request payment |
| `ClearanceCreatedNotification` | All Department officers | db + broadcast | Clearance row initialized |
| `ClearanceSignedNotification` | Student + Admins | db + broadcast | Officer signs |
| `ClearanceDeniedNotification` | Student | db + mail + broadcast | Officer denies |
| `ClearanceCompletedNotification` | Student + Admins | db + mail + broadcast | All 4 cleared |
| `MessageReceivedNotification` | Receiver | db + broadcast | New message (no email — too noisy) |
| `AnnouncementPostedNotification` | Audience | db + broadcast | Pinned announcement created |

## Email Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com         # or Brevo, Mailgun, Resend
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@svci.example
MAIL_FROM_NAME="SVCI Document System"

QUEUE_CONNECTION=database
```

All notifications implement `ShouldQueue` so email sending is asynchronous and never blocks the HTTP request.

## Mail Templates

Use Laravel's notification mail template system, customized with brand colors:

```bash
php artisan vendor:publish --tag=laravel-mail
```

Edit `resources/views/vendor/mail/html/themes/default.css`:
- Brand color matches the app (deep navy `#1e3a5f`)
- Logo at the top
- Footer with school address and contact info

## In-App Notifications (Vue)

```vue
<!-- Components/NotificationBell.vue -->
<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useUserChannel } from '@/Composables/useEcho';

const props = defineProps({ initialNotifications: Array, unreadCount: Number });

const notifications = ref(props.initialNotifications);
const count = ref(props.unreadCount);

useUserChannel(props.userId, {
    'notification': (e) => {
        notifications.value.unshift(e);
        count.value++;
    },
});

const markAllRead = () => router.post(route('notifications.read-all'));
</script>
```

## Notifications Index Page

Lists all notifications, paginated, with filters (read/unread, type). Clicking navigates to the related entity.

## Email Throttling

To avoid mail provider rate limits, configure queue rate limiting:

```php
RateLimiter::for('mail', fn () => Limit::perMinute(60));
```

Apply to mail queue jobs.

## Failed Notifications

Failed jobs go to `failed_jobs`. Monitor via:

```bash
php artisan queue:failed
php artisan queue:retry all
```

Set up a cron to alert if `failed_jobs` count exceeds a threshold.

## Notification Preferences (Future)

For v2, allow users to opt out of specific email notifications:

```
[ ] Email me when my request status changes
[x] Email me when my payment is approved
[ ] Email me about new announcements
```

Stored in a `notification_preferences` JSON column on `users`.

## Anti-Spam

- All emails go through configured SMTP, not direct mail.
- SPF, DKIM, DMARC configured on the sending domain (when domain is set up).
- Footer includes "Sent by SVCI Document System" identifier.
- Reply-To address set to a real monitored mailbox or `noreply` clearly labeled.
