# Phase 06 — SuperAdmin Closeout

> **Goal:** Verify and close gaps in SuperAdmin user management, reports, logs, route coverage, and sensitive-action security.

**Status:** Active closeout. Core MVP is implemented and covered for current user, log, report, and CSV export workflows. Remaining gaps are explicit SuperAdmin aliases for some admin-managed resources from older route docs.

**Depends on:** Phase 02, Phase 04.

**Primary docs:** [`03-roles-and-permissions.md`](../03-roles-and-permissions.md), [`07-routes-and-controllers.md`](../07-routes-and-controllers.md), [`10-security.md`](../10-security.md).

---

## Agent Task 6.1 — Reconcile SuperAdmin Implementation

**Delegate to:** code-explorer + security-review

**Read first:**
- `routes/superadmin.php`
- `app/Http/Controllers/SuperAdmin/*`
- `resources/js/Pages/SuperAdmin/*`
- `app/Policies/UserPolicy.php`
- `tests/Feature/SuperAdmin/*`

**Steps:**
- [x] Confirm dashboard, users, pending users, approve/reject/suspend/reactivate/delete/bulk delete, staff creation, logs, reports, profile, and notifications exist.
- [x] Confirm all sensitive actions use policies and audit logging.
- [x] Confirm self-delete and self-suspend are blocked.
- [x] List missing docs-required routes from `docs/07-routes-and-controllers.md`.
- [x] Update Phase Notes with explicit deferrals.

**Acceptance:**
- [x] SuperAdmin plan reflects actual code state.
- [x] Each missing item maps to a task below.

## Agent Task 6.2 — User Management Closeout

**Delegate to:** tdd-workflow + backend-patterns

**Files likely touched:**
- `app/Http/Controllers/SuperAdmin/UserController.php`
- `app/Http/Requests/SuperAdmin/*UserRequest.php`
- `resources/js/Pages/SuperAdmin/Users/Index.vue`
- `resources/js/Pages/SuperAdmin/Users/Pending.vue`
- `tests/Feature/SuperAdmin/UserManagementTest.php`

**Steps:**
- [x] Add tests for approve, reject, suspend, reactivate, soft delete, bulk approve, and bulk delete.
- [ ] Add tests for empty bulk selection and mixed-status selections.
- [x] Add tests for self-delete blocked and self-suspend blocked.
- [x] Verify filters: role, status, course, year, and search.
- [x] Verify staff creation sends reset/setup email and never exposes generated password in logs.
- [x] Verify every current user-management action writes `activity_logs` with actor and affected user.

**Acceptance:**
- [x] User management actions are covered by feature tests.
- [x] Sensitive operations require explicit confirmation where destructive.

## Agent Task 6.3 — SuperAdmin Route Coverage

**Delegate to:** backend-patterns + frontend-patterns

**Files likely touched:**
- `routes/superadmin.php`
- `app/Http/Controllers/SuperAdmin/ReportController.php`
- `app/Http/Controllers/Admin/DocumentTypeController.php`
- `app/Http/Controllers/Admin/AnnouncementController.php`
- `app/Http/Controllers/Admin/FaqController.php`

**Steps:**
- [ ] Add or verify SuperAdmin request overview route if docs require it.
- [ ] Add or verify SuperAdmin can access document type, announcement, and FAQ CRUD using shared admin controllers or explicit SuperAdmin controllers.
- [ ] Ensure route names do not collide with admin route names.
- [ ] Ensure menus expose only implemented routes.
- [ ] Add route authorization tests for each added route.

**Acceptance:**
- [ ] SuperAdmin has documented system-wide visibility and management access.
- [ ] Admin-only middleware does not accidentally block SuperAdmin where docs require access.

## Agent Task 6.4 — Logs And Reports Closeout

**Delegate to:** backend-patterns + code-reviewer

**Files likely touched:**
- `app/Http/Controllers/SuperAdmin/LogController.php`
- `app/Http/Controllers/SuperAdmin/ReportController.php`
- `resources/js/Pages/SuperAdmin/Logs.vue`
- `resources/js/Pages/SuperAdmin/Reports.vue`
- `tests/Feature/SuperAdmin/LogsAndReportsTest.php`

**Steps:**
- [x] Verify logs are server-side paginated.
- [x] Verify filters: action, actor, affected user, date range, and search.
- [x] Verify report page supports request, payment, and clearance summaries.
- [x] Ensure export buttons route to Phase 09 endpoints or are hidden until Phase 09 is complete.

**Acceptance:**
- [x] Large log tables do not load fully into Vue.
- [x] Export functionality is not presented as working until Phase 09 lands.

## Agent Task 6.5 — SuperAdmin Security Review

**Delegate to:** security-review

**Commands:**

```bash
php artisan test --filter=SuperAdmin
php artisan route:list --path=superadmin
```

**Checklist:**
- [ ] Only SuperAdmin can access `/superadmin/*`.
- [ ] SuperAdmin `Gate::before` exists and is tested, or policy behavior is explicitly tested.
- [ ] Mass actions are CSRF-protected and use non-GET verbs.
- [ ] No hardcoded passwords or reset links are logged.
- [ ] Activity logs avoid storing sensitive tokens.

**Acceptance:**
- [ ] No CRITICAL/HIGH SuperAdmin security finding remains.

## Phase Notes

- CSV exports for SuperAdmin users and activity logs exist; XLSX exports remain deferred to Phase 09.
- Current SuperAdmin routes cover dashboard, users, pending users, logs, reports, notifications, and profile. Older docs-required aliases for `/superadmin/requests`, `/superadmin/reports/export`, `/superadmin/announcements`, `/superadmin/faqs`, and `/superadmin/document-types` remain deferred until shared admin-resource access is designed.
- Broadcast announcements are stretch unless client explicitly asks.
- Session revoke is stretch unless security requirements change.
- Do not move this phase to `finished/` until SuperAdmin route coverage and final security review pass.
