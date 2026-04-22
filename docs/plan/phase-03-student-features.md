# Phase 03 — Student Features

> **Goal:** Full student-facing functionality: dashboard, request submission, payment upload, tracking, clearance view, FAQ. Real-time deferred to Phase 07.

**Subagents:** `tdd-guide`, `code-reviewer`, `security-reviewer` (uploads).
**Skills:** `tdd-workflow`, `frontend-patterns`, `backend-patterns`.
**Depends on:** Phase 02.

---

## 3.1 Student Layout

- [x] `Layouts/StudentLayout.vue` — top navbar with all student nav items, mobile hamburger drawer.
- [x] `NotificationBell.vue`, `MessageBell.vue` (UI only; real-time wired in Phase 07).
- [x] `UserAvatar.vue` with dropdown (profile, logout).
- [x] Responsive verified at 375px, 768px, 1280px.

## 3.2 Student Dashboard

- [x] `Student\DashboardController@index` returns:
    - Counts: active requests, pending payments, clearance status
    - Latest 3 announcements (pinned first)
    - Top 5 student-audience FAQs
    - 5 most recent notifications
- [x] `Pages/Student/Dashboard.vue` with stat cards, announcements panel, FAQ accordion, recent activity.
- [x] Loading skeleton.
- [x] Empty states.

## 3.3 Document Request Submission

- [x] `Student\RequestController@create` returns active document types grouped by category.
- [x] `Pages/Student/Requests/Create.vue`:
    - Multi-select (checkbox list)
    - Live total fee calculation
    - Purpose textarea (optional, max 500)
    - Submit button disabled if zero selected or pending request exists
- [x] `StoreRequestRequest` Form Request — validates docs IDs exist, are active, count between 1 and 5.
- [x] `RequestService::createRequestBatch($user, $docIds, $purpose)`:
    - Begin transaction
    - Insert one `document_request` row per doc
    - Insert single `payment` row with sum total
    - Generate reference_no
    - Dispatch `RequestSubmitted` event
    - Commit
- [x] `@store` calls service, redirects to show page with success flash.
- [x] Tests: happy path, no docs selected, pending request exists, inactive doc.

## 3.4 My Requests List

- [x] `Student\RequestController@index` returns paginated requests with filters (status, date range).
- [x] `Pages/Student/Requests/Index.vue`:
    - DataTable with reference, document, status, payment status, created_at, action
    - Filter bar (status dropdown, date range, search)
    - Pagination (server-side)
    - Empty state
- [x] Tests.

## 3.5 Request Detail / Tracking

- [x] `Student\RequestController@show` returns request with all related data (payment, clearance).
- [x] `Pages/Student/Requests/Show.vue`:
    - Header with reference and status badge
    - Timeline component showing all stages with timestamps
    - Payment section (status, receipt preview if uploaded)
    - Clearance section (4 dept statuses with sign timestamps)
    - Cancel button (only if pending and no receipt)
- [x] `Timeline.vue` reusable component.
- [x] Tests including authorization.

## 3.6 Cancel Request

- [x] `@cancel` action with policy check.
- [x] Confirmation modal in Vue.
- [x] Activity log + notify admin.
- [x] Tests.

## 3.7 Payment Upload

- [x] `Student\PaymentController@index` shows pending payments.
- [x] `Pages/Student/Payments/Index.vue` lists pending payments with upload form per row.
- [x] `UploadPaymentRequest` Form Request — file (jpg/png/pdf, max 5MB), payment_method, reference_number.
- [x] `PaymentService::uploadReceipt($payment, $file, $method, $ref)`:
    - Validate not already approved
    - Store file with UUID name in private disk
    - Update payment row
    - Dispatch `PaymentSubmitted` event
- [x] `<FileUpload>` Vue component (drag-and-drop, preview, progress).
- [x] Receipt preview route `/files/payment-receipt/{payment}` with policy check.
- [x] Tests including invalid file types, oversized files.

## 3.8 Clearance View

- [x] `Student\ClearanceController@show` returns student's latest clearance.
- [x] `Pages/Student/Clearance/Show.vue`:
    - 4 department status cards (teacher, dean, accounting, SAO)
    - Each shows status badge, signer name, signed date, remarks
    - Overall status indicator
    - "Submit clearance file" form (optional supporting doc)
    - "Download PDF" button (visible only if completed)
- [x] `ClearanceService::submitFile($clearance, $file)`.
- [x] Tests.

## 3.9 Notifications Page

- [x] `NotificationController@index` (shared across roles).
- [x] `Pages/Notifications/Index.vue` — paginated list, filter by read/unread, click navigates to source.
- [x] Mark-as-read endpoint.
- [x] Mark-all-read endpoint.
- [x] Tests.

## 3.10 FAQ Page

- [x] `Student\FaqController@index` returns student-audience FAQs.
- [x] `Pages/Student/Faq.vue` — accordion list.
- [x] Search input (client-side filtering OK at this scale).

## 3.11 Profile (already from Phase 02 — verify)

- [x] Profile page works, avatar uploads, password change works.

## 3.12 Browser Test (Stretch)

- [ ] Playwright spec: register → admin approves → login → submit request → upload payment.

---

## Exit Criteria

- ✅ Student can register, get approved, log in, submit request, upload payment, view tracking page.
- ✅ All actions logged to `activity_logs`.
- ✅ Coverage 80%+ on student code.
- ✅ All forms have proper validation and error UX.
- ✅ Mobile-responsive verified.
