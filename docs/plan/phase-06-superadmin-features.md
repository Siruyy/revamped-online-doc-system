# Phase 06 — SuperAdmin Features

> **Goal:** SuperAdmin power-user features: full user management, approval workflow, logs, reports, system overrides.

**Subagents:** `tdd-guide`, `code-reviewer`, `security-reviewer` (mass operations).
**Skills:** `tdd-workflow`, `security-review`.
**Depends on:** Phases 02, 04.

---

## 6.1 SuperAdmin Dashboard

- [ ] `SuperAdmin\DashboardController@index` aggregates:
    - User counts by role and status
    - System-wide request and payment stats
    - Pending registrations count
    - Recent activity (last 20 log entries)
- [ ] `Pages/SuperAdmin/Dashboard.vue`.

## 6.2 User Management

- [ ] `SuperAdmin\UserController@index` — paginated, filters: role, status, course, year, search.
- [ ] `Pages/SuperAdmin/Users/Index.vue` with checkbox selection for bulk ops.
- [ ] `@pending` — dedicated page for `status=pending` users.
- [ ] `@approve` (already in Phase 02) — verify works.
- [ ] `@reject` — with reason.
- [ ] `@suspend` / `@reactivate`.
- [ ] `@destroy` (soft delete).
- [ ] `@bulkDelete` — with confirmation modal and "type DELETE to confirm" pattern.
- [ ] `@store` — manually create staff accounts (admin/department) with auto-generated password sent via email.
- [ ] `@edit` / `@update` — edit any user (with care).
- [ ] All actions logged with affected_user_id.

## 6.3 Pending Registrations

- [ ] `Pages/SuperAdmin/Users/Pending.vue` (verify from Phase 02).
- [ ] Bulk approve option.
- [ ] Real-time bell notification (Phase 07).

## 6.4 Logs Viewer

- [ ] `SuperAdmin\LogController@index` — paginated, filters: action, user, date range, search.
- [ ] `Pages/SuperAdmin/Logs.vue` — table with expandable detail row showing metadata.
- [ ] Server-side filtering (don't paginate millions of rows in Vue).

## 6.5 Reports

- [ ] `SuperAdmin\ReportController@index` — request reports, payment reports, clearance reports.
- [ ] Date range + filters.
- [ ] Excel export wired Phase 09.

## 6.6 System-Wide Overrides

- [ ] SuperAdmin can edit/delete document types, announcements, FAQs (reuse Admin controllers, just add to superadmin routes).
- [ ] SuperAdmin can override admin actions (re-approve a denied request, etc.) — implement as needed.

## 6.7 Broadcast Announcements (Stretch)

- [ ] Special "broadcast to all users" mode that sends an in-app notification + email to all active users.
- [ ] Throttled (max 1 per hour) to prevent spam.
- [ ] Activity logged.

## 6.8 Account Security Settings

- [ ] Force password reset on a user (sends email link).
- [ ] View user's recent login activity.
- [ ] Revoke all sessions for a user.

## 6.9 Tests

- [ ] All controller actions tested.
- [ ] Bulk delete edge cases (empty selection, mixed roles, self-deletion blocked).
- [ ] Authorization: only SuperAdmin can access.
- [ ] Coverage 80%+.

---

## Exit Criteria

- ✅ SuperAdmin can fully manage all users, roles, and statuses.
- ✅ Activity logs are searchable and exportable (export in Phase 09).
- ✅ Cannot self-delete or self-suspend.
- ✅ Bulk operations require explicit confirmation.
- ✅ All sensitive actions audit-logged.
