# Implementation Plan — SVCI Online Document System

This folder tracks remaining work for the Laravel 13 + Inertia + Vue rewrite.

## Current State

Core MVP exists for setup, schema, auth, student, and admin flows. Remaining work is mostly closeout, realtime/notifications, messaging, PDF/export, hardening, deployment, and legacy migration.

## Active Phases

| # | Phase | File | Status | Suggested Delegate |
|---|-------|------|:------:|--------------------|
| 05 | Department Clearance Closeout | [`phase-05-department-clearance.md`](./phase-05-department-clearance.md) | Active closeout | backend + tests |
| 06 | SuperAdmin Closeout | [`phase-06-superadmin-features.md`](./phase-06-superadmin-features.md) | Active closeout | backend + security |
| 07 | Real-Time & Notifications | [`phase-07-realtime-and-notifications.md`](./phase-07-realtime-and-notifications.md) | Partial | backend + realtime |
| 08 | Messaging | [`phase-08-messaging.md`](./phase-08-messaging.md) | Deferred | backend + frontend |
| 09 | PDF Generation & Exports | [`phase-09-pdf-and-exports.md`](./phase-09-pdf-and-exports.md) | Partial | backend + reporting |
| 10 | UI/UX Polish | [`phase-10-ui-polish.md`](./phase-10-ui-polish.md) | Not started | frontend + accessibility |
| 11 | Testing & Hardening | [`phase-11-testing-and-hardening.md`](./phase-11-testing-and-hardening.md) | Not started | verification + security |
| 12 | Deployment | [`phase-12-deployment.md`](./phase-12-deployment.md) | Not started | deployment |
| 13 | Legacy Data Migration | [`phase-13-legacy-data-migration.md`](./phase-13-legacy-data-migration.md) | Not started | database + migration |
| 14 | Post-Launch | [`phase-14-post-launch.md`](./phase-14-post-launch.md) | Not started | support + docs |

## Finished Phases

Completed phases are archived in [`finished/`](./finished/). Do not edit archived phases for new work; create an active closeout task instead.

| # | Phase | File |
|---|-------|------|
| 00 | Project Setup & Tooling | [`finished/phase-00-setup.md`](./finished/phase-00-setup.md) |
| 01 | Database, Models & Seeders | [`finished/phase-01-database-and-models.md`](./finished/phase-01-database-and-models.md) |
| 02 | Auth, Roles & Approval Workflow | [`finished/phase-02-auth-and-roles.md`](./finished/phase-02-auth-and-roles.md) |
| 03 | Student Features | [`finished/phase-03-student-features.md`](./finished/phase-03-student-features.md) |
| 04 | Admin Features | [`finished/phase-04-admin-features.md`](./finished/phase-04-admin-features.md) |

## Recommended Execution Order

1. Phase 11 Task 11.1: fix CI blockers first (`phpstan.neon`, Pint, ESLint, coverage driver/CI DB parity).
2. Phase 07: manually verify Reverb, queue worker, and browser notification bell behavior.
3. Phase 09: add UI links and XLSX only if the client asks; CSV exports and clearance PDF generation now exist.
4. Phase 08: messaging is explicitly deferred from v1.
5. Phase 05 and 06: close out department and SuperAdmin gaps.
6. Phase 10: UI/accessibility polish.
7. Phase 12: production deployment artifacts and Dokploy setup.
8. Phase 13: legacy migration only after client confirms import is required.
9. Phase 14: post-launch handoff and operations.

## How To Use These Plans

- Each `Agent Task` is sized for one subagent or one focused implementation session.
- Before delegating, give the subagent the phase file, listed read-first docs, and current repo path.
- Do not mark a task complete until its acceptance checks and verification commands pass.
- If work is explicitly deferred, mark it as deferred in the phase notes and update this index.
- Keep docs aligned with code. If implementation differs from a phase plan, update the plan in the same change.

## Standard Verification Commands

Run the smallest relevant set first, then broaden before completion.

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

## Plan Maintenance Rules

See [`AGENTS.md`](./AGENTS.md) for planning-agent instructions scoped to this folder.
