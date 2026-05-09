# Phase 08 — Messaging

> **Goal:** Add authorized real-time messaging between allowed roles with unread counts, conversation views, and broadcast delivery.

**Status:** Not started. `Message` model exists, but controller, service, routes, pages, events, and unread flow are missing.

**Depends on:** Phase 07.

**Primary docs:** [`05-features.md`](../05-features.md), [`07-routes-and-controllers.md`](../07-routes-and-controllers.md), [`08-real-time.md`](../08-real-time.md), [`10-security.md`](../10-security.md).

---

## Agent Task 8.1 — Messaging Scope Decision

**Delegate to:** architect

**Steps:**
- [ ] Confirm v1 includes messaging. If not, mark Phase 08 deferred in `docs/plan/README.md` and hide all message UI affordances.
- [ ] Confirm allowed conversations: student↔admin/superadmin, admin↔student/superadmin/department, department↔admin/superadmin, superadmin↔anyone.
- [ ] Confirm attachments are out of v1 unless client explicitly asks.

**Acceptance:**
- [ ] Messaging is either explicitly in scope or explicitly deferred.

## Agent Task 8.2 — Message Service And Authorization Rules

**Delegate to:** tdd-workflow + backend-patterns

**Files likely touched:**
- `app/Services/MessageService.php`
- `app/Policies/MessagePolicy.php`
- `app/Http/Requests/Messages/StoreMessageRequest.php`
- `tests/Unit/MessageServiceTest.php`
- `tests/Feature/Messages/MessageAuthorizationTest.php`

**Steps:**
- [ ] Add failing tests for canonical conversation key using `min(id):max(id)`.
- [ ] Add failing tests for allowed and denied role pairings.
- [ ] Implement `getConversationKey(User $a, User $b): string`.
- [ ] Implement `userBelongsToConversation(User $user, string $key): bool`.
- [ ] Implement `canMessage(User $sender, User $receiver): bool`.
- [ ] Validate receiver exists, is active, and is allowed for sender role.

**Acceptance:**
- [ ] Student cannot message another student.
- [ ] Department cannot message students directly.
- [ ] SuperAdmin can message anyone active.

## Agent Task 8.3 — Message Routes And Controller

**Delegate to:** backend-patterns + tdd-workflow

**Files likely touched:**
- `routes/student.php`
- `routes/admin.php`
- `routes/department.php`
- `routes/superadmin.php`
- `app/Http/Controllers/MessageController.php`
- `tests/Feature/Messages/MessageControllerTest.php`

**Steps:**
- [ ] Add shared authenticated message routes under each role prefix.
- [ ] Implement `index` to list latest conversation per peer with unread counts.
- [ ] Implement `show(User $peer)` to return paginated messages newest-last.
- [ ] Implement `store` to create message, broadcast event, and notify receiver.
- [ ] Implement `markRead(User $peer)` to mark receiver-visible messages as read.
- [ ] Implement unread-count endpoint for polling fallback.

**Acceptance:**
- [ ] Routes respect current role middleware.
- [ ] Users cannot fetch or mark unrelated conversations.

## Agent Task 8.4 — Broadcast Events And Channel Auth

**Delegate to:** backend-patterns

**Files likely touched:**
- `app/Events/MessageSent.php`
- `app/Events/MessageRead.php`
- `routes/channels.php`
- `tests/Feature/Broadcasting/ChatChannelAuthorizationTest.php`

**Steps:**
- [ ] Add `MessageSent` broadcasting on `chat.{conversationKey}`.
- [ ] Add `MessageRead` broadcasting on `chat.{conversationKey}`.
- [ ] Ensure channel auth allows only conversation participants.
- [ ] Ensure event payload includes id, sender id, receiver id, body, read state, created timestamp.
- [ ] Exclude email, contact number, private paths, and session data from payload.

**Acceptance:**
- [ ] Non-participants cannot subscribe to chat channels.
- [ ] Broadcast payload is minimal and safe.

## Agent Task 8.5 — Message UI

**Delegate to:** frontend-patterns + ui-ux-pro-max

**Files likely touched:**
- `resources/js/Pages/Messages/Index.vue`
- `resources/js/Components/Messages/ConversationList.vue`
- `resources/js/Components/Messages/MessageThread.vue`
- `resources/js/Components/Messages/MessageComposer.vue`
- `resources/js/Components/MessageBell.vue`

**Steps:**
- [ ] Build two-pane desktop layout: conversation list left, active thread right.
- [ ] Build mobile layout: list and thread stack with back navigation.
- [ ] Add message bubbles with sender-right and receiver-left alignment.
- [ ] Add timestamp display and read receipt state.
- [ ] Add composer textarea with Enter-to-send and Shift+Enter newline.
- [ ] Disable send while request is pending, then reconcile optimistic message with server response.

**Acceptance:**
- [ ] Messaging works on 375px, 768px, and desktop widths.
- [ ] Keyboard users can navigate list, thread, and composer.

## Agent Task 8.6 — New Conversation Picker

**Delegate to:** frontend-patterns + backend-patterns

**Files likely touched:**
- `app/Http/Controllers/MessageRecipientController.php`
- `resources/js/Components/Messages/NewConversationModal.vue`
- `tests/Feature/Messages/MessageRecipientTest.php`

**Steps:**
- [ ] Add recipient search endpoint scoped to allowed roles.
- [ ] Exclude inactive, pending, suspended, and rejected users.
- [ ] Add modal with search input and role/status labels.
- [ ] Start conversation by navigating to selected peer thread.

**Acceptance:**
- [ ] Users cannot discover disallowed recipients through search.

## Agent Task 8.7 — Messaging Verification

**Delegate to:** code-reviewer

**Commands:**

```bash
php artisan test --filter=Message
php artisan test --filter=ChatChannel
npm run build
```

**Acceptance:**
- [ ] Two allowed users can chat in real time.
- [ ] Unread count is accurate after reload.
- [ ] Cross-role restrictions are covered by tests.
- [ ] Attachments remain deferred unless separate security-reviewed task exists.
