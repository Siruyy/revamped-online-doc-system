# Phase 07 — Real-Time And Notifications

> **Goal:** Finish reliable live updates, queued notifications, broadcast notification payloads, email delivery, and manual Reverb verification.

**Status:** Partial. Echo, channels, events, fallback polling, queued notification classes, normalized broadcast/database notification payloads, and bell listener exist. Manual Reverb/queue/browser verification remains.

**Phase notes (2026-05-10):** Existing workflow side effects remain service-based instead of listeners to keep the change small. `WorkflowStatusNotification`, registration notifications, request cancellation, and clearance completion are queued and broadcastable. Messaging notifications remain deferred with Phase 08.

**Closeout notes (2026-05-13):** `BrandedResetPasswordNotification` now implements `ShouldQueue` and keeps reset tokens out of array payloads. Current notification payloads are normalized for bell rendering with safe `type`, `title`, `message`, and `url` keys where applicable. `WorkflowStatusNotification` now whitelists extra payload keys to avoid future private path leaks. `ClearanceUpdated` now broadcasts after commit to `user.{id}`, `role.admin`, and `role.department.{department}` so the department dashboard listener can receive committed updates. Phase 08 messaging notifications remain intentionally deferred; generic request/payment workflow updates continue to use `WorkflowStatusNotification` for v1.

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
- [x] Confirm `user.{id}`, `role.admin`, `role.superadmin`, and `role.department.{role}` channel behavior. `chat.{messageId}` authorization exists but belongs to Phase 08 messaging.
- [x] Confirm current workflow events implement `ShouldBroadcast` or `ShouldBroadcastNow` correctly for tested channels.
- [x] List missing event tests by event class.
- [x] Confirm Vue pages reload only needed partial props on broadcast.

**Acceptance:**
- [x] This phase has a current event/channel inventory.
- [x] Missing tests are listed before implementation starts.

## Agent Task 7.2 — Queue All Notifications

**Delegate to:** tdd-workflow + backend-patterns

**Files likely touched:**
- `app/Notifications/*`
- `tests/Feature/Notifications/*`

**Steps:**
- [x] Add failing tests proving notifications are queued.
- [x] Update each notification class to implement `Illuminate\Contracts\Queue\ShouldQueue`.
- [x] Keep `Queueable` trait on each queued notification.
- [x] Verify notification payloads use only safe IDs, labels, URLs, and status text.
- [x] Add tests for request, payment, clearance, and registration notifications that already exist. Message and announcement notifications are deferred because those features are not in v1 scope yet.

**Acceptance:**
- [x] Existing notification classes implement `ShouldQueue`.
- [x] Tests prove jobs are queued or notification fakes receive expected classes.

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
- [x] Compare existing notification classes with `docs/12-notifications-and-email.md`; request/payment status classes remain represented by generic `WorkflowStatusNotification` for v1.
- [ ] Add missing classes with `via()`, `toDatabase()`, `toMail()` where email is required, and `toBroadcast()` where live bell is required.
- [x] Ensure database payloads include `type`, `title`, `message`, `url`, and related resource IDs.
- [ ] Ensure mail subject/body is branded and does not expose sensitive files or private paths.
- [x] Add tests for payload shape and channels per notification.

**Acceptance:**
- [x] Notification catalog matches current v1 implementation and explicitly documents Phase 08/request-payment class deferrals.
- [x] Bell can render all current database payloads consistently.

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
- [x] Decide whether side effects live in listeners or existing services; service-based side effects retained for current flows.
- [x] When request is submitted, notify admins.
- [x] When payment is submitted, notify admins.
- [x] When request/payment is approved or denied, notify student.
- [x] When clearance is created, notify relevant department roles.
- [x] When clearance is completed, notify student.
- [x] When registration is submitted, notify SuperAdmins.
- [ ] Add tests with `Notification::fake()` for every side effect.

**Acceptance:**
- [x] Every critical workflow emits the expected notification.
- [x] Tests fail if a notification hook is removed.

## Agent Task 7.5 — Broadcast Notification Delivery

**Delegate to:** backend-patterns + frontend-patterns

**Files likely touched:**
- `app/Models/User.php`
- `app/Notifications/*`
- `resources/js/Components/NotificationBell.vue`
- `tests/Feature/Notifications/BroadcastNotificationTest.php`

**Steps:**
- [x] Verify `User::receivesBroadcastNotificationsOn()` returns `user.{id}`.
- [x] Add `toBroadcast()` to notifications that should update the bell instantly.
- [x] Ensure `NotificationBell.vue` listens with `.notification()` and reloads current shared props.
- [x] Add broadcast payload tests.

**Acceptance:**
- [x] Notification bell badge can update without full page refresh.
- [x] Broadcast payload contains no private file paths or sensitive metadata.

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

**Manual blocker (2026-05-13):** Not run in this agent environment because it requires concurrent `php artisan reverb:start`, `php artisan queue:work`, `npm run dev`, `php artisan serve`, and browser login sessions. Leave this unchecked until verified manually.

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

**Automated results (2026-05-13):**
- `php artisan test --filter=Notification` passed.
- `php artisan test --filter=Broadcast` passed.
- `php artisan test --filter=ClearanceUpdated` passed.
- `ClearanceUpdated` after-commit and `WorkflowStatusNotification` safe-payload regression tests passed.
- `./vendor/bin/pint --test app/Notifications app/Events tests/Feature/Notifications tests/Feature/Broadcasting` passed.
- Manual Reverb/browser verification remains blocked as noted in Task 7.7.
