# Phase 15 — Public Request Intake

> **Goal:** Replace requestor-facing student account registration with public document request submission, upfront receipt upload, admin validation, and reference-number tracking.

**Status:** Not started. Client feedback on 2026-06-23 requests that students/requestors go directly to a request page, fill student/request details, upload required attachments and payment receipt, then receive a reference number. Existing student routes/pages should be hidden for now but not deleted.

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
- [ ] Add failing tests proving a document request can exist without a `users` row.
- [ ] Add nullable requestor snapshot fields to `document_requests`: full name, email, contact number, student ID, course, year level.
- [ ] Make `document_requests.user_id` nullable while preserving existing authenticated-student records.
- [ ] Make `payments.user_id` nullable and keep `document_request_id` required for the public flow.
- [ ] Add useful indexes for public tracking and admin search: `reference_no`, `requester_student_id`, `requester_email`, `status`.

**Acceptance:**
- [ ] Public requests persist without creating hidden student users.
- [ ] Existing staff/admin/student model relationships still work for old records.
- [ ] `php artisan test tests/Feature/Public/PublicRequestSubmissionTest.php` passes.

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
- [ ] Add public GET/POST routes for `/request-document`.
- [ ] Validate requestor details, selected document items, purpose, required receipt, payment method/reference, and requirement files.
- [ ] Store receipts under `payment-receipts/public/{requestId}/` and requirements under `request-requirements/public/{requestId}/` on the private `local` disk.
- [ ] Create `DocumentRequest`, `DocumentRequestItem`, `RequestRequirement`, and `Payment` in one DB transaction.
- [ ] Start payment as `pending_approval` because receipt is uploaded upfront.
- [ ] Notify active admins and SuperAdmins after submission.
- [ ] Redirect to a confirmation page showing only the generated `reference_no`.

**Acceptance:**
- [ ] A public request cannot be submitted without a receipt.
- [ ] A public request cannot be submitted without document-type required attachments.
- [ ] Receipt and requirement files are private and exist on disk.
- [ ] No `users` row is created for the requestor.

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
- [ ] Add public `GET /track-document` page.
- [ ] Add reference-number lookup using only `reference_no`.
- [ ] Return a privacy-safe status payload: reference number, document names, request status, payment status, processing stage, submitted date, expected release date, and denial reason when denied.
- [ ] Do not expose uploaded file URLs, email, contact number, internal IDs, department signer names, or staff-only notes.
- [ ] Rate limit tracking lookups to slow reference-number guessing.

**Acceptance:**
- [ ] Requestor can track with only the reference number.
- [ ] Unknown references return a generic not-found state.
- [ ] Public tracking never returns private file URLs or PII beyond the requestor-entered reference context.

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
- [ ] Show requestor snapshot details on admin request detail.
- [ ] Show requirement attachments with validate/reject actions.
- [ ] Show payment receipt, amount, method, and reference on the same request detail page.
- [ ] Add a single approve action that validates the request and approves the payment when requirements are valid and payment is `pending_approval`.
- [ ] Add a deny-whole-request action with a required denial reason; set request to `denied` and payment to `denied`.
- [ ] Expose equivalent SuperAdmin request validation routes/actions.

**Acceptance:**
- [ ] Admin can validate attachments and payment from request detail without visiting a separate payment queue.
- [ ] Denying the request stores a request-level reason visible in public tracking.
- [ ] Payment approval still starts clearance when the document type requires clearance.

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
- [ ] Add an authenticated route for request requirement files, e.g. `/files/request-requirements/{requirement}`.
- [ ] Authorize admin/SuperAdmin and, only for legacy authenticated student records, the owning student.
- [ ] Enforce path prefixes: `request-requirements/{userId}/...` for legacy student records and `request-requirements/public/{requestId}/...` for public records.
- [ ] Replace `/storage/${req.file_path}` links with the private file route.
- [ ] Add tests proving direct `/storage/request-requirements/...` access is not used and private route blocks unrelated users.

**Acceptance:**
- [ ] Admin can open request requirement images/PDFs without 404.
- [ ] Private request files are not publicly enumerable.

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
- [ ] Replace public `Create account` and `Start a request` links with `Request Document`.
- [ ] Add `Track Document` as the secondary public action.
- [ ] Build public request form with requestor details, document selector, copies, purpose, requirement uploads, payment method/reference, and receipt upload.
- [ ] Show payment instructions before receipt upload when an active payment profile exists.
- [ ] Show confirmation page with reference number and next-step copy.
- [ ] Hide student self-service links from public navigation; do not delete student pages/routes.

**Acceptance:**
- [ ] Mobile request submission works at 375px width.
- [ ] The public form does not ask for a password.
- [ ] Login remains available for staff/admin/SuperAdmin.

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
- [ ] Send public request submitted emails to admins/SuperAdmins.
- [ ] Send request status emails to requestor email when present.
- [ ] Keep in-app/database notifications for authenticated staff users.
- [ ] Update training docs for public request and reference tracking.
- [ ] Update manual verification checklist for the new guest flow.

**Acceptance:**
- [ ] Requestor receives email status updates when email is provided.
- [ ] Staff notification behavior remains queued and test-covered.

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
