# Phase 15 — Public Request Intake

> **Goal:** Replace requestor-facing student account registration with public document request submission, upfront receipt upload, admin validation, and reference-number tracking.

**Status:** Partially implemented. Client feedback on 2026-06-23 requests that students/requestors go directly to a request page, fill request details, upload required attachments and payment receipt, then receive a reference number. Existing student routes/pages remain in code for now but are hidden from public navigation.

**Latest closeout note:** Public intake, reference tracking, private file serving, package validation, public clearance compatibility, `/register` redirect, staff public-snapshot search fallbacks, and the cached-config CSP fix are implemented in code. Keep this phase open only for manual browser/realtime/email verification and any later cleanup of legacy authenticated-student pages/routes.

**Supersedes for requestors:** Phase 02 student self-registration and Phase 03 authenticated student request/payment pages remain historical implementation, but they are no longer the desired public request workflow.

**Primary handoff plan:** [`../superpowers/plans/2026-06-23-public-request-intake.md`](../superpowers/plans/2026-06-23-public-request-intake.md)

**Primary docs:** [`05-features.md`](../05-features.md), [`06-user-flows.md`](../06-user-flows.md), [`07-routes-and-controllers.md`](../07-routes-and-controllers.md), [`10-security.md`](../10-security.md), [`11-file-storage.md`](../11-file-storage.md), [`12-notifications-and-email.md`](../12-notifications-and-email.md), [`18-uat-script.md`](../18-uat-script.md).

---

## Agent Task 15.1 — Schema And Model Support

**Delegate to:** database-migrations + backend-patterns

**Read first:**
- `docs/04-database-schema.md`
- `app/Models/DocumentRequest.php`
- `app/Models/Payment.php`
- `database/migrations/2026_04_22_150002_create_document_requests_table.php`
- `database/migrations/2026_04_22_150003_create_payments_table.php`

**Files likely touched:**
- `database/migrations/*_support_public_document_requests.php`
- `app/Models/DocumentRequest.php`
- `app/Models/Payment.php`
- `tests/Feature/Public/PublicRequestSubmissionTest.php`

**Steps:**
- [x] Add failing tests proving a document request can exist without a `users` row.
- [x] Add nullable requestor snapshot fields to `document_requests`: full name, email, contact number, student ID, course, year level.
- [x] Make `document_requests.user_id` nullable while preserving existing authenticated-student records.
- [x] Make `payments.user_id` nullable and keep `document_request_id` required for the public flow.
- [x] Add useful indexes for public tracking and admin search: `reference_no`, `requester_student_id`, `requester_email`, `status`.

**Acceptance:**
- [x] Public requests persist without creating hidden student users.
- [x] Existing staff/admin/student model relationships still work for old records.
- [x] `php artisan test tests/Feature/Public/PublicRequestSubmissionTest.php` passes.

## Agent Task 15.2 — Public Request Submission Backend

**Delegate to:** tdd-workflow + backend-patterns + security-review

**Read first:**
- `docs/10-security.md`
- `docs/11-file-storage.md`
- `app/Services/RequestService.php`
- `app/Services/PaymentService.php`
- `app/Http/Requests/Student/StoreWizardRequest.php`
- `app/Http/Requests/Student/UploadPaymentRequest.php`

**Files likely touched:**
- `routes/web.php`
- `app/Http/Controllers/Public/DocumentRequestController.php`
- `app/Http/Requests/Public/StorePublicDocumentRequest.php`
- `app/Services/PublicDocumentRequestService.php`
- `app/Events/RequestSubmitted.php`
- `app/Events/PaymentSubmitted.php`
- `tests/Feature/Public/PublicRequestSubmissionTest.php`

**Steps:**
- [x] Add public GET/POST routes for `/request-document`.
- [x] Validate requestor details, selected document items, purpose, required receipt, payment method/reference, and requirement files.
- [x] Store receipts under `payment-receipts/public/{requestId}/` and requirements under `request-requirements/public/{requestId}/` on the private `local` disk.
- [x] Create `DocumentRequest`, `DocumentRequestItem`, `RequestRequirement`, and `Payment` in one DB transaction.
- [x] Start payment as `pending_approval` because receipt is uploaded upfront.
- [x] Notify active admins and SuperAdmins after submission.
- [x] Redirect to a confirmation page showing only the generated `reference_no`.

**Acceptance:**
- [x] A public request cannot be submitted without a receipt.
- [x] A public request cannot be submitted without document-type required attachments.
- [x] Receipt and requirement files are private and exist on disk.
- [x] No `users` row is created for the requestor.

## Agent Task 15.3 — Public Tracking Backend

**Delegate to:** backend-patterns + security-review

**Read first:**
- `docs/10-security.md`
- `docs/06-user-flows.md`
- `app/Http/Controllers/Student/RequestController.php`
- `resources/js/Pages/Student/Requests/Show.vue`

**Files likely touched:**
- `routes/web.php`
- `app/Http/Controllers/Public/TrackDocumentController.php`
- `app/Http/Requests/Public/TrackDocumentRequest.php`
- `resources/js/Pages/Public/TrackDocument.vue`
- `resources/js/Pages/Public/TrackResult.vue`
- `tests/Feature/Public/PublicTrackingTest.php`

**Steps:**
- [x] Add public `GET /track-document` page.
- [x] Add reference-number lookup using only `reference_no`.
- [x] Return a privacy-safe status payload: reference number, document names, request status, payment status, processing stage, submitted date, expected release date, and denial reason when denied.
- [x] Do not expose uploaded file URLs, email, contact number, internal IDs, department signer names, or staff-only notes.
- [x] Rate limit tracking lookups to slow reference-number guessing.

**Acceptance:**
- [x] Requestor can track with only the reference number.
- [x] Unknown references return a generic not-found state.
- [x] Public tracking never returns private file URLs or PII beyond the requestor-entered reference context.

## Agent Task 15.4 — Admin/SuperAdmin Combined Validation

**Delegate to:** backend-patterns + frontend-patterns + security-review

**Read first:**
- `app/Http/Controllers/Admin/RequestController.php`
- `app/Http/Controllers/Admin/PaymentController.php`
- `resources/js/Pages/Admin/Requests/Show.vue`
- `resources/js/Pages/Admin/Payments/Index.vue`
- `routes/admin.php`
- `routes/superadmin.php`

**Files likely touched:**
- `routes/admin.php`
- `routes/superadmin.php`
- `app/Http/Controllers/Admin/RequestController.php`
- `app/Services/RequestService.php`
- `app/Services/PaymentService.php`
- `resources/js/Pages/Admin/Requests/Show.vue`
- `tests/Feature/Admin/PublicRequestValidationTest.php`

**Steps:**
- [x] Show requestor snapshot details on admin request detail.
- [x] Show requirement attachments with validate/reject actions.
- [x] Show payment receipt, amount, method, and reference on the same request detail page.
- [x] Add a single approve action that validates the request and approves the payment when requirements are valid and payment is `pending_approval`.
- [x] Add a deny-whole-request action with a required denial reason; set request to `denied` and payment to `denied`.
- [x] Expose equivalent SuperAdmin request validation routes/actions.

**Acceptance:**
- [x] Admin can validate attachments and payment from request detail without visiting a separate payment queue.
- [x] Denying the request stores a request-level reason visible in public tracking.
- [x] Payment approval still starts clearance when the document type requires clearance.

## Agent Task 15.5 — Private File Serving Fix

**Delegate to:** security-review + backend-patterns

**Read first:**
- `docs/11-file-storage.md`
- `app/Http/Controllers/FileController.php`
- `resources/js/Pages/Admin/Requests/Show.vue`
- `app/Models/RequestRequirement.php`

**Files likely touched:**
- `routes/web.php`
- `app/Http/Controllers/FileController.php`
- `app/Policies/RequestRequirementPolicy.php`
- `resources/js/Pages/Admin/Requests/Show.vue`
- `tests/Feature/Auth/SecurityHardeningTest.php`

**Steps:**
- [x] Add an authenticated route for request requirement files, e.g. `/files/request-requirements/{requirement}`.
- [x] Authorize admin/SuperAdmin and, only for legacy authenticated student records, the owning student.
- [x] Enforce path prefixes: `request-requirements/{userId}/...` for legacy student records and `request-requirements/public/{requestId}/...` for public records.
- [x] Replace `/storage/${req.file_path}` links with the private file route.
- [x] Add tests proving direct `/storage/request-requirements/...` access is not used and private route blocks unrelated users.

**Acceptance:**
- [x] Admin can open request requirement images/PDFs without 404.
- [x] Private request files are not publicly enumerable.

## Agent Task 15.6 — Public Frontend And Navigation

**Delegate to:** frontend-patterns + ui-ux-pro-max

**Read first:**
- `resources/js/Pages/Welcome.vue`
- `resources/js/Pages/Student/Requests/Create.vue`
- `resources/js/Layouts/GuestLayout.vue`
- `docs/17-design-system.md`

**Files likely touched:**
- `resources/js/Pages/Welcome.vue`
- `resources/js/Pages/Public/RequestDocument.vue`
- `resources/js/Pages/Public/RequestSubmitted.vue`
- `resources/js/Pages/Public/TrackDocument.vue`
- `resources/js/Pages/Public/TrackResult.vue`
- `resources/js/Components/Public/DocumentCart.vue`
- `resources/js/Components/Public/FileUploadField.vue`

**Steps:**
- [x] Replace public `Create account` and `Start a request` links with `Request Document`.
- [x] Add `Track Document` as the secondary public action.
- [x] Build public request form with requestor details, document selector, copies, purpose, requirement uploads, payment method/reference, and receipt upload.
- [x] Show payment instructions before receipt upload when an active payment profile exists.
- [x] Show confirmation page with reference number and next-step copy.
- [x] Hide student self-service links from public navigation; do not delete student pages/routes.
- [x] Redirect direct `/register` GET/POST traffic to the public request page.

**Acceptance:**
- [ ] Mobile request submission works at 375px width.
- [x] The public form does not ask for a password.
- [x] Login remains available for staff/admin/SuperAdmin.

## Agent Task 15.7 — Notifications, Docs, And Training

**Delegate to:** doc-updater + backend-patterns

**Read first:**
- `docs/12-notifications-and-email.md`
- `docs/training/student-guide.md`
- `docs/training/admin-guide.md`
- `docs/manual-verification-checklist.md`

**Files likely touched:**
- `app/Notifications/WorkflowStatusNotification.php`
- `docs/training/student-guide.md`
- `docs/training/admin-guide.md`
- `docs/manual-verification-checklist.md`

**Steps:**
- [x] Send public request submitted emails to admins/SuperAdmins.
- [x] Send request status emails to requestor email when present.
- [x] Keep in-app/database notifications for authenticated staff users.
- [ ] Update training docs for public request and reference tracking.
- [x] Update manual verification checklist for the new guest flow.
- [x] Send public clearance status/completion emails when requestor email is present.

**Acceptance:**
- [x] Requestor receives email status updates when email is provided.
- [x] Staff notification behavior remains queued and test-covered.

## Agent Task 15.9 — Public Clearance And Staff Search Compatibility

**Delegate to:** backend-patterns + frontend-patterns + security-review

**Status:** Implemented.

**Steps:**
- [x] Redirect public `/register` GET/POST traffic to `/request-document` without creating student accounts.
- [x] Allow public `clearances.user_id = NULL` records to be signed/denied by department staff without a separate student-uploaded clearance file.
- [x] Generate private public-clearance PDFs from requestor snapshot fields under `pdfs/clearance/public/{requestId}/`.
- [x] Restrict public-clearance PDF downloads to Admin/SuperAdmin authenticated file routes.
- [x] Include public requestor snapshot fields in admin request search, department clearance search/filtering, and admin clearance monitor filtering.
- [x] Replace runtime `env()` usage in `SecurityHeaders` with cached config lookup.

**Acceptance:**
- [x] Public requests that require clearance can complete without hidden users.
- [x] Public requestor private files and clearance PDFs are not exposed in public tracking.
- [x] Staff queues show public requestor names instead of blank student relationship fields.
- [x] PHPStan no longer reports `env()` usage in middleware.

## Agent Task 15.8 — Verification And Closeout

**Delegate to:** code-reviewer + verification-loop

**Commands:**

```bash
php artisan test --filter=PublicRequest
php artisan test --filter=PublicTracking
php artisan test --filter=PublicRequestValidation
php artisan test --filter=SecurityHardening
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress
npm run lint
npm run build
```

**Acceptance:**
- [ ] Public request submission, tracking, admin validation, and private file serving tests pass.
- [ ] Existing auth/staff/admin/department/SuperAdmin tests still pass.
- [ ] Final diff reviewed for accidental deletion of hidden student pages/routes.
