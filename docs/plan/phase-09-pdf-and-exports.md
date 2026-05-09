# Phase 09 — PDF Generation And Exports

> **Goal:** Replace stub clearance PDFs with real DomPDF output and add authorized CSV/XLSX exports for users, requests, and activity logs.

**Status:** Not started. Current clearance PDF behavior is a stub in service code.

**Depends on:** Phases 04-06. PDF completion seam depends on Phase 05 closeout.

**Primary docs:** [`13-pdf-generation.md`](../13-pdf-generation.md), [`11-file-storage.md`](../11-file-storage.md), [`10-security.md`](../10-security.md), [`04-database-schema.md`](../04-database-schema.md).

---

## Agent Task 9.1 — PDF Seam Audit

**Delegate to:** code-explorer + backend-patterns

**Read first:**
- `app/Services/ClearanceService.php`
- `app/Policies/ClearancePolicy.php`
- `routes/student.php`
- `routes/admin.php`
- `docs/13-pdf-generation.md`

**Steps:**
- [ ] Locate current stub PDF generation.
- [ ] Confirm `clearances.pdf_path` exists and expected disk is private/local.
- [ ] Confirm policy method for PDF download exists.
- [ ] Identify exact call site where real `PdfService` should replace stub behavior.

**Acceptance:**
- [ ] Implementation seam is documented before code changes.

## Agent Task 9.2 — Clearance PdfService

**Delegate to:** tdd-workflow + backend-patterns

**Files likely touched:**
- `app/Services/PdfService.php`
- `config/dompdf.php`
- `tests/Unit/PdfServiceTest.php`

**Steps:**
- [ ] Write failing test that completed clearance generates a non-empty PDF file on private disk.
- [ ] Implement `PdfService::generateClearance(Clearance $clearance): string`.
- [ ] Load student, request, payment, and signer relationships before rendering.
- [ ] Store output under `pdfs/clearance/{clearance_id}.pdf` or documented equivalent.
- [ ] Return stored relative path.

**Acceptance:**
- [ ] Generated PDF file exists, is non-empty, and path is saved without exposing public URL.

## Agent Task 9.3 — Clearance PDF Template

**Delegate to:** frontend-patterns + backend-patterns

**Files likely touched:**
- `resources/views/pdf/clearance.blade.php`
- `public/images/svci-logo.png` if missing
- `tests/Feature/Pdf/ClearancePdfContentTest.php`

**Steps:**
- [ ] Create DomPDF-compatible Blade template using tables and inline CSS.
- [ ] Include SVCI logo, student name, course/year, reference number, department statuses, signer names, signed dates, and verification footer.
- [ ] Render signatures from private disk only after authorization path resolves them safely.
- [ ] Avoid modern CSS unsupported by DomPDF.
- [ ] Add content test that rendered PDF source includes student name and signer labels.

**Acceptance:**
- [ ] Template matches `docs/13-pdf-generation.md` constraints.
- [ ] No private storage path leaks into rendered text.

## Agent Task 9.4 — PDF Generation Trigger And Download

**Delegate to:** backend-patterns + tdd-workflow

**Files likely touched:**
- `app/Services/ClearanceService.php`
- `app/Http/Controllers/FileController.php` or dedicated clearance PDF controller
- `routes/student.php`
- `routes/admin.php`
- `routes/superadmin.php`
- `tests/Feature/Pdf/ClearancePdfDownloadTest.php`

**Steps:**
- [ ] Replace stub PDF call with `PdfService::generateClearance()` on completed transition.
- [ ] Ensure generation happens once per completed transition unless forced regeneration is explicitly added.
- [ ] Add `/files/clearance/{clearance}/pdf` download route.
- [ ] Enforce ownership/admin/superadmin access through policy.
- [ ] Return correct `application/pdf` response headers.

**Acceptance:**
- [ ] Student can download own completed clearance PDF.
- [ ] Other students cannot download it.
- [ ] Admin/SuperAdmin access follows policy.

## Agent Task 9.5 — Users Export

**Delegate to:** backend-patterns + tdd-workflow

**Files likely touched:**
- `app/Exports/UsersExport.php`
- `app/Http/Controllers/SuperAdmin/UserExportController.php`
- `routes/superadmin.php`
- `tests/Feature/Exports/UsersExportTest.php`

**Steps:**
- [ ] Implement export using query-based export class.
- [ ] Support current filters: role, status, course, year, search.
- [ ] Include headings: ID, Full Name, Email, Role, Status, Course, Year, Created At, Approved At.
- [ ] Support `format=xlsx|csv`.
- [ ] Restrict endpoint to SuperAdmin.

**Acceptance:**
- [ ] Export respects filters and authorization.
- [ ] Response has downloadable file headers.

## Agent Task 9.6 — Requests And Payments Report Export

**Delegate to:** backend-patterns + tdd-workflow

**Files likely touched:**
- `app/Exports/RequestsExport.php`
- `app/Http/Controllers/Admin/ReportExportController.php`
- `app/Http/Controllers/SuperAdmin/ReportExportController.php`
- `routes/admin.php`
- `routes/superadmin.php`
- `tests/Feature/Exports/RequestsExportTest.php`

**Steps:**
- [ ] Implement query export with student, document, request status, payment status, processing stage, submitted date, approved date.
- [ ] Support date range, status, payment status, document type, course, and year filters where existing report page supports them.
- [ ] Allow admin and SuperAdmin only.
- [ ] Keep export memory-safe with query/chunk style.

**Acceptance:**
- [ ] Admin/SuperAdmin report exports match visible filters.
- [ ] Student/department roles cannot access endpoints.

## Agent Task 9.7 — Activity Log Export

**Delegate to:** backend-patterns + security-review

**Files likely touched:**
- `app/Exports/ActivityLogExport.php`
- `app/Http/Controllers/SuperAdmin/ActivityLogExportController.php`
- `routes/superadmin.php`
- `tests/Feature/Exports/ActivityLogExportTest.php`

**Steps:**
- [ ] Export action, actor, affected user, IP, user agent, created date, and safe metadata summary.
- [ ] Support date range, action, actor, and search filters.
- [ ] Redact tokens, passwords, and sensitive payload fields.
- [ ] Restrict to SuperAdmin.

**Acceptance:**
- [ ] Export is useful for forensics without leaking secrets.

## Agent Task 9.8 — Phase Verification

**Delegate to:** code-reviewer

**Commands:**

```bash
php artisan test --filter=Pdf
php artisan test --filter=Export
php artisan test --filter=Clearance
./vendor/bin/pint --test app/Services app/Exports tests/Feature/Exports tests/Feature/Pdf
```

**Acceptance:**
- [ ] Real clearance PDFs are generated and downloadable.
- [ ] Export endpoints are authorized and tested.
- [ ] Stub PDF behavior is removed or no longer reachable.
