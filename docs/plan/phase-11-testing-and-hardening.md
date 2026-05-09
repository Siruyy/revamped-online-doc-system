# Phase 11 — Testing And Hardening

> **Goal:** Make the pipeline trustworthy, raise coverage on business logic, add critical E2E coverage, and complete security/performance hardening.

**Status:** Not started. Audit found CI blockers that should be fixed before new feature work.

**Depends on:** Can start now for CI blockers. Full E2E/security pass depends on feature completion.

**Primary docs:** [`15-testing-strategy.md`](../15-testing-strategy.md), [`10-security.md`](../10-security.md), [`18-uat-script.md`](../18-uat-script.md).

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
- [ ] Fix PHPStan config so `./vendor/bin/phpstan analyse --no-progress` runs.
- [ ] Run Pint and review formatting-only changes.
- [ ] Run `npm run lint:fix`, then manually fix remaining Vue lint errors.
- [ ] Decide coverage driver path for CI: PCOV or Xdebug.
- [ ] Ensure CI database config is explicit and reproducible.
- [ ] Run full local verification commands below.

**Commands:**

```bash
./vendor/bin/phpstan analyse --no-progress
./vendor/bin/pint --test
npm run lint
npm run build
php artisan test
```

**Acceptance:**
- [ ] Static analysis, style, lint, build, and tests pass locally.
- [ ] CI workflow no longer references unavailable coverage tooling without installing it.

## Agent Task 11.2 — Service Test Coverage

**Delegate to:** tdd-workflow

**Files likely touched:**
- `tests/Unit/Services/RequestServiceTest.php`
- `tests/Unit/Services/PaymentServiceTest.php`
- `tests/Unit/Services/ClearanceServiceTest.php`
- `tests/Unit/Services/ClaimSlipServiceTest.php`
- `tests/Unit/Services/ActivityLoggerTest.php`

**Steps:**
- [ ] Add direct tests for request creation, approval, denial, cancellation, and stage update.
- [ ] Add direct tests for payment upload, approval, denial, duplicate approval prevention, and receipt path behavior.
- [ ] Add direct tests for clearance sign, deny, recompute, completion, and file submission.
- [ ] Add direct tests for claim slip state rules if service exists.
- [ ] Add direct tests for activity logger actor, action, metadata, IP, and user agent capture.

**Acceptance:**
- [ ] Core business services have focused tests, not only indirect controller coverage.

## Agent Task 11.3 — Broadcast And Notification Regression Tests

**Delegate to:** tdd-workflow + backend-patterns

**Files likely touched:**
- `tests/Feature/Broadcasting/*`
- `tests/Feature/Notifications/*`
- `app/Events/*`
- `app/Notifications/*`

**Steps:**
- [ ] Assert `RequestSubmitted`, `RequestApproved`, `RequestDenied`, and `RequestStageUpdated` dispatch as expected.
- [ ] Assert `PaymentSubmitted`, `PaymentApproved`, and `PaymentDenied` dispatch as expected.
- [ ] Assert `ClearanceCreated`, `ClearanceUpdated`, and `ClearanceCompleted` dispatch as expected.
- [ ] Assert notification side effects for each critical workflow.
- [ ] Assert broadcast channels reject unauthorized users.

**Acceptance:**
- [ ] Removing any critical event or notification causes a test failure.

## Agent Task 11.4 — Playwright E2E Baseline

**Delegate to:** e2e-testing

**Files likely touched:**
- `tests/Browser/*.spec.ts` or `e2e/*.spec.ts`
- `playwright.config.ts`
- `.github/workflows/ci.yml`
- `database/seeders/E2eSeeder.php`

**Steps:**
- [ ] Add Playwright config with base URL and trace-on-failure.
- [ ] Add seeded users for student, admin, department roles, and SuperAdmin.
- [ ] Add registration to approval to login spec.
- [ ] Add student request and payment upload spec.
- [ ] Add admin approval and department clearance signing spec.
- [ ] Add PDF download spec after Phase 09.
- [ ] Add messaging spec only if Phase 08 is in v1 scope.

**Acceptance:**
- [ ] E2E tests cover the critical happy path across roles.
- [ ] CI stores traces/screenshots on failure.

## Agent Task 11.5 — Security Hardening Pass

**Delegate to:** security-review

**Checklist:**
- [ ] No raw SQL unless parameterized and justified.
- [ ] All state-changing routes use CSRF-protected verbs.
- [ ] All upload endpoints validate extension, MIME, size, and disk.
- [ ] Private files are served only through authorized controllers.
- [ ] Role middleware protects role route files.
- [ ] Policies protect resource access.
- [ ] No secrets in code, logs, exports, or notifications.
- [ ] Rate limits exist for login, password reset, upload, and sensitive actions.

**Commands:**

```bash
composer audit
npm audit
php artisan route:list
```

**Acceptance:**
- [ ] No CRITICAL/HIGH finding remains open in the new Laravel app.

## Agent Task 11.6 — Performance And Data Volume Checks

**Delegate to:** backend-patterns + database-migrations

**Steps:**
- [ ] Seed production-like volume: 1000 students, 5000 requests, 5000 payments, and related clearances/logs.
- [ ] Measure admin dashboard and request list query counts.
- [ ] Fix N+1 issues with targeted eager loading.
- [ ] Run `EXPLAIN` on slow filters and add missing indexes only when justified.
- [ ] Verify pagination prevents loading full tables into Vue.

**Acceptance:**
- [ ] Core admin pages remain responsive with production-like data.

## Agent Task 11.7 — Coverage Enforcement

**Delegate to:** verify-app

**Steps:**
- [x] Install/configure coverage driver in CI.
- [ ] Run `php artisan test --coverage --min=80` locally or in CI. Local run blocked by missing Xdebug/PCOV; CI path is configured as the coverage authority.
- [ ] Identify uncovered business-critical classes. Deferred until a coverage-enabled run produces a report.
- [ ] Add targeted tests instead of lowering threshold. Not applicable without uncovered-class output; threshold remains 80%.

**Acceptance:**
- [x] Coverage threshold is enforced and documented.

**Result:** CI installs Xdebug and runs `composer test:coverage`, which maps to `php artisan test --coverage --min=80`. Local coverage requires Xdebug or PCOV; if neither extension is installed, use CI as the coverage authority and do not lower the threshold.

## Agent Task 11.8 — Final Verification Sweep

**Delegate to:** verify-app + code-reviewer

**Commands:**

```bash
php artisan test
php artisan test --coverage --min=80
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress
npm run lint
npm run build
```

**Acceptance:**
- [ ] All checks pass or any blocker is documented with owner and next action.
