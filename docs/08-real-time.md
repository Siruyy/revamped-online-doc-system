# 08 — Real-Time (Laravel Reverb)

## Overview

Laravel Reverb is the official first-party WebSocket server for Laravel. It runs as a separate process alongside Laravel, accepts WebSocket connections from browsers (via Laravel Echo + Pusher.js client), and broadcasts events from the server to subscribed clients.

## Why Reverb (vs Pusher / Soketi / Ably)

| | Reverb | Pusher | Soketi | Ably |
|--|--------|--------|--------|------|
| Cost | Free, self-hosted | Paid SaaS | Free, self-hosted | Paid |
| Maintenance | Official Laravel | Third party | Community | Third party |
| Pusher protocol | ✅ | ✅ | ✅ | ✅ |
| Setup difficulty | Low (`php artisan reverb:install`) | Trivial | Medium | Low |

Reverb is the obvious choice for our self-hosted Dokploy setup.

## Architecture

```
Browser ──ws://vps:8080──▶ Reverb (PHP process)
   │
   │ Authenticates via Laravel /broadcasting/auth (uses session)
   │
   ▼
Subscribes to channels: private-user.123, private-role.admin

Laravel App ──event(new RequestSubmitted($request))──▶ Reverb ──▶ Browsers
```

## Channels Used

| Channel | Type | Subscribers | Events |
|---------|------|-------------|--------|
| `user.{userId}` | private | Authenticated staff or legacy authenticated students | Personal notifications, status updates on authenticated records |
| `role.admin` | private | All admins (and superadmin) | New requests, new payments |
| `role.superadmin` | private | SuperAdmins only | Legacy pending registrations, system/user events |
| `role.department.{role}` | private | Officers of that role | New clearances to sign |
| `chat.{conversationId}` | private | Both participants | Messages, typing, read receipts |

## Event Catalog

| Event | Channel | Listener (server) | Vue listener |
|-------|---------|-------------------|--------------|
| `RequestSubmitted` | `role.admin` | Notify admins | Prepend row in admin requests table, increment count |
| `RequestApproved` | email for public requestors; `user.{studentId}` for legacy records | — | Public requestors track by reference; legacy pages update detail |
| `RequestDenied` | email for public requestors; `user.{studentId}` for legacy records | — | Same |
| `RequestStageUpdated` | email for public requestors; `user.{studentId}` for legacy records | — | Public requestors refresh tracking page |
| `PaymentSubmitted` | `role.admin` | — | Update admin payments queue |
| `PaymentApproved` | email for public requestors; `user.{studentId}` for legacy records | Initialize clearance | Public requestors track by reference |
| `PaymentDenied` | email for public requestors; `user.{studentId}` for legacy records | — | Same |
| `ClearanceUpdated` | `role.admin`; email for public requestors when relevant | If completed: generate PDF | Staff queues update |
| `ClearanceCompleted` | email for public requestors; `user.{studentId}` for legacy records | Generate PDF, email | Tracking shows completion |
| `RegistrationSubmitted` | `role.superadmin` | — | Legacy pending registrations list |
| `MessageSent` | `chat.{conversationId}` | — | Append message to thread, bell badge |
| `MessageRead` | `chat.{conversationId}` | — | Mark message as seen |
| `NotificationCreated` | `user.{userId}` | — | Increment bell badge, prepend to dropdown |

## Server-Side Pattern

```php
// app/Events/RequestSubmitted.php
class RequestSubmitted implements ShouldBroadcast
{
    public function __construct(public DocumentRequest $request) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('role.admin')];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->request->id,
            'reference_no' => $this->request->reference_no,
            'requester_name' => $this->request->requester_name ?? $this->request->user?->fullname,
            'document_type' => $this->request->documentType->name,
            'submitted_at' => $this->request->created_at->toIso8601String(),
        ];
    }
}

// In service
event(new RequestSubmitted($request));
```

## Client-Side Pattern (Vue + Echo)

```js
// resources/js/echo.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

```js
// resources/js/Composables/useEcho.js
export function useUserChannel(userId, handlers) {
    const channel = window.Echo.private(`user.${userId}`);

    onMounted(() => {
        Object.entries(handlers).forEach(([event, handler]) => {
            channel.listen(event, handler);
        });
    });

    onUnmounted(() => {
        window.Echo.leave(`user.${userId}`);
    });
}
```

```vue
<!-- Student/Requests/Show.vue -->
<script setup>
import { useUserChannel } from '@/Composables/useEcho';

const props = defineProps({ request: Object, auth: Object });
const requestState = ref(props.request);

useUserChannel(props.auth.user.id, {
    '.request.approved': (e) => {
        if (e.id === requestState.value.id) {
            requestState.value.status = 'approved';
        }
    },
    '.request.stage_updated': (e) => {
        if (e.id === requestState.value.id) {
            requestState.value.processing_stage = e.stage;
        }
    },
});
</script>
```

## Deployment of Reverb

In production, Reverb runs as its own container managed by Supervisor:

```ini
[program:reverb]
command=php /var/www/html/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/log/reverb.log
```

Nginx proxies WebSocket traffic on a subdomain or `/app/` path:

```nginx
location /app/ {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
}
```

## Authentication

Reverb uses Laravel's session for auth. The browser sends its session cookie when subscribing to private channels; Laravel's `BroadcastController` validates the session and runs the channel authorization callback in `channels.php`.

## Failure Handling

- If WebSocket connection drops, Echo auto-reconnects with backoff.
- Vue components should fall back to **polling** every 30 seconds if Echo is disconnected for >60 seconds. This is implemented in a `useRealtimeOrPoll` composable.
- All real-time updates are **enhancements, not requirements** — the user can refresh the page to get the latest state. Never rely on a broadcast to persist state.

## Performance Considerations

- One Reverb process handles thousands of concurrent connections on a 2GB VPS — adequate for school scale.
- Avoid broadcasting large payloads — only send IDs and small fields, let the client refetch detail if needed.
- Use private channels (not presence) unless you actually need presence (we don't, except possibly chat).
