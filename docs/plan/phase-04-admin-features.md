# Phase 04 — Admin Features

> **Goal:** Admin workflow: approve/deny requests and payments, manage document types, announcements, FAQs, monitor clearances.

**Subagents:** `tdd-guide`, `code-reviewer`.
**Skills:** `tdd-workflow`, `backend-patterns`.
**Depends on:** Phase 03 (so we have student-created data to act on).
**Can run in parallel with:** Phase 05.

---

## 4.1 Admin Layout

- [ ] `Layouts/StaffLayout.vue` — sidebar (collapsible) + topbar with breadcrumbs.
- [ ] Role-aware sidebar items.
- [ ] Mobile drawer.

## 4.2 Admin Dashboard

- [ ] `Admin\DashboardController@index` returns:
    - Request counts by status
    - Payment counts by status
    - Today's submissions
    - Pending action queue (10 most recent)
- [ ] `Pages/Admin/Dashboard.vue` with stat cards and quick action panels.
- [ ] Charts (optional) — stretch goal, use Chart.js if added.

## 4.3 Requests Management

- [ ] `Admin\RequestController@index` — paginated with filters (status, course, year, document type, date range, search).
- [ ] `Pages/Admin/Requests/Index.vue` with `<DataTable>` and `<FilterBar>`.
- [ ] `@show` — detail page with student info, all linked documents in batch, payment, clearance.
- [ ] `@approve` — `RequestService::approveRequest($request, $admin)`:
    - Update status, processing_stage, approved_by/at
    - Dispatch `RequestApproved` event
- [ ] `@deny` — with required denial reason.
- [ ] `@updateStage` — move to processing / ready_for_pickup / released.
- [ ] Activity logged.
- [ ] Tests: approval, denial, stage transitions, authorization.

## 4.4 Payments Management

- [ ] `Admin\PaymentController@index` — paginated, filter by status.
- [ ] `Pages/Admin/Payments/Index.vue` — table with inline receipt preview link.
- [ ] `@show` (or modal) — full receipt preview, student info, request details.
- [ ] `@approve` — `PaymentService::approve($payment, $admin)`:
    - Update status
    - Initialize clearance row for related student/request
    - Dispatch `PaymentApproved` event
- [ ] `@deny` — with reason.
- [ ] Tests.

## 4.5 Document Types CRUD

- [ ] `Admin\DocumentTypeController` resource.
- [ ] `Pages/Admin/DocumentTypes/Index.vue` — table with inline create/edit modals.
- [ ] Form fields: name, description, category, fee, processing_days, is_active.
- [ ] Soft-disable (set `is_active=false`) instead of delete if there are existing requests referencing the type.
- [ ] Tests.

## 4.6 Announcements

- [ ] `Admin\AnnouncementController` resource.
- [ ] `Pages/Admin/Announcements/Index.vue` — list with create/edit, pin toggle, audience selector.
- [ ] Plain text body for now (rich text v2).
- [ ] `published_at` toggle (draft/published).
- [ ] Tests.

## 4.7 FAQs

- [ ] `Admin\FaqController` resource.
- [ ] `Pages/Admin/Faqs/Index.vue` — list with drag-handle to reorder (`sort_order`).
- [ ] Audience selector (student/staff/all).
- [ ] Tests.

## 4.8 Clearance Monitor (Read-Only)

- [ ] `Admin\ClearanceMonitorController@index` — paginated with filters.
- [ ] `Pages/Admin/Clearances/Index.vue` — table with all 4 dept statuses inline.
- [ ] `@show` — detail view with full audit trail.
- [ ] Cannot edit; admin can only observe.

## 4.9 Reports

- [ ] `Admin\ReportController@index` — date range + filters page.
- [ ] `Pages/Admin/Reports.vue` — report viewer with summary stats.
- [ ] Excel export wired in Phase 09.

## 4.10 Notifications & Messages

- [ ] Verify shared `NotificationController` works for admin.
- [ ] Messages controller wired in Phase 08.

## 4.11 Tests

- [ ] Feature test per controller action.
- [ ] Policy enforcement tests.
- [ ] Coverage 80%+.

---

## Exit Criteria

- ✅ Admin can fully manage the request → payment → release pipeline.
- ✅ CRUD works for document types, announcements, FAQs.
- ✅ Clearance monitor shows accurate live data.
- ✅ All actions logged.
- ✅ Mobile-responsive.
