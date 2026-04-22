# Phase 09 — PDF Generation & Excel Exports

> **Goal:** Generate clearance certificate PDFs; export reports and user lists to Excel/CSV.

**Subagents:** `tdd-guide`, `code-reviewer`.
**Skills:** `backend-patterns`.
**Depends on:** Phases 04, 05, 06.

---

## 9.1 PDF Service Setup

- [ ] Install `barryvdh/laravel-dompdf` (already in Phase 00).
- [ ] Add `App\Services\PdfService`.
- [ ] Embed school logo at `public/images/svci-logo.png`.

## 9.2 Clearance PDF Template

- [ ] Create `resources/views/pdf/clearance.blade.php` per [`docs/13-pdf-generation.md`](../docs/13-pdf-generation.md).
- [ ] Uses table-based layout (DomPDF compatible).
- [ ] Inline styles only.
- [ ] Embeds signer signatures from `signature_path`.
- [ ] Includes verification footer.

## 9.3 Generation Trigger

- [ ] `ClearanceService::recomputeAndComplete($clearance)`:
    - On transition to `completed`, call `PdfService::generateClearance`.
    - Save path to `pdf_path` column.
    - Send `ClearanceCompletedNotification`.
- [ ] Test generation produces a non-empty file.
- [ ] Test PDF includes student name and all 4 signers.

## 9.4 Download Routes

- [ ] `/files/clearance/{clearance}/pdf` — student or admin can download.
- [ ] Policy enforces ownership / admin role.
- [ ] Tests.

## 9.5 Public Verification Page (Stretch)

- [ ] `/verify/{clearance}` shows minimal authenticity confirmation.
- [ ] No login required.
- [ ] Tests.

## 9.6 Excel Exports — Setup

- [ ] Install `maatwebsite/laravel-excel` (already in Phase 00).
- [ ] Configure default export disk and chunk size.

## 9.7 Users Export (SuperAdmin)

- [ ] `UsersExport` class implementing `FromQuery`, `WithHeadings`, `WithMapping`.
- [ ] Filters from request applied.
- [ ] Format: ID, Full Name, Email, Role, Status, Course, Year, Created At, Approved At.
- [ ] Route: `/superadmin/users/export?format=xlsx|csv`.
- [ ] Tests.

## 9.8 Requests Report Export (Admin & SuperAdmin)

- [ ] `RequestsExport` class with similar pattern.
- [ ] Includes student info, document, status, payment status, timestamps.
- [ ] Date range and status filters.
- [ ] Route: `/admin/reports/export?format=xlsx`.

## 9.9 Activity Log Export (SuperAdmin)

- [ ] `ActivityLogExport` for forensics.
- [ ] Date range filter.

## 9.10 Async Exports for Large Datasets (Stretch)

- [ ] If exports exceed 10k rows, queue them and email a download link when ready.
- [ ] Use Excel facade's `queue()` method.

## 9.11 Tests

- [ ] Each export class unit-tested.
- [ ] Endpoint returns proper headers and downloadable file.
- [ ] Authorization enforced.

---

## Exit Criteria

- ✅ Clearance PDFs auto-generated on completion and downloadable.
- ✅ Signatures and logo render correctly.
- ✅ Excel exports work for users and reports.
- ✅ All exports respect filters and authorization.
