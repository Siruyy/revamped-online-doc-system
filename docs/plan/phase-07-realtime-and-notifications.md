# Phase 07 — Real-Time (Reverb) & Notifications

> **Goal:** Live updates across the system — notification bell, request lists, status changes — without page refresh. Email notifications via queue.

**Subagents:** `tdd-guide`, `code-reviewer`, `architect` (event design review).
**Skills:** `backend-patterns`.
**Depends on:** Phases 03–06.

---

## 7.1 Reverb Configuration

- [ ] Verify `php artisan reverb:start` runs locally.
- [ ] `.env` configured with Reverb keys.
- [ ] `BROADCAST_CONNECTION=reverb`.

## 7.2 Echo Client Setup

- [ ] `resources/js/echo.js` configured (per [`docs/08-real-time.md`](../docs/08-real-time.md)).
- [ ] Imported in `app.js`.
- [ ] Connection works in browser console: `Echo.private('user.1').listen(...)`.

## 7.3 Channels

- [ ] `routes/channels.php` defines: `user.{userId}`, `role.admin`, `role.superadmin`, `role.department.{role}`, `chat.{conversationId}`.
- [ ] Authorization callbacks tested.

## 7.4 Events

Implement each event class with `ShouldBroadcast` (or `ShouldBroadcastNow` for chat):

- [ ] `RegistrationSubmitted` → `role.superadmin`
- [ ] `RegistrationApproved` → `user.{id}`
- [ ] `RequestSubmitted` → `role.admin`
- [ ] `RequestApproved` / `RequestDenied` → `user.{studentId}`
- [ ] `RequestStageUpdated` → `user.{studentId}`
- [ ] `PaymentSubmitted` → `role.admin`
- [ ] `PaymentApproved` / `PaymentDenied` → `user.{studentId}`
- [ ] `ClearanceCreated` → `role.department.teacher`, `dean`, `accounting`, `sao` (each)
- [ ] `ClearanceUpdated` → `user.{studentId}` + `role.admin`
- [ ] `ClearanceCompleted` → `user.{studentId}`
- [ ] `NotificationCreated` → `user.{userId}` (sent automatically by Laravel notifications)

## 7.5 Notifications

- [ ] Implement all Notification classes from [`docs/12-notifications-and-email.md`](../docs/12-notifications-and-email.md).
- [ ] Each implements `ShouldQueue`.
- [ ] Each defines `via()`, `toDatabase()`, `toMail()`, `toBroadcast()` as appropriate.
- [ ] Mail templates customized with brand styling.

## 7.6 Listeners / Service Hooks

Wire events to side effects in services or dedicated listeners:

- [ ] When `RequestSubmitted` fires → notify all admins.
- [ ] When `PaymentApproved` fires → initialize clearance + notify student.
- [ ] When `ClearanceCompleted` fires → generate PDF + notify student.
- [ ] When `RegistrationSubmitted` fires → notify all SuperAdmins.

## 7.7 Vue Real-Time Integration

- [ ] `useEcho` composable for subscribing/unsubscribing.
- [ ] `useUserChannel` helper.
- [ ] `useRoleChannel` helper.
- [ ] `NotificationBell.vue` subscribes to user channel, prepends new notifications, increments badge.
- [ ] `MessageBell.vue` (UI-only here; messaging features in Phase 08).
- [ ] Admin Requests list subscribes to `role.admin`, prepends new rows.
- [ ] Student Request detail subscribes to user channel, updates timeline live.
- [ ] Department dashboard subscribes to `role.department.{role}`, updates pending list.
- [ ] Fallback polling composable (`useRealtimeOrPoll`) for when WebSocket unavailable.

## 7.8 Queue Worker

- [ ] Verify `php artisan queue:work` processes notification jobs.
- [ ] Test failed-job handling (`failed_jobs` table).
- [ ] Document Supervisor config for production (already in [`docs/14-deployment.md`](../docs/14-deployment.md)).

## 7.9 Email Configuration (Local)

- [ ] Mailhog (or similar) container running.
- [ ] `.env` points to Mailhog.
- [ ] Trigger each notification, verify email arrives.

## 7.10 Email Configuration (Staging/Prod)

- [ ] Choose SMTP provider (Brevo / Mailgun / Gmail SMTP / Resend).
- [ ] Add credentials to environment.
- [ ] Send test email.

## 7.11 Tests

- [ ] `Notification::fake()` based tests for every notifiable action.
- [ ] `Event::fake()` for broadcast assertions.
- [ ] Channel authorization tests.

## 7.12 Manual Verification

- [ ] Open two browser windows (student + admin).
- [ ] Student submits request → admin sees row appear live without refresh.
- [ ] Admin approves → student sees status update live.
- [ ] Department signs → student timeline updates live.
- [ ] Notification bell badge updates without refresh.

---

## Exit Criteria

- ✅ Real-time updates work end-to-end across all critical flows.
- ✅ Notifications stored in DB, broadcast via Reverb, and emailed (where configured).
- ✅ Queue worker processes async jobs reliably.
- ✅ WebSocket connection failure gracefully falls back to polling.
- ✅ Coverage 80%+ on event/notification code.
