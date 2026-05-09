# SVCI Revamp Agent Guide

Project-scoped instructions for this repository only.

## Project Goal

Build the revamped SVCI Online Document Request and Management System with Laravel 13, Inertia.js, Vue 3, Tailwind CSS, MySQL, and Laravel Reverb.

Core workflows:
- Student registration with SuperAdmin approval.
- Student document requests and offline payment receipt upload.
- Admin request/payment/release management.
- Department clearance signing by teacher, dean, accounting, and SAO.
- SuperAdmin user, logs, reports, and system management.
- Notifications, realtime updates, PDFs/exports, deployment, and optional legacy migration.

## Read Order For Implementation Tasks

1. `docs/plan/README.md`
2. Active phase file in `docs/plan/`
3. `docs/plan/AGENTS.md` when editing plans
4. `docs/02-architecture.md`
5. `docs/03-roles-and-permissions.md`
6. `docs/07-routes-and-controllers.md`
7. Feature-specific docs:
   - Security: `docs/10-security.md`
   - Storage/uploads: `docs/11-file-storage.md`
   - Realtime: `docs/08-real-time.md`
   - Notifications/email: `docs/12-notifications-and-email.md`
   - PDFs: `docs/13-pdf-generation.md`
   - Testing: `docs/15-testing-strategy.md`
   - Deployment: `docs/14-deployment.md`
   - Policy matrix/UAT: `docs/16-policy-matrix.md`, `docs/18-uat-script.md`

## Current Plan State

Finished phases are archived in `docs/plan/finished/`.

Active work is in:
- `docs/plan/phase-05-department-clearance.md`
- `docs/plan/phase-06-superadmin-features.md`
- `docs/plan/phase-07-realtime-and-notifications.md`
- `docs/plan/phase-08-messaging.md`
- `docs/plan/phase-09-pdf-and-exports.md`
- `docs/plan/phase-10-ui-polish.md`
- `docs/plan/phase-11-testing-and-hardening.md`
- `docs/plan/phase-12-deployment.md`
- `docs/plan/phase-13-legacy-data-migration.md`
- `docs/plan/phase-14-post-launch.md`

Do not trust old phase status without checking the active phase file and current code.

## Known Audit Findings To Respect

- Fix CI/readiness blockers before major new features: PHPStan config, Pint formatting, ESLint errors, coverage driver/CI DB parity.
- Phase 07 is partial: notifications need `ShouldQueue`, `toBroadcast()`, full class catalog, side-effect tests, and manual Reverb verification.
- Phase 08 messaging is not implemented unless future code changes prove otherwise.
- Phase 09 PDF/export is not implemented; current clearance PDF behavior may be a stub.
- Phase 12 deployment artifacts are missing; only dev Docker setup was found during audit.
- Legacy PHP folders are insecure and should not be reused as-is.

## Legacy Context

Sibling client folders outside this repo:
- `../document-request-system/` — legacy PHP application.
- `../document_system/` — raw MySQL/MariaDB table files, not a portable SQL dump.

Legacy risks found during audit:
- SQL injection in legacy admin/superadmin files.
- Unrestricted public upload risk in legacy receipt upload.
- Missing CSRF across legacy forms.
- Hardcoded legacy DB credentials.

Do not copy legacy patterns into the Laravel app. Use legacy only for behavior understanding or migration mapping.

## Architecture Rules

- Keep controllers thin.
- Put business logic in services under `app/Services/`.
- Use Form Requests for validation.
- Use Policies and role middleware for authorization.
- Use events/listeners/notifications for side effects.
- Use private storage for receipts, signatures, clearance files, and PDFs.
- Serve private files only through authorized controllers.
- Avoid raw SQL unless parameterized and justified.
- Keep realtime as enhancement with polling fallback.

## Important Paths

- Routes: `routes/student.php`, `routes/admin.php`, `routes/department.php`, `routes/superadmin.php`, `routes/channels.php`
- Controllers: `app/Http/Controllers/`
- Requests: `app/Http/Requests/`
- Policies: `app/Policies/`
- Services: `app/Services/`
- Events: `app/Events/`
- Notifications: `app/Notifications/`
- Vue pages: `resources/js/Pages/`
- Vue components: `resources/js/Components/`
- Layouts: `resources/js/Layouts/`
- Tests: `tests/Unit/`, `tests/Feature/`, future `tests/Browser/` or Playwright folder

## Verification Commands

Use focused commands first, then broaden before claiming completion.

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress
npm run lint
npm run build
```

Coverage requires Xdebug or PCOV:

```bash
composer test:coverage
```

Realtime/manual verification usually needs:

```bash
php artisan reverb:start
php artisan queue:work
npm run dev
php artisan serve
```

## Workflow Rules

- Tests are required for behavior changes.
- If touching auth, authorization, uploads, private files, notifications, or user data, run a security-focused review before completion.
- If touching database schema or migration/import logic, use database migration review.
- If editing `docs/plan/`, keep tasks small enough for subagent delegation and follow `docs/plan/AGENTS.md`.
- Do not move a phase to `finished/` unless all acceptance checks pass or deferred work is explicitly moved to another active phase.
- For this project, the expected workflow is one focused commit after each completed task once verification passes.
- In interactive agent sessions, create that commit when the user has asked to work through a task or explicitly requested commits. If unsure, ask before committing.
- Commit messages must not include `Co-authored-by`, AI attribution, or generated-by trailers.
- Use `gh` for GitHub operations such as PRs, issues, checks, and comments.

## Cursor Rule Sources

Also follow:
- `.cursor/rules/00-project-core.mdc`
- `.cursor/rules/10-workflow-quality.mdc`
