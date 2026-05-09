# Phase 07 — Real-Time And Notifications

> **Goal:** Finish reliable live updates, queued notifications, broadcast notification payloads, email delivery, and manual Reverb verification.

**Status:** Partial. Echo, channels, several events, and fallback polling exist. Notifications, queue/broadcast completion, and manual verification remain.

**Depends on:** Phases 03-06.

**Primary docs:** [`08-real-time.md`](../08-real-time.md), [`12-notifications-and-email.md`](../12-notifications-and-email.md), [`10-security.md`](../10-security.md).

---

## Agent Task 7.1 — Reconcile Existing Realtime Code

**Delegate to:** code-explorer

**Read first:**
- `resources/js/echo.js`
- `resources/js/bootstrap.js`
- `resources/js/Composables/useEchoPrivateChannel.js`
- `resources/js/Composables/useRealtimeOrPoll.js`
- `routes/channels.php`
- `app/Events/*`
- `tests/Feature/Broadcasting/*`

**Steps:**
- [ ] Confirm `user.{id}`, `role.admin`, `role.superadmin`, `role.department.{role}`, and `chat.{conversationKey}` channel behavior.
- [ ] Confirm existing events implement `ShouldBroadcast` or `ShouldBroadcastNow` correctly.
- [ ] List missing event tests by event class.
- [ ] Confirm Vue pages reload only needed partial props on broadcast.

**Acceptance:**
- [ ] This phase has a current event/channel inventory.
- [ ] Missing tests are listed before implementation starts.

## Agent Task 7.2 — Queue All Notifications

**Delegate to:** tdd-workflow + backend-patterns

**Files likely touched:**
- `app/Notifications/*`
- `tests/Feature/Notifications/*`

**Steps:**
- [ ] Add failing tests proving notifications are queued.
- [ ] Update each notification class to implement `Illuminate\Contracts\Queue\ShouldQueue`.
- [ ] Keep `Queueable` trait on each queued notification.
- [ ] Verify notification payloads use only safe IDs, labels, URLs, and status text.
- [ ] Add tests for request, payment, clearance, registration, message, and announcement notifications that already exist.

**Acceptance:**
- [ ] Existing notification classes implement `ShouldQueue`.
- [ ] Tests prove jobs are queued or notification fakes receive expected classes.

## Agent Task 7.3 — Complete Notification Class Catalog

**Delegate to:** backend-patterns + tdd-workflow

**Read first:** [`12-notifications-and-email.md`](../12-notifications-and-email.md)

**Files likely touched:**
- `app/Notifications/RequestStatusChangedNotification.php`
- `app/Notifications/PaymentStatusChangedNotification.php`
- `app/Notifications/ClearanceDepartmentActionNotification.php`
- `app/Notifications/ClearanceCompletedNotification.php`
- `app/Notifications/MessageReceivedNotification.php`
- `app/Notifications/AnnouncementPublishedNotification.php`
- `tests/Feature/Notifications/NotificationCatalogTest.php`

**Steps:**
- [ ] Compare existing notification classes with `docs/12-notifications-and-email.md`.
- [ ] Add missing classes with `via()`, `toDatabase()`, `toMail()` where email is required, and `toBroadcast()` where live bell is required.
- [ ] Ensure database payloads include `type`, `title`, `message`, `url`, and related resource IDs.
- [ ] Ensure mail subject/body is branded and does not expose sensitive files or private paths.
- [ ] Add tests for payload shape and channels per notification.

**Acceptance:**
- [ ] Notification catalog matches docs or explicitly documents deferrals.
- [ ] Bell can render all database payloads consistently.

## Agent Task 7.4 — Wire Events To Notification Side Effects

**Delegate to:** backend-patterns + code-reviewer

**Files likely touched:**
- `app/Providers/EventServiceProvider.php` or Laravel event discovery equivalent
- `app/Listeners/*`
- `app/Services/RequestService.php`
- `app/Services/PaymentService.php`
- `app/Services/ClearanceService.php`
- `tests/Feature/Notifications/NotificationSideEffectTest.php`

**Steps:**
- [ ] Decide whether side effects live in listeners or existing services; prefer listeners for cross-cutting notification side effects.
- [ ] When request is submitted, notify admins.
- [ ] When payment is submitted, notify admins.
- [ ] When request/payment is approved or denied, notify student.
- [ ] When clearance is created, notify relevant department roles.
- [ ] When clearance is completed, notify student.
- [ ] When registration is submitted, notify SuperAdmins.
- [ ] Add tests with `Notification::fake()` for every side effect.

**Acceptance:**
- [ ] Every critical workflow emits the expected notification.
- [ ] Tests fail if a notification hook is removed.

## Agent Task 7.5 — Broadcast Notification Delivery

**Delegate to:** backend-patterns + frontend-patterns

**Files likely touched:**
- `app/Models/User.php`
- `app/Notifications/*`
- `resources/js/Components/NotificationBell.vue`
- `tests/Feature/Notifications/BroadcastNotificationTest.php`

**Steps:**
- [ ] Verify `User::receivesBroadcastNotificationsOn()` returns `user.{id}`.
- [ ] Add `toBroadcast()` to notifications that should update the bell instantly.
- [ ] Ensure `NotificationBell.vue` listens with `.notification()` and reloads `unreadNotificationsCount` only.
- [ ] Add broadcast payload tests.

**Acceptance:**
- [ ] Notification bell badge can update without full page refresh.
- [ ] Broadcast payload contains no private file paths or sensitive metadata.

## Agent Task 7.6 — Queue And Email Verification

**Delegate to:** verify-app + deployment-patterns

**Files likely touched:**
- `.env.example`
- `config/queue.php`
- `config/mail.php`
- `docs/14-deployment.md`

**Steps:**
- [ ] Verify `php artisan queue:work` processes notification jobs locally.
- [ ] Verify failed jobs are stored and visible.
- [ ] Configure local mail capture instructions using Mailpit/Mailhog.
- [ ] Trigger each mail-producing notification and verify delivery locally.
- [ ] Document required production SMTP environment variables.

**Acceptance:**
- [ ] Queue worker path is documented and tested locally.
- [ ] Email setup is reproducible without guessing.

## Agent Task 7.7 — Manual Reverb Verification

**Delegate to:** verify-app

**Commands:**

```bash
php artisan reverb:start
php artisan queue:work
npm run dev
php artisan serve
```

**Checklist:**
- [ ] Browser console can subscribe to a private channel with authenticated user.
- [ ] Student submits request and admin list updates live.
- [ ] Admin approves or denies and student detail updates live.
- [ ] Department signs and student timeline updates live.
- [ ] Notification bell badge updates live.
- [ ] Stop Reverb and verify fallback polling keeps critical pages fresh.

**Acceptance:**
- [ ] Manual verification notes include browser, users, commands, and result.

## Agent Task 7.8 — Phase Verification

**Delegate to:** code-reviewer

**Commands:**

```bash
php artisan test --filter=Broadcast
php artisan test --filter=Notification
php artisan test --filter=Realtime
npm run build
```

**Acceptance:**
- [ ] Event, channel, notification, and build checks pass.
- [ ] Any unverified manual step is documented as a blocker.
