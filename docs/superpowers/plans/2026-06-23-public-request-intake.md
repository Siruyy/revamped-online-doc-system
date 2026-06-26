# Public Request Intake Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace requestor-facing student registration with public document request submission, upfront receipt/requirement upload, admin/SuperAdmin validation, and reference-number tracking.

**Architecture:** Add a guest/public intake path that stores requestor details as immutable snapshots on `document_requests` instead of creating hidden `student` users. Keep existing authenticated student routes/pages in code but hide them from public navigation. Admin/SuperAdmin validate request details, requirement files, and payment receipt together from request detail.

**Tech Stack:** Laravel 13, Inertia.js, Vue 3, Tailwind CSS, MySQL, private `local` storage disk, Laravel notifications/events, Pest/PHPUnit, Playwright.

---

## Context

Client feedback received on 2026-06-23:

- Remove student account registration from the requestor flow.
- Public requestors should go directly to a request page.
- Requestors fill name, student ID, course, year level, document type, purpose, contact details, and email for notifications.
- Required document attachments and payment receipt are uploaded during initial request submission.
- System returns a reference number after submission.
- Public tracking uses reference number only.
- Admin/SuperAdmin validates request details, attachments, and payment receipt together.
- If invalid, admin denies the whole request with a reason shown in tracking.
- Existing student pages/routes should be hidden for now, not deleted.

Key existing implementation facts:

- Public landing currently links `Start a request` to registration in `resources/js/Pages/Welcome.vue`.
- Student routes are authenticated under `routes/student.php` and grouped in `routes/web.php` with `auth`, `role:student`, `approved`, and `verified`.
- `RequestService::createMultiItemRequest()` requires a `User`.
- `PaymentService::uploadReceipt()` intentionally blocks upload until request approval.
- Admin request detail links requirement files via `/storage/${req.file_path}`, but requirement files are stored on private `local` disk, causing 404.
- Admin payment approve/deny exists in `Admin\PaymentController`, but request detail only links to the payment queue.

## File Map

Create:
- `database/migrations/YYYY_MM_DD_HHMMSS_support_public_document_requests.php` — nullable user links and requestor snapshot columns.
- `app/Http/Controllers/Public/DocumentRequestController.php` — public request form, submit, confirmation.
- `app/Http/Controllers/Public/TrackDocumentController.php` — public reference tracking page and lookup.
- `app/Http/Requests/Public/StorePublicDocumentRequest.php` — validation for requestor details, document items, requirement files, and receipt.
- `app/Http/Requests/Public/TrackDocumentRequest.php` — validation for reference lookup.
- `app/Services/PublicDocumentRequestService.php` — transactional public request creation.
- `app/Policies/RequestRequirementPolicy.php` — authorization for private requirement downloads.
- `resources/js/Pages/Public/RequestDocument.vue` — public request form.
- `resources/js/Pages/Public/RequestSubmitted.vue` — confirmation with reference number.
- `resources/js/Pages/Public/TrackDocument.vue` — reference lookup.
- `resources/js/Pages/Public/TrackResult.vue` — privacy-safe status result.
- `resources/js/Components/Public/FileUploadField.vue` — upload control reused for receipt/requirements.
- `tests/Feature/Public/PublicRequestSubmissionTest.php`
- `tests/Feature/Public/PublicTrackingTest.php`
- `tests/Feature/Admin/PublicRequestValidationTest.php`

Modify:
- `routes/web.php` — add public routes and private requirement file route.
- `routes/admin.php`, `routes/superadmin.php` — add combined validation routes.
- `app/Models/DocumentRequest.php`, `app/Models/Payment.php`, `app/Models/RequestRequirement.php` — fillable/casts/relationships/helpers.
- `app/Http/Controllers/FileController.php` — serve request requirement files.
- `app/Http/Controllers/Admin/RequestController.php` — combined approve/deny actions and show payload.
- `app/Services/RequestService.php`, `app/Services/PaymentService.php` — support nullable users/public request approval without breaking legacy flow.
- `resources/js/Pages/Welcome.vue` — replace registration CTA with request/track actions.
- `resources/js/Pages/Admin/Requests/Show.vue` — requestor snapshot, receipt preview, combined validation controls, fixed file URLs.
- `resources/js/Pages/Admin/Payments/Index.vue` — keep legacy queue wording clear.
- Existing tests that assume payment upload is locked until request approval. Update only where behavior is intentionally superseded for public intake; keep legacy student tests if routes remain.

## Data Model Decisions

- Do not create hidden `users` rows for public requestors.
- `document_requests.user_id` becomes nullable.
- `payments.user_id` becomes nullable.
- Public requestor fields live on `document_requests` as snapshot columns:
  - `requester_name`
  - `requester_email`
  - `requester_contact_number`
  - `requester_student_id`
  - `requester_course`
  - `requester_year_level`
- Public request payment rows are created as `pending_approval` because the receipt is required during submission.
- Requirement and receipt files stay private on the `local` disk.
- Tracking by reference number returns a privacy-safe payload only.

---

### Task 1: Schema Migration And Models

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_support_public_document_requests.php`
- Modify: `app/Models/DocumentRequest.php`
- Modify: `app/Models/Payment.php`
- Test: `tests/Feature/Public/PublicRequestSubmissionTest.php`

- [ ] **Step 1: Write failing persistence test**

Add `tests/Feature/Public/PublicRequestSubmissionTest.php` with a test that posts a public request and asserts:

```php
$this->assertDatabaseHas('document_requests', [
    'user_id' => null,
    'requester_name' => 'Juan Dela Cruz',
    'requester_student_id' => 'SVCI-2026-001',
    'status' => 'pending',
]);

$this->assertDatabaseHas('payments', [
    'user_id' => null,
    'status' => 'pending_approval',
]);
```

Run:

```bash
php artisan test tests/Feature/Public/PublicRequestSubmissionTest.php
```

Expected: fails because routes/schema do not exist yet.

- [ ] **Step 2: Add migration**

Migration requirements:

```php
Schema::table('document_requests', function (Blueprint $table) {
    $table->foreignId('user_id')->nullable()->change();
    $table->string('requester_name', 150)->nullable()->after('user_id');
    $table->string('requester_email', 150)->nullable()->after('requester_name');
    $table->string('requester_contact_number', 30)->nullable()->after('requester_email');
    $table->string('requester_student_id', 50)->nullable()->after('requester_contact_number');
    $table->string('requester_course', 100)->nullable()->after('requester_student_id');
    $table->unsignedTinyInteger('requester_year_level')->nullable()->after('requester_course');
    $table->index('requester_student_id');
    $table->index('requester_email');
});

Schema::table('payments', function (Blueprint $table) {
    $table->foreignId('user_id')->nullable()->change();
});
```

If MySQL requires Doctrine DBAL for `change()`, use a Laravel-compatible migration approach already accepted by this repo.

- [ ] **Step 3: Update model fillable/casts**

Add requestor fields to `DocumentRequest::$fillable`; keep `requester_year_level` cast as integer if useful. Confirm `Payment::$fillable` remains compatible with nullable `user_id`.

- [ ] **Step 4: Run focused tests**

```bash
php artisan test tests/Feature/Public/PublicRequestSubmissionTest.php
```

- [ ] **Step 5: Commit**

```bash
git add database/migrations app/Models tests/Feature/Public/PublicRequestSubmissionTest.php
git commit -m "feat: support public request persistence"
```

### Task 2: Public Request Backend

**Files:**
- Create: `app/Http/Controllers/Public/DocumentRequestController.php`
- Create: `app/Http/Requests/Public/StorePublicDocumentRequest.php`
- Create: `app/Services/PublicDocumentRequestService.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Public/PublicRequestSubmissionTest.php`

- [ ] **Step 1: Add validation tests**

Cover:
- missing receipt returns validation error,
- missing requestor name/student ID/course/year/contact returns validation errors,
- missing items returns validation error,
- required document-type requirement files are required,
- invalid file type is rejected,
- valid request stores files on `local`.

- [ ] **Step 2: Add public routes**

In `routes/web.php`, add guest-friendly routes before auth role groups:

```php
Route::get('/request-document', [PublicDocumentRequestController::class, 'create'])->name('public.requests.create');
Route::post('/request-document', [PublicDocumentRequestController::class, 'store'])
    ->middleware('throttle:public-requests')
    ->name('public.requests.store');
Route::get('/request-document/submitted/{reference}', [PublicDocumentRequestController::class, 'submitted'])
    ->name('public.requests.submitted');
```

Use aliases/imports matching actual class names.

- [ ] **Step 3: Implement `StorePublicDocumentRequest`**

Rules:

```php
'requester_name' => ['required', 'string', 'max:150'],
'requester_email' => ['nullable', 'email', 'max:150'],
'requester_contact_number' => ['required', 'string', 'max:30'],
'requester_student_id' => ['required', 'string', 'max:50'],
'requester_course' => ['required', 'string', 'max:100'],
'requester_year_level' => ['required', 'integer', 'min:1', 'max:8'],
'items' => ['required', 'array', 'min:1', 'max:10'],
'items.*.document_type_id' => ['required', 'integer', 'exists:document_types,id'],
'items.*.copies' => ['required', 'integer', 'min:1', 'max:20'],
'purpose' => ['required', 'string', 'min:5', 'max:500'],
'payment_method' => ['required', 'string', 'max:50'],
'payment_reference_number' => ['nullable', 'string', 'max:100'],
'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:5120'],
'requirements' => ['nullable', 'array'],
'requirements.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:5120'],
```

Add `withValidator()` to require files for selected document-type requirements after loading active document types and policy requirements.

- [ ] **Step 4: Implement `PublicDocumentRequestService`**

Inside one DB transaction:
- lock nothing user-specific because no user row exists,
- resolve active document types,
- compute line totals using `RequestService::computeLineTotal()` or equivalent shared method,
- create one `DocumentRequest` with first document type as primary,
- create `DocumentRequestItem` rows,
- seed `RequestRequirement` rows and immediately attach submitted requirement files with `status=submitted`,
- create `Payment` with `status=pending_approval`, `submitted_at=now()`, receipt path, method, reference, and total amount,
- log activity with actor null or system actor,
- dispatch request/payment submitted events or equivalent notifications to admins/SuperAdmins.

Store paths:

```php
payment-receipts/public/{documentRequestId}/{uuid}.{ext}
request-requirements/public/{documentRequestId}/{uuid}.{ext}
```

- [ ] **Step 5: Implement controller**

`create()` returns document type groups and active payment profile to `Public/RequestDocument`.

`store()` calls service, redirects to `public.requests.submitted` with reference number.

`submitted()` renders `Public/RequestSubmitted` with only reference number.

- [ ] **Step 6: Run focused tests**

```bash
php artisan test tests/Feature/Public/PublicRequestSubmissionTest.php
```

- [ ] **Step 7: Commit**

```bash
git add routes/web.php app/Http/Controllers/Public app/Http/Requests/Public app/Services/PublicDocumentRequestService.php tests/Feature/Public/PublicRequestSubmissionTest.php
git commit -m "feat: add public request submission backend"
```

### Task 3: Public Tracking

**Files:**
- Create: `app/Http/Controllers/Public/TrackDocumentController.php`
- Create: `app/Http/Requests/Public/TrackDocumentRequest.php`
- Create: `resources/js/Pages/Public/TrackDocument.vue`
- Create: `resources/js/Pages/Public/TrackResult.vue`
- Modify: `routes/web.php`
- Test: `tests/Feature/Public/PublicTrackingTest.php`

- [ ] **Step 1: Write tracking tests**

Assert:
- valid reference returns OK and includes status/stage/payment status,
- unknown reference shows generic not-found result,
- response does not include `requester_email`, `requester_contact_number`, `receipt_path`, `file_path`, or numeric internal IDs,
- repeated lookups are throttled if practical in feature tests.

- [ ] **Step 2: Add routes**

```php
Route::get('/track-document', [TrackDocumentController::class, 'create'])->name('track-document');
Route::post('/track-document', [TrackDocumentController::class, 'show'])
    ->middleware('throttle:public-tracking')
    ->name('track-document.show');
```

- [ ] **Step 3: Implement request validation**

```php
'reference_no' => ['required', 'string', 'max:20', 'regex:/^REQ-[0-9]{4}-[0-9]{6}$/'],
```

- [ ] **Step 4: Implement privacy-safe payload**

Payload fields only:
- `reference_no`
- `status`
- `processing_stage`
- `submitted_at`
- `expected_release_on`
- `denial_reason` only if status is `denied`
- `documents` names/copies/line totals
- `payment.status`
- `payment.total_amount`
- `clearance.overall_status` if present
- `claim_slip.claim_number` and `claim_date` only when ready/released and if already designed as public-safe

- [ ] **Step 5: Run focused tests**

```bash
php artisan test tests/Feature/Public/PublicTrackingTest.php
```

- [ ] **Step 6: Commit**

```bash
git add routes/web.php app/Http/Controllers/Public app/Http/Requests/Public resources/js/Pages/Public tests/Feature/Public/PublicTrackingTest.php
git commit -m "feat: add public reference tracking"
```

### Task 4: Private Requirement File Route

**Files:**
- Create: `app/Policies/RequestRequirementPolicy.php`
- Modify: `app/Http/Controllers/FileController.php`
- Modify: `routes/web.php`
- Modify: `resources/js/Pages/Admin/Requests/Show.vue`
- Test: `tests/Feature/Auth/SecurityHardeningTest.php`

- [ ] **Step 1: Write failing file access tests**

Assert:
- admin can download/open requirement file,
- superadmin can download/open requirement file,
- unrelated student cannot,
- direct `/storage/request-requirements/...` is not the generated UI link,
- missing/private path returns 404.

- [ ] **Step 2: Add policy**

`view(User $user, RequestRequirement $requirement)`:
- `admin` and `superadmin`: true,
- legacy `student`: true only when requirement request has `user_id === user.id`,
- department roles: false unless a specific requirement is needed for clearance review later.

- [ ] **Step 3: Add controller method**

In `FileController::requestRequirement(RequestRequirement $requirement)`:
- authorize `view`,
- load `documentRequest`,
- allow only paths starting with:
  - `request-requirements/public/{document_request_id}/`
  - `request-requirements/{user_id}/{document_request_id}/`
- require `Storage::disk('local')->exists($path)`,
- return `Storage::disk('local')->download($path)`.

- [ ] **Step 4: Add route**

```php
Route::get('/files/request-requirements/{requirement}', [FileController::class, 'requestRequirement'])
    ->name('files.request-requirement');
```

- [ ] **Step 5: Update Vue link**

Replace:

```vue
:href="`/storage/${req.file_path}`"
```

with a route generated by backend payload or Ziggy route:

```vue
:href="route('files.request-requirement', req.id)"
```

- [ ] **Step 6: Run focused tests**

```bash
php artisan test tests/Feature/Auth/SecurityHardeningTest.php
```

- [ ] **Step 7: Commit**

```bash
git add app/Policies/RequestRequirementPolicy.php app/Http/Controllers/FileController.php routes/web.php resources/js/Pages/Admin/Requests/Show.vue tests/Feature/Auth/SecurityHardeningTest.php
git commit -m "fix: serve request requirements through private route"
```

### Task 5: Combined Admin/SuperAdmin Validation

**Files:**
- Modify: `routes/admin.php`
- Modify: `routes/superadmin.php`
- Modify: `app/Http/Controllers/Admin/RequestController.php`
- Modify: `app/Services/RequestService.php`
- Modify: `app/Services/PaymentService.php`
- Modify: `resources/js/Pages/Admin/Requests/Show.vue`
- Test: `tests/Feature/Admin/PublicRequestValidationTest.php`

- [ ] **Step 1: Write validation tests**

Assert:
- approve-with-payment requires request status `pending`,
- approve-with-payment requires payment status `pending_approval`,
- approve-with-payment requires all requirements `validated`,
- success sets request `approved`, payment `approved`, request `approved_by`, payment `approved_by`,
- success creates clearance when needed,
- deny-with-payment requires reason and sets request/payment denied,
- denial reason appears in public tracking.

- [ ] **Step 2: Add routes**

Admin:

```php
Route::post('/requests/{documentRequest}/approve-with-payment', [RequestController::class, 'approveWithPayment'])->name('requests.approve-with-payment');
Route::post('/requests/{documentRequest}/deny-with-payment', [RequestController::class, 'denyWithPayment'])->name('requests.deny-with-payment');
```

SuperAdmin: add equivalent names under `superadmin.requests.*`.

- [ ] **Step 3: Add service method**

`RequestService::approvePublicRequestPackage(DocumentRequest $request, User $admin, PaymentService $payments)` should:
- assert status `pending`,
- assert all requirements validated,
- find first payment and assert `pending_approval`,
- approve request,
- approve payment,
- run in a transaction or make method transaction-safe.

`RequestService::denyPublicRequestPackage(DocumentRequest $request, User $admin, string $reason)` should:
- set request denied with reason,
- set related non-approved payments denied with same reason,
- notify requestor email when present.

- [ ] **Step 4: Update controller**

Add `approveWithPayment()` and `denyWithPayment()` with policy checks and validation.

- [ ] **Step 5: Update admin request detail UI**

Show:
- requestor snapshot panel,
- requirement status/actions,
- payment receipt link,
- payment amount/method/reference/status,
- approve package button when eligible,
- deny package form with reason.

Keep existing legacy approve/stage controls available for old authenticated-student records only when still valid.

- [ ] **Step 6: Run focused tests**

```bash
php artisan test tests/Feature/Admin/PublicRequestValidationTest.php
```

- [ ] **Step 7: Commit**

```bash
git add routes/admin.php routes/superadmin.php app/Http/Controllers/Admin/RequestController.php app/Services resources/js/Pages/Admin/Requests/Show.vue tests/Feature/Admin/PublicRequestValidationTest.php
git commit -m "feat: validate public request packages"
```

### Task 6: Public Frontend And Navigation

**Files:**
- Modify: `resources/js/Pages/Welcome.vue`
- Create/modify: `resources/js/Pages/Public/RequestDocument.vue`
- Create/modify: `resources/js/Pages/Public/RequestSubmitted.vue`
- Create/modify: `resources/js/Pages/Public/TrackDocument.vue`
- Create/modify: `resources/js/Pages/Public/TrackResult.vue`
- Create: `resources/js/Components/Public/FileUploadField.vue`
- Browser test: `tests/Browser/public-request-flow.spec.ts`

- [ ] **Step 1: Update landing**

Change public CTA:
- primary: `Request Document` → `/request-document`,
- secondary: `Track Document` → `/track-document`,
- keep `Log in` for staff/admin/SuperAdmin,
- hide `Create account`.

- [ ] **Step 2: Build request form**

Use existing document cart behavior from `Student/Requests/Create.vue` as a pattern. Include:
- requestor fields,
- document selector/copies,
- purpose,
- requirement upload controls for selected document requirements,
- payment instructions/profile,
- payment method/reference,
- receipt upload,
- submit button with progress/errors.

- [ ] **Step 3: Build confirmation**

Show:
- reference number in large readable text,
- short instruction to use Track Document,
- no private file links.

- [ ] **Step 4: Build tracking pages**

`TrackDocument.vue`: reference input form.

`TrackResult.vue`: status timeline and denial reason when applicable.

- [ ] **Step 5: Add browser smoke**

Playwright should:
- visit `/`,
- click `Request Document`,
- assert no password field,
- fill a minimal request with fake file,
- submit,
- see reference number,
- visit tracking page and lookup the reference.

- [ ] **Step 6: Run frontend checks**

```bash
npm run lint
npm run build
```

- [ ] **Step 7: Commit**

```bash
git add resources/js tests/Browser/public-request-flow.spec.ts
git commit -m "feat: add public request and tracking UI"
```

### Task 7: Notification And Email Adjustments

**Files:**
- Modify: `app/Notifications/WorkflowStatusNotification.php`
- Modify: service/controller notification calls touched above
- Test: `tests/Feature/Notifications/BroadcastNotificationRegressionTest.php`
- Test: `tests/Feature/Public/PublicRequestSubmissionTest.php`

- [ ] **Step 1: Add notification tests**

Assert:
- admins/SuperAdmins receive database/broadcast notification for public request submission,
- requestor email receives approval/denial when email is present,
- no requestor email is attempted when email is null,
- notification payloads do not include receipt paths or private file paths.

- [ ] **Step 2: Implement mail routing**

Use `Notification::route('mail', $request->requester_email)` for public requestor emails when no `User` exists.

- [ ] **Step 3: Keep staff notifications authenticated**

Continue sending database/broadcast notifications to active `admin` and `superadmin` users.

- [ ] **Step 4: Run notification tests**

```bash
php artisan test --filter=Notification
php artisan test tests/Feature/Public/PublicRequestSubmissionTest.php
```

- [ ] **Step 5: Commit**

```bash
git add app/Notifications app/Services tests/Feature
git commit -m "feat: notify staff and public requestors"
```

### Task 8: Documentation And Legacy Route Hiding Closeout

**Files:**
- Modify docs touched by implementation if behavior differs from this plan.
- Modify: `resources/js/Layouts/*` and nav components that expose student links.
- Modify: `resources/js/Pages/Auth/Login.vue` only if adding password visibility for staff login.

- [ ] **Step 1: Hide legacy student entry points**

Confirm public navigation no longer links to:
- `/register`,
- `/student/requests/new`,
- `/student/payments`.

Do not delete route files or Vue pages.

- [ ] **Step 2: Optional password visibility**

If still relevant after hiding registration, add a staff-login password visibility toggle to `Auth/Login.vue` only. Do not spend time on removed public registration UX.

- [ ] **Step 3: Update docs if implementation differs**

Update:
- `docs/04-database-schema.md`,
- `docs/06-user-flows.md`,
- `docs/07-routes-and-controllers.md`,
- `docs/18-uat-script.md`.

- [ ] **Step 4: Commit**

```bash
git add resources/js docs
git commit -m "docs: align public request handoff"
```

### Task 9: Full Verification

**Files:** all changed files.

- [ ] **Step 1: Run focused public tests**

```bash
php artisan test --filter=PublicRequest
php artisan test --filter=PublicTracking
php artisan test --filter=PublicRequestValidation
```

- [ ] **Step 2: Run security/private file tests**

```bash
php artisan test --filter=SecurityHardening
```

- [ ] **Step 3: Run full backend and quality gates**

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress
```

- [ ] **Step 4: Run frontend gates**

```bash
npm run lint
npm run build
```

- [ ] **Step 5: Review diff**

```bash
git diff --stat
git diff -- docs routes app resources tests
```

Check:
- no hidden user creation,
- no public `/storage/request-requirements` links,
- no private paths in public tracking payload,
- legacy student pages/routes hidden but not deleted,
- denial reason is requestor-visible.

- [ ] **Step 6: Final commit if needed**

```bash
git add .
git commit -m "test: verify public request intake"
```

## Open Risks

- Reference-only tracking is intentionally less private than reference plus verification field. Mitigation is limited status payload and rate limiting.
- Public uploads increase spam risk. Mitigate with throttling, strict file validation, max file sizes, and optional CAPTCHA later if abuse appears.
- Nullable `user_id` may uncover assumptions in existing policies, notifications, factories, and reports. Tests should catch these, but implementation must audit all `->user` dereferences on request/payment paths.
- Department clearance currently expects a student/user relationship in places. Public request clearance may need requestor snapshot display instead of `clearance->user` in UI/PDF paths.

## Definition Of Done

- Public requestor can submit without account/password.
- Required receipt and requirement files upload during submission.
- No student user is created.
- Reference number confirmation is shown.
- Tracking by reference number works and is privacy-safe.
- Admin/SuperAdmin can validate attachments and payment together.
- Deny whole request with reason works.
- Requirement file preview no longer 404s.
- Existing staff/admin/department/SuperAdmin flows still pass tests.
- Student self-service pages are hidden from public navigation, not deleted.
