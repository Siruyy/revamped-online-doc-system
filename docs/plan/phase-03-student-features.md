# Phase 03 — Student Features

> **Goal:** Full student-facing functionality: dashboard, request submission, payment upload, tracking, clearance view, FAQ. Real-time deferred to Phase 07.

**Subagents:** `tdd-guide`, `code-reviewer`, `security-reviewer` (uploads).
**Skills:** `tdd-workflow`, `frontend-patterns`, `backend-patterns`.
**Depends on:** Phase 02.

---

## 3.1 Student Layout

- [ ] `Layouts/StudentLayout.vue` — top navbar with all student nav items, mobile hamburger drawer.
- [ ] `NotificationBell.vue`, `MessageBell.vue` (UI only; real-time wired in Phase 07).
- [ ] `UserAvatar.vue` with dropdown (profile, logout).
- [ ] Responsive verified at 375px, 768px, 1280px.

## 3.2 Student Dashboard

- [ ] `Student\DashboardController@index` returns:
    - Counts: active requests, pending payments, clearance status
    - Latest 3 announcements (pinned first)
    - Top 5 student-audience FAQs
    - 5 most recent notifications
- [ ] `Pages/Student/Dashboard.vue` with stat cards, announcements panel, FAQ accordion, recent activity.
- [ ] Loading skeleton.
- [ ] Empty states.

## 3.3 Document Request Submission

- [ ] `Student\RequestController@create` returns active document types grouped by category.
- [ ] `Pages/Student/Requests/Create.vue`:
    - Multi-select (checkbox list)
    - Live total fee calculation
    - Purpose textarea (optional, max 500)
    - Submit button disabled if zero selected or pending request exists
- [ ] `StoreRequestRequest` Form Request — validates docs IDs exist, are active, count between 1 and 5.
- [ ] `RequestService::createRequestBatch($user, $docIds, $purpose)`:
    - Begin transaction
    - Insert one `document_request` row per doc
    - Insert single `payment` row with sum total
    - Generate reference_no
    - Dispatch `RequestSubmitted` event
    - Commit
- [ ] `@store` calls service, redirects to show page with success flash.
- [ ] Tests: happy path, no docs selected, pending request exists, inactive doc.

## 3.4 My Requests List

- [ ] `Student\RequestController@index` returns paginated requests with filters (status, date range).
- [ ] `Pages/Student/Requests/Index.vue`:
    - DataTable with reference, document, status, payment status, created_at, action
    - Filter bar (status dropdown, date range, search)
    - Pagination (server-side)
    - Empty state
- [ ] Tests.

## 3.5 Request Detail / Tracking

- [ ] `Student\RequestController@show` returns request with all related data (payment, clearance).
- [ ] `Pages/Student/Requests/Show.vue`:
    - Header with reference and status badge
    - Timeline component showing all stages with timestamps
    - Payment section (status, receipt preview if uploaded)
    - Clearance section (4 dept statuses with sign timestamps)
    - Cancel button (only if pending and no receipt)
- [ ] `Timeline.vue` reusable component.
- [ ] Tests including authorization.

## 3.6 Cancel Request

- [ ] `@cancel` action with policy check.
- [ ] Confirmation modal in Vue.
- [ ] Activity log + notify admin.
- [ ] Tests.

## 3.7 Payment Upload

- [ ] `Student\PaymentController@index` shows pending payments.
- [ ] `Pages/Student/Payments/Index.vue` lists pending payments with upload form per row.
- [ ] `UploadPaymentRequest` Form Request — file (jpg/png/pdf, max 5MB), payment_method, reference_number.
- [ ] `PaymentService::uploadReceipt($payment, $file, $method, $ref)`:
    - Validate not already approved
    - Store file with UUID name in private disk
    - Update payment row
    - Dispatch `PaymentSubmitted` event
- [ ] `<FileUpload>` Vue component (drag-and-drop, preview, progress).
- [ ] Receipt preview route `/files/payment-receipt/{payment}` with policy check.
- [ ] Tests including invalid file types, oversized files.

## 3.8 Clearance View

- [ ] `Student\ClearanceController@show` returns student's latest clearance.
- [ ] `Pages/Student/Clearance/Show.vue`:
    - 4 department status cards (teacher, dean, accounting, SAO)
    - Each shows status badge, signer name, signed date, remarks
    - Overall status indicator
    - "Submit clearance file" form (optional supporting doc)
    - "Download PDF" button (visible only if completed)
- [ ] `ClearanceService::submitFile($clearance, $file)`.
- [ ] Tests.

## 3.9 Notifications Page

- [ ] `NotificationController@index` (shared across roles).
- [ ] `Pages/Notifications/Index.vue` — paginated list, filter by read/unread, click navigates to source.
- [ ] Mark-as-read endpoint.
- [ ] Mark-all-read endpoint.
- [ ] Tests.

## 3.10 FAQ Page

- [ ] `Student\FaqController@index` returns student-audience FAQs.
- [ ] `Pages/Student/Faq.vue` — accordion list.
- [ ] Search input (client-side filtering OK at this scale).

## 3.11 Profile (already from Phase 02 — verify)

- [ ] Profile page works, avatar uploads, password change works.

## 3.12 Browser Test (Stretch)

- [ ] Playwright spec: register → admin approves → login → submit request → upload payment.

---

## Exit Criteria

- ✅ Student can register, get approved, log in, submit request, upload payment, view tracking page.
- ✅ All actions logged to `activity_logs`.
- ✅ Coverage 80%+ on student code.
- ✅ All forms have proper validation and error UX.
- ✅ Mobile-responsive verified.
