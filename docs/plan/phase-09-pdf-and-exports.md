# Phase 09 — PDF Generation And Exports

> **Goal:** Replace stub clearance PDFs with real DomPDF output and add authorized CSV/XLSX exports for users, requests, and activity logs.

**Status:** Finished for CSV/PDF MVP. Clearance PDF generation uses DomPDF on private storage, CSV exports exist for users, admin request/payment reports, and SuperAdmin activity logs, and remaining XLSX/broader UI links are explicitly deferred until requested.

**Phase notes (2026-05-10):** CSV was chosen over XLSX to avoid adding a spreadsheet package before the client asks for it. Export endpoints stream chunked query results and are covered by authorization/content tests.

**Closeout notes (2026-05-13):** SuperAdmin users CSV now matches the Phase 09 heading contract and includes `approved_at`. Clearance PDF rendering includes student/signer content tests, uses DomPDF-compatible table markup, and embeds private signatures as data URIs so local storage paths are not rendered. Clearance PDF downloads now require completed clearances and owner-scoped private paths under `pdfs/clearance/{student_id}/`.

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
- [x] Locate current stub PDF generation.
- [x] Confirm `clearances.pdf_path` exists and expected disk is private/local.
- [x] Confirm policy method for PDF download exists.
- [x] Identify exact call site where real `PdfService` should replace stub behavior.

**Acceptance:**
- [x] Implementation seam is documented before code changes.

## Agent Task 9.2 — Clearance PdfService

**Delegate to:** tdd-workflow + backend-patterns

**Files likely touched:**
- `app/Services/PdfService.php`
- `config/dompdf.php`
- `tests/Unit/PdfServiceTest.php`

**Steps:**
- [x] Write failing test that completed clearance generates a non-empty PDF file on private disk.
- [x] Implement `PdfService::generateClearancePdf(Clearance $clearance): string`.
- [x] Load student, request, and signer relationships before rendering.
- [x] Store output under `pdfs/clearance/{student_id}/clearance-{clearance_id}.pdf`.
- [x] Return stored relative path.

**Acceptance:**
- [x] Generated PDF file exists, is non-empty, and path is saved without exposing public URL.

## Agent Task 9.3 — Clearance PDF Template

**Delegate to:** frontend-patterns + backend-patterns

**Files likely touched:**
- `resources/views/pdf/clearance.blade.php`
- `public/images/svci-logo.png` if missing
- `tests/Feature/Pdf/ClearancePdfContentTest.php`

**Steps:**
- [x] Create DomPDF-compatible Blade template using tables and inline CSS.
- [x] Include student name, course/year, reference number, department statuses, signer names, signed dates, and verification footer.
- [x] Render signatures from private disk only after authorization path resolves them safely.
- [x] Avoid modern CSS unsupported by DomPDF.
- [x] Add content test that rendered PDF source includes student name and signer labels.

**Acceptance:**
- [x] Template matches `docs/13-pdf-generation.md` constraints.
- [x] No private storage path leaks into rendered text.

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
- [x] Replace stub PDF call with `PdfService::generateClearancePdf()` on completed transition.
- [x] Ensure generation happens once per completed transition unless forced regeneration is explicitly added.
- [x] Add `/files/clearance/{clearance}/pdf` download route.
- [x] Enforce ownership/admin/superadmin access through policy.
- [x] Return correct download response headers from private storage.

**Acceptance:**
- [x] Student can download own completed clearance PDF.
- [x] Other students cannot download it.
- [x] Admin/SuperAdmin access follows policy.

## Agent Task 9.5 — Users Export

**Delegate to:** backend-patterns + tdd-workflow

**Files likely touched:**
- `app/Exports/UsersExport.php`
- `app/Http/Controllers/SuperAdmin/UserExportController.php`
- `routes/superadmin.php`
- `tests/Feature/Exports/UsersExportTest.php`

**Steps:**
- [x] Implement export using query-based export service.
- [x] Support current filters: role, status, course, year, search.
- [x] Include headings: ID, Full Name, Email, Role, Status, Course, Year, Created At, Approved At.
- [x] Support CSV.
- [x] Restrict endpoint to SuperAdmin.

**Acceptance:**
- [x] Export respects filters and authorization.
- [x] Response has downloadable file headers.

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
- [x] Implement query export with student, document, request status, processing stage, submitted date, and approved date fields where available.
- [x] Support date range, status, and course filters where existing admin report page supports them.
- [x] Allow admin only through admin report route; SuperAdmin report export can be added separately if needed.
- [x] Keep export memory-safe with query/chunk style.

**Acceptance:**
- [x] Admin report exports match visible filters.
- [x] Student/department roles cannot access endpoints.

## Agent Task 9.7 — Activity Log Export

**Delegate to:** backend-patterns + security-review

**Files likely touched:**
- `app/Exports/ActivityLogExport.php`
- `app/Http/Controllers/SuperAdmin/ActivityLogExportController.php`
- `routes/superadmin.php`
- `tests/Feature/Exports/ActivityLogExportTest.php`

**Steps:**
- [x] Export action, actor, affected user, IP, and created date.
- [x] Support date range, action, actor, and search filters.
- [x] Avoid exporting metadata payloads to reduce leak risk.
- [x] Restrict to SuperAdmin.

**Acceptance:**
- [x] Export is useful for forensics without leaking secrets.

## Agent Task 9.8 — Phase Verification

**Delegate to:** code-reviewer

**Commands:**

```bash
php artisan test --filter=Pdf
php artisan test --filter=Export
php artisan test --filter=Clearance
./vendor/bin/pint --test app/Services app/Http/Controllers/SuperAdmin tests/Feature/Exports tests/Feature/Pdf
```

**Acceptance:**
- [x] Real clearance PDFs are generated and downloadable.
- [x] Export endpoints are authorized and tested.
- [x] Stub PDF behavior is removed or no longer reachable.
