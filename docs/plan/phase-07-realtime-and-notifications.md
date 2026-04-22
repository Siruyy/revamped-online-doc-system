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

- [x] `resources/js/echo.js` configured (per [`08-real-time.md`](../08-real-time.md)).
- [x] Imported via `resources/js/bootstrap.js` (loaded from `app.js`).
- [ ] Connection works in browser console: `Echo.private('user.1').listen(...)` (manual check with Reverb running).

## 7.3 Channels

- [x] `routes/channels.php` defines: `user.{userId}`, `role.admin`, `role.superadmin`, `role.department.{role}`, `chat.{messageId}` (message-participant auth; refine in Phase 08 if needed).
- [x] Authorization callbacks tested (`tests/Feature/Broadcasting/BroadcastChannelAuthorizationTest.php`).

## 7.4 Events

Implement each event class with `ShouldBroadcast` (or `ShouldBroadcastNow` for chat):

- [x] `RegistrationSubmitted` → `role.superadmin`
- [x] `RegistrationApproved` → `user.{id}`
- [x] `RequestSubmitted` → `role.admin`
- [x] `RequestApproved` / `RequestDenied` → `user.{studentId}`
- [x] `RequestStageUpdated` → `user.{studentId}`
- [x] `PaymentSubmitted` → `role.admin`
- [x] `PaymentApproved` / `PaymentDenied` → `user.{studentId}`
- [x] `ClearanceCreated` → `role.department.teacher`, `dean`, `accounting`, `sao` (each)
- [x] `ClearanceUpdated` → `user.{studentId}` + `role.admin`
- [x] `ClearanceCompleted` → `user.{studentId}`
- [ ] `NotificationCreated` / per-notification `toBroadcast()` where needed (Laravel + `User::receivesBroadcastNotificationsOn()`; bell uses Inertia reload on `.notification()` when notifications use `broadcast` channel)

## 7.5 Notifications

- [ ] Implement all Notification classes from [`12-notifications-and-email.md`](../12-notifications-and-email.md).
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

- [x] `useEchoPrivateChannel` composable (`resources/js/Composables/useEchoPrivateChannel.js`) for private channel subscribe / cleanup (non-`user.*` channels leave on unmount).
- [ ] `useUserChannel` / `useRoleChannel` named helpers (optional refactor on top of composable above).
- [x] `NotificationBell.vue` — Echo `user.{id}` `.notification()` + Inertia reload for shared `unreadNotificationsCount`; notifications link by role; shown on `StudentLayout` and `StaffLayout`.
- [ ] `MessageBell.vue` (UI-only here; messaging features in Phase 08).
- [x] Admin Requests list subscribes to `role.admin` — reloads table on `RequestSubmitted`, `PaymentSubmitted`, `ClearanceUpdated`.
- [x] Student Request detail subscribes to `user.{id}` — partial reload `request` on status / payment / clearance / registration events.
- [x] Department dashboard subscribes to `role.department.{role}` — reload stats/list on `ClearanceCreated` / `ClearanceUpdated`.
- [x] SuperAdmin Pending registrations — `role.superadmin` + `RegistrationSubmitted`.
- [x] Fallback polling (`useRealtimeOrPoll`) when Echo is unavailable.

## 7.8 Queue Worker

- [ ] Verify `php artisan queue:work` processes notification jobs.
- [ ] Test failed-job handling (`failed_jobs` table).
- [ ] Document Supervisor config for production (already in [`14-deployment.md`](../14-deployment.md)).

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
- [x] `Event::fake()` for broadcast assertions (registration, request deny/stage, payment deny, clearance created).
- [x] Channel authorization tests (`BroadcastChannelAuthorizationTest`).

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
