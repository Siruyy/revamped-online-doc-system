# Phase 02 — Auth, Roles & Approval Workflow

> **Goal:** Login, registration with approval, password reset, role middleware, policies. The foundation for all role-gated features.

**Subagents:** `tdd-guide`, `security-reviewer` (mandatory at end of phase), `code-reviewer`.
**Skills:** `security-review`, `tdd-workflow`.
**Depends on:** Phase 01.

---

## 2.1 Breeze Customization

- [x] Customize `RegisteredUserController@store`:
    - Force role = `student`
    - Force status = `pending`
    - Send `RegistrationSubmittedNotification` to all SuperAdmins
    - Redirect to "registration pending approval" page (not auto-login)
- [x] Customize `LoginRequest::authenticate()`:
    - After credential check, verify `user.status === 'active'`
    - If `pending`: redirect with message
    - If `suspended` or `rejected`: redirect with message
- [x] Update Vue pages: `Auth/Register.vue` adds course, year_level, student_id, contact_number fields.
- [x] Update `Auth/Login.vue` styling per design system.
- [x] Add `Auth/RegistrationPending.vue` page.

## 2.2 Email Verification (Optional but recommended)

- [x] Enable email verification on `User` model (`MustVerifyEmail`).
- [x] Configure email driver (use Mailhog locally).
- [x] Test verification email sends.
- [x] Add `verified` middleware to student routes (after status check).

## 2.3 Custom Middleware

- [x] `EnsureRole` middleware — accepts comma-separated roles, e.g., `role:admin,superadmin`.
- [x] `EnsureApprovedAccount` middleware — `user.status === 'active'`.
- [x] Register middleware aliases in `bootstrap/app.php`.
- [x] Tests: middleware blocks/allows correctly.

## 2.4 Route Files

- [x] Create `routes/student.php`, `admin.php`, `department.php`, `superadmin.php`.
- [x] Wire from `web.php` with appropriate middleware groups (per [`docs/07-routes-and-controllers.md`](../../07-routes-and-controllers.md)).
- [x] Add stub controllers for each role's dashboard.
- [x] Test: each role redirected appropriately, others receive 403.

## 2.5 Policies

- [x] `DocumentRequestPolicy` — view, cancel, approve, deny, updateStage, delete.
- [x] `PaymentPolicy` — view, upload, approve, deny.
- [x] `ClearancePolicy` — view, sign, signFor (column-aware), deny, downloadPdf.
- [x] `UserPolicy` — view, approve, reject, suspend, delete.
- [x] Register all policies in `AuthServiceProvider`.
- [x] `Gate::before` for SuperAdmin override.
- [x] Policy unit tests for every method.

## 2.6 Password Reset

- [x] Verify Breeze password reset flow works.
- [x] Customize email template with brand styling.
- [x] Add throttling: max 3 reset requests per hour per email.
- [x] After reset, invalidate all other sessions.
- [x] Test full flow.

## 2.7 Login Throttling

- [x] Confirm Laravel's default throttle (`throttle:5,1`) on login route.
- [x] Add account lockout after 10 consecutive failures (24h cooldown).
- [x] Log all failed login attempts to `activity_logs` with IP.
- [x] Test brute-force scenario gets locked out.

## 2.8 Profile Management

- [x] `ProfileController` (per role or shared) — `edit`, `update`, `updatePassword`, `updateAvatar`, `updateSignature` (department only).
- [x] Form Requests with strict validation.
- [x] Avatar upload to public disk (with image resize via Intervention).
- [x] Signature upload to private disk (department roles only).
- [x] Vue pages: `Student/Profile.vue`, `Admin/Profile.vue`, `Department/Profile.vue`, `SuperAdmin/Profile.vue`.

## 2.9 SuperAdmin Approval Endpoints (early stub)

This unblocks the registration → approval → login flow in dev.

- [x] `SuperAdmin\UserController@pending` — list pending users.
- [x] `@approve` — set status=active, send `RegistrationApprovedNotification`.
- [x] `@reject` — set status=rejected with reason, notify.
- [x] Vue page: `SuperAdmin/Users/Pending.vue` with approve/reject buttons.

## 2.10 Activity Logging

- [x] `ActivityLogger` service or model observer.
- [x] Log: register, approve registration, reject registration, login (success/fail), logout, password change, profile update.
- [x] Test: log rows exist after each action.

## 2.11 Security Headers Middleware

- [x] Create `SecurityHeaders` middleware (X-Content-Type-Options, X-Frame-Options, Referrer-Policy, CSP, Permissions-Policy).
- [x] Apply globally.
- [x] Test headers present in response.

## 2.12 Rate Limiting Configuration

- [x] Define limiters in `RouteServiceProvider`: `login`, `registration`, `password-reset`, `api`.
- [x] Apply to relevant routes.
- [x] Test rate limit triggers 429.

## 2.13 Tests

- [x] `RegistrationTest` — successful registration creates pending user, notifies superadmin.
- [x] `RegistrationTest` — pending user cannot log in.
- [x] `RegistrationTest` — approved user can log in.
- [x] `LoginTest` — happy path, wrong password, suspended account, rate limit.
- [x] `PasswordResetTest` — full flow, expiry, throttling.
- [x] `RoleMiddlewareTest` — role checks pass/fail correctly.
- [x] `PolicyTest` — comprehensive coverage per policy.
- [x] All pass.

## 2.14 Security Review

- [x] Invoke `security-reviewer` subagent on auth-related code.
- [x] Address all CRITICAL and HIGH findings.
- [x] Document accepted MEDIUM findings.

Security review notes:
- No CRITICAL/HIGH findings were reported.
- Initial MEDIUM findings (avatar decompression risk, missing `verified` on privileged routes) were fixed in this phase.

---

## Exit Criteria

- ✅ Self-registration → pending → SuperAdmin approves → login works end-to-end.
- ✅ All four department roles can log in and reach a stub dashboard.
- ✅ Cross-role access attempts return 403.
- ✅ Security headers present, rate limits enforced, password reset works.
- ✅ Coverage 80%+ on auth code.
- ✅ Security review passed.
