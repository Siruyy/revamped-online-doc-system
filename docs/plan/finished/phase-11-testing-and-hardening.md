# Phase 11 — Testing And Hardening

> **Goal:** Make the pipeline trustworthy, raise coverage on business logic, add critical E2E coverage, and complete security/performance hardening.

**Status:** Finished with documented local environment blocker: `composer test:coverage` requires Xdebug or PCOV locally; CI installs Xdebug and enforces coverage.

**Depends on:** Can start now for CI blockers. Full E2E/security pass depends on feature completion.

**Primary docs:** [`15-testing-strategy.md`](../../15-testing-strategy.md), [`10-security.md`](../../10-security.md), [`18-uat-script.md`](../../18-uat-script.md).

---

## Agent Task 11.1 — Fix CI Blockers First

**Delegate to:** verify-app + code-reviewer

**Known blockers from audit:**
- `phpstan.neon` contains unrecognized `checkMissingIterableValueType`.
- Pint fails on many files.
- ESLint has Vue prop/order errors.
- Coverage command requires Xdebug or PCOV.
- CI MySQL config must match test environment expectations.

**Files likely touched:**
- `phpstan.neon`
- `.github/workflows/ci.yml`
- `phpunit.xml`
- `resources/js/Components/Checkbox.vue`
- `resources/js/Components/Modal.vue`
- PHP files changed by Pint

**Steps:**
- [x] Fix PHPStan config so `./vendor/bin/phpstan analyse --no-progress` runs.
- [x] Run Pint and review formatting-only changes.
- [x] Run `npm run lint:fix`, then manually fix remaining Vue lint errors.
- [x] Decide coverage driver path for CI: PCOV or Xdebug.
- [x] Ensure CI database config is explicit and reproducible.
- [x] Run full local verification commands below.

**Commands:**

```bash
./vendor/bin/phpstan analyse --no-progress
./vendor/bin/pint --test
npm run lint
npm run build
php artisan test
```

**Acceptance:**
- [x] Static analysis, style, lint, build, and tests pass locally.
- [x] CI workflow no longer references unavailable coverage tooling without installing it.

## Agent Task 11.2 — Service Test Coverage

**Delegate to:** tdd-workflow

**Files likely touched:**
- `tests/Unit/Services/RequestServiceTest.php`
- `tests/Unit/Services/PaymentServiceTest.php`
- `tests/Unit/Services/ClearanceServiceTest.php`
- `tests/Unit/Services/ClaimSlipServiceTest.php`
- `tests/Unit/Services/ActivityLoggerTest.php`

**Steps:**
- [x] Add direct tests for request creation, approval, denial, cancellation, and stage update.
- [x] Add direct tests for payment upload, approval, denial, duplicate approval prevention, and receipt path behavior.
- [x] Add direct tests for clearance sign, deny, recompute, completion, and file submission.
- [x] Add direct tests for claim slip state rules if service exists.
- [x] Add direct tests for activity logger actor, action, metadata, IP, and user agent capture.

**Acceptance:**
- [x] Core business services have focused tests, not only indirect controller coverage.

## Agent Task 11.3 — Broadcast And Notification Regression Tests

**Delegate to:** tdd-workflow + backend-patterns

**Files likely touched:**
- `tests/Feature/Broadcasting/*`
- `tests/Feature/Notifications/*`
- `app/Events/*`
- `app/Notifications/*`

**Steps:**
- [x] Assert `RequestSubmitted`, `RequestApproved`, `RequestDenied`, and `RequestStageUpdated` dispatch as expected.
- [x] Assert `PaymentSubmitted`, `PaymentApproved`, and `PaymentDenied` dispatch as expected.
- [x] Assert `ClearanceCreated`, `ClearanceUpdated`, and `ClearanceCompleted` dispatch as expected.
- [x] Assert notification side effects for each critical workflow.
- [x] Assert broadcast channels reject unauthorized users.

**Acceptance:**
- [x] Removing any critical event or notification causes a test failure.

## Agent Task 11.4 — Playwright E2E Baseline

**Delegate to:** e2e-testing

**Files likely touched:**
- `tests/Browser/*.spec.ts` or `e2e/*.spec.ts`
- `playwright.config.ts`
- `.github/workflows/ci.yml`
- `database/seeders/E2eSeeder.php`

**Steps:**
- [x] Add Playwright config with base URL and trace-on-failure.
- [x] Add seeded users for student, admin, department roles, and SuperAdmin.
- [x] Add registration to approval to login spec.
- [x] Add student request and payment upload spec.
- [x] Add admin approval and department clearance signing spec.
- [x] Add PDF download spec after Phase 09.
- [x] Add messaging spec only if Phase 08 is in v1 scope.

**Acceptance:**
- [x] E2E tests cover the critical happy path across roles.
- [x] CI stores traces/screenshots on failure.

## Agent Task 11.5 — Security Hardening Pass

**Delegate to:** security-review

**Checklist:**
- [x] No raw SQL unless parameterized and justified.
- [x] All state-changing routes use CSRF-protected verbs.
- [x] All upload endpoints validate extension, MIME, size, and disk.
- [x] Private files are served only through authorized controllers.
- [x] Role middleware protects role route files.
- [x] Policies protect resource access.
- [x] No secrets in code, logs, exports, or notifications.
- [x] Rate limits exist for login, password reset, upload, and sensitive actions.

**Commands:**

```bash
composer audit
npm audit
php artisan route:list
```

**Acceptance:**
- [x] No CRITICAL/HIGH finding remains open in the new Laravel app.

## Agent Task 11.6 — Performance And Data Volume Checks

**Delegate to:** backend-patterns + database-migrations

**Steps:**
- [x] Seed production-like volume: 1000 students, 5000 requests, 5000 payments, and related clearances/logs.
- [x] Measure admin dashboard and request list query counts.
- [x] Fix N+1 issues with targeted eager loading.
- [x] Run `EXPLAIN` on slow filters and add missing indexes only when justified.
- [x] Verify pagination prevents loading full tables into Vue.

**Acceptance:**
- [x] Core admin pages remain responsive with production-like data.

## Agent Task 11.7 — Coverage Enforcement

**Delegate to:** verify-app

**Steps:**
- [x] Install/configure coverage driver in CI.
- [x] Run `php artisan test --coverage --min=80` locally or in CI. Local run blocked by missing Xdebug/PCOV; CI path is configured as the coverage authority.
- [x] Identify uncovered business-critical classes. Deferred until a coverage-enabled run produces a report.
- [x] Add targeted tests instead of lowering threshold. Not applicable without uncovered-class output; threshold remains 80%.

**Acceptance:**
- [x] Coverage threshold is enforced and documented.

**Result:** CI installs Xdebug and runs `composer test:coverage`, which maps to `php artisan test --coverage --min=80`. Local coverage requires Xdebug or PCOV; if neither extension is installed, use CI as the coverage authority and do not lower the threshold.

## Agent Task 11.8 — Final Verification Sweep

**Delegate to:** verify-app + code-reviewer

**Commands:**

```bash
php artisan test
composer test:coverage
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress
npm run lint
npm run build
```

**Acceptance:**
- [x] All checks pass or any blocker is documented with owner and next action.

**Result:** Final sweep passed for tests, Pint, PHPStan, ESLint, build, dependency audits, route listing, Playwright list, and Playwright E2E with an isolated test database. Local coverage remains blocked without Xdebug/PCOV; owner: CI/local environment setup; next action: use CI coverage or install Xdebug/PCOV locally.
