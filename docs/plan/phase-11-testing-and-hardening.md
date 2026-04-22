# Phase 11 — Testing & Hardening

> **Goal:** Reach 80%+ coverage on business logic, full E2E coverage on critical flows, security audit clean, performance verified.

**Subagents:** `tdd-guide`, `e2e-runner`, `security-reviewer` (final audit), `verify-app`.
**Skills:** `tdd-workflow`, `e2e-testing`, `security-review`.
**Depends on:** All previous feature phases.

---

## 11.1 Coverage Audit

- [ ] Run `./vendor/bin/pest --coverage`.
- [ ] Identify gaps: services <90%, controllers <80%, policies <100%.
- [ ] Add targeted tests for gaps.
- [ ] CI enforces `--min=80`.

## 11.2 Feature Test Completeness

For each major flow, ensure these scenarios:

- [ ] Happy path
- [ ] Validation failure
- [ ] Authorization failure (wrong role)
- [ ] Authorization failure (other user's resource)
- [ ] State precondition failure (e.g., approve already-approved request)
- [ ] Concurrent action (two admins approving same request)

## 11.3 E2E Tests (Playwright)

Critical flows automated:
- [ ] `student-registration.spec.ts` — register → see pending page
- [ ] `superadmin-approval.spec.ts` — approve pending registration → student can log in
- [ ] `student-request.spec.ts` — full submit + payment upload flow
- [ ] `admin-approval.spec.ts` — approve payment + request, update stages
- [ ] `clearance-flow.spec.ts` — all 4 departments sign, PDF available
- [ ] `messaging.spec.ts` — student and admin chat in real time
- [ ] `password-reset.spec.ts` — full forgot/reset flow
- [ ] CI runs Playwright headless, captures traces on failure.

## 11.4 Security Audit (Final)

- [ ] Invoke `security-reviewer` subagent on the entire codebase.
- [ ] Run `composer audit` and `npm audit`.
- [ ] Manual checklist:
    - [ ] All forms have CSRF tokens
    - [ ] No raw SQL anywhere
    - [ ] All file upload endpoints validated
    - [ ] All routes have proper middleware
    - [ ] All resources have policy checks
    - [ ] No secrets in code or commits
    - [ ] Security headers present
    - [ ] Rate limiting in place
    - [ ] Account lockout works
- [ ] Fix all CRITICAL and HIGH findings.

## 11.5 Performance Testing

- [ ] Seed 1000 students, 5000 requests, 5000 payments.
- [ ] Load admin dashboard — should render <500ms.
- [ ] Load admin requests list (paginated 25) — should render <300ms.
- [ ] Verify N+1 queries via Laravel Debugbar — fix any found with `with()`.
- [ ] Run Apache Bench: `ab -n 1000 -c 10 http://localhost/admin/dashboard` — verify acceptable.

## 11.6 Database Optimization

- [ ] Run `EXPLAIN` on slowest queries.
- [ ] Verify all indexes from [`docs/04-database-schema.md`](../docs/04-database-schema.md) exist.
- [ ] Add missing indexes.
- [ ] Re-test performance.

## 11.7 Browser Compatibility

- [ ] Chrome / Edge — verified
- [ ] Firefox — verified
- [ ] Safari (macOS + iOS) — verified
- [ ] Chrome Mobile (Android) — verified

## 11.8 Failure Mode Testing

- [ ] Stop database mid-request — confirm graceful error.
- [ ] Stop Reverb — confirm fallback polling works.
- [ ] Disk full simulation — file upload fails gracefully.
- [ ] Email service down — queue retries, no crash.

## 11.9 Documentation Sync

- [ ] Invoke `doc-updater` subagent.
- [ ] Update README with final feature list.
- [ ] Update deployment doc with any learned tweaks.
- [ ] API docs (Inertia routes are documented in [`docs/07-routes-and-controllers.md`](../docs/07-routes-and-controllers.md)).

## 11.10 Verify-App Run

- [ ] Invoke `verify-app` subagent.
- [ ] All checks pass: build, lint, type check, tests.

---

## Exit Criteria

- ✅ ≥80% coverage on PHP, all critical flows in E2E.
- ✅ Security audit passed, no CRITICAL/HIGH findings open.
- ✅ Performance acceptable on production-sized data.
- ✅ Cross-browser tested.
- ✅ `verify-app` passes.
