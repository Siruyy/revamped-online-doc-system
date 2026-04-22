# Phase 08 — Messaging

> **Goal:** Real-time chat between students and admin/superadmin (and admin↔department).

**Subagents:** `tdd-guide`, `code-reviewer`, `security-reviewer` (file uploads in chat if added).
**Skills:** `backend-patterns`, `frontend-patterns`.
**Depends on:** Phase 07 (Reverb).

---

## 8.1 Conversation Concept

A **conversation** is the pair (sender_id, receiver_id). Identified by deterministic hash or composite key. We'll use `min(id1,id2):max(id1,id2)` as conversation key for channel naming.

- [ ] `MessageService::getConversationKey($a, $b)` returns canonical key.
- [ ] `MessageService::userBelongsToConversation($user, $key)` for channel auth.

## 8.2 Routes & Controllers

- [ ] `MessageController@index` — list conversations (latest message per peer + unread count per peer).
- [ ] `@show($peerId)` — load thread with a specific peer, paginated (load older on scroll).
- [ ] `@store` — send message (validates receiver is allowed for this user's role).
- [ ] `@markRead($peerId)` — mark all from peer as read.
- [ ] `unread_count` endpoint for badge polling fallback.

## 8.3 Authorization Rules

- [ ] Students may message: any admin, superadmin.
- [ ] Admin may message: any student, superadmin, department.
- [ ] Department may message: any admin, superadmin (not students directly per scope).
- [ ] SuperAdmin may message anyone.
- [ ] Implemented in Form Request validation + controller policy check.

## 8.4 Vue UI

- [ ] `Pages/Messages/Index.vue`:
    - Two-pane layout: conversation list (left), active thread (right)
    - Mobile: stack vertically, switch between list and thread
- [ ] `Pages/Messages/Show.vue` (or unified single page).
- [ ] Message bubble component (sender right, receiver left).
- [ ] Timestamp display (relative — "5m ago", "Yesterday").
- [ ] Read receipt indicator.
- [ ] Composer textarea + send button + Enter-to-send.

## 8.5 Real-Time Messaging

- [ ] `MessageSent` event broadcasts on `chat.{conversationKey}`.
- [ ] Both clients subscribe and prepend new messages.
- [ ] Sender's optimistic UI: insert immediately, mark "sending", confirm on broadcast back.
- [ ] Receiver's notification bell increments + sound (optional).
- [ ] `MessageRead` event when receiver focuses thread.

## 8.6 New Conversation Picker

- [ ] "Start new conversation" button → modal with searchable user picker.
- [ ] Picker scoped to allowed-recipient roles per current user.

## 8.7 Optional Features (Scope per client)

- [ ] File attachments in chat (image/PDF, max 5 MB).
- [ ] "Typing..." indicator (broadcast typing event with debounce).
- [ ] Soft-delete own messages.

## 8.8 Tests

- [ ] Send message creates row, broadcasts event.
- [ ] Authorization: student cannot message another student directly.
- [ ] Mark-read updates `read_at`.
- [ ] Channel auth rejects non-participants.
- [ ] Coverage 80%+.

---

## Exit Criteria

- ✅ Two users can chat in real time.
- ✅ Unread count accurate across page reloads.
- ✅ Cross-role authorization enforced.
- ✅ Mobile-friendly layout.
