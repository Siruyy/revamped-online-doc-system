# Phase 04 — Admin Features

> **Goal:** Admin workflow: approve/deny requests and payments, manage document types, announcements, FAQs, monitor clearances.

**Subagents:** `tdd-guide`, `code-reviewer`.
**Skills:** `tdd-workflow`, `backend-patterns`.
**Depends on:** Phase 03 (so we have student-created data to act on).
**Can run in parallel with:** Phase 05.

---

## 4.1 Admin Layout

- [x] `Layouts/StaffLayout.vue` — sidebar (collapsible) + topbar with breadcrumbs.
- [x] Role-aware sidebar items.
- [x] Mobile drawer.

## 4.2 Admin Dashboard

- [x] `Admin\DashboardController@index` returns:
    - Request counts by status
    - Payment counts by status
    - Today's submissions
    - Pending action queue (10 most recent)
- [x] `Pages/Admin/Dashboard.vue` with stat cards and quick action panels.
- [ ] Charts (optional) — stretch goal, use Chart.js if added.

## 4.3 Requests Management

- [x] `Admin\RequestController@index` — paginated with filters (status, course, year, document type, date range, search).
- [x] `Pages/Admin/Requests/Index.vue` with `<DataTable>` and `<FilterBar>`.
- [x] `@show` — detail page with student info, all linked documents in batch, payment, clearance.
- [x] `@approve` — `RequestService::approveRequest($request, $admin)`:
    - Update status, processing_stage, approved_by/at
    - Dispatch `RequestApproved` event
- [x] `@deny` — with required denial reason.
- [x] `@updateStage` — move to processing / ready_for_pickup / released.
- [x] Activity logged.
- [x] Tests: approval, denial, stage transitions, authorization.

## 4.4 Payments Management

- [x] `Admin\PaymentController@index` — paginated, filter by status.
- [x] `Pages/Admin/Payments/Index.vue` — table with inline receipt preview link.
- [x] `@show` (or modal) — full receipt preview, student info, request details.
- [x] `@approve` — `PaymentService::approve($payment, $admin)`:
    - Update status
    - Initialize clearance row for related student/request
    - Dispatch `PaymentApproved` event
- [x] `@deny` — with reason.
- [x] Tests.

## 4.5 Document Types CRUD

- [x] `Admin\DocumentTypeController` resource.
- [x] `Pages/Admin/DocumentTypes/Index.vue` — table with inline create/edit modals.
- [x] Form fields: name, description, category, fee, processing_days, is_active.
- [x] Soft-disable (set `is_active=false`) instead of delete if there are existing requests referencing the type.
- [x] Tests.

## 4.6 Announcements

- [x] `Admin\AnnouncementController` resource.
- [x] `Pages/Admin/Announcements/Index.vue` — list with create/edit, pin toggle, audience selector.
- [x] Plain text body for now (rich text v2).
- [x] `published_at` toggle (draft/published).
- [x] Tests.

## 4.7 FAQs

- [x] `Admin\FaqController` resource.
- [x] `Pages/Admin/Faqs/Index.vue` — list with drag-handle to reorder (`sort_order`).
- [x] Audience selector (student/staff/all).
- [x] Tests.

## 4.8 Clearance Monitor (Read-Only)

- [x] `Admin\ClearanceMonitorController@index` — paginated with filters.
- [x] `Pages/Admin/Clearances/Index.vue` — table with all 4 dept statuses inline.
- [x] `@show` — detail view with full audit trail.
- [x] Cannot edit; admin can only observe.

## 4.9 Reports

- [x] `Admin\ReportController@index` — date range + filters page.
- [x] `Pages/Admin/Reports.vue` — report viewer with summary stats.
- [ ] Excel export wired in Phase 09.

## 4.10 Notifications & Messages

- [x] Verify shared `NotificationController` works for admin.
- [ ] Messages controller wired in Phase 08.

## 4.11 Tests

- [x] Feature test per controller action.
- [x] Policy enforcement tests.
- [ ] Coverage 80%+.

### Phase 04 Notes

- `Messages controller wired in Phase 08` remains intentionally deferred.
- `Excel export wired in Phase 09` remains intentionally deferred.
- Charting remains an optional stretch item and is not part of this phase completion.

---

## Exit Criteria

- ✅ Admin can fully manage the request → payment → release pipeline.
- ✅ CRUD works for document types, announcements, FAQs.
- ✅ Clearance monitor shows accurate live data.
- ✅ All actions logged.
- ✅ Mobile-responsive.
