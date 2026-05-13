# Implementation Plan — SVCI Online Document System

This folder tracks remaining work for the Laravel 13 + Inertia + Vue rewrite.

## Current State

Core MVP exists for setup, schema, auth, student, admin, department, SuperAdmin, realtime/notifications, PDF/export, UI polish, and hardening flows. Remaining active work is messaging and post-launch handoff; deployment and legacy migration are not needed for the current requested scope.

## Active Phases

| # | Phase | File | Status | Suggested Delegate |
|---|-------|------|:------:|--------------------|
| 08 | Messaging | [`phase-08-messaging.md`](./phase-08-messaging.md) | Deferred | backend + frontend |
| 12 | Deployment | [`phase-12-deployment.md`](./phase-12-deployment.md) | Skipped for current scope | deployment |
| 13 | Legacy Data Migration | [`phase-13-legacy-data-migration.md`](./phase-13-legacy-data-migration.md) | Skipped for current scope | database + migration |
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
| 05 | Department Clearance Closeout | [`finished/phase-05-department-clearance.md`](./finished/phase-05-department-clearance.md) |
| 06 | SuperAdmin Closeout | [`finished/phase-06-superadmin-features.md`](./finished/phase-06-superadmin-features.md) |
| 07 | Real-Time & Notifications | [`finished/phase-07-realtime-and-notifications.md`](./finished/phase-07-realtime-and-notifications.md) |
| 09 | PDF Generation & Exports | [`finished/phase-09-pdf-and-exports.md`](./finished/phase-09-pdf-and-exports.md) |
| 10 | UI/UX Polish | [`finished/phase-10-ui-polish.md`](./finished/phase-10-ui-polish.md) |
| 11 | Testing & Hardening | [`finished/phase-11-testing-and-hardening.md`](./finished/phase-11-testing-and-hardening.md) |

## Recommended Execution Order

1. Phase 08: messaging remains explicitly deferred from v1.
2. Phase 14: post-launch handoff and operations if needed.
3. Manual operational checks: Reverb/browser notification flow, `queue:work`, Mailpit/Mailhog capture, and authenticated mobile/accessibility lab testing.
4. Phase 12 and Phase 13 are skipped for the current requested scope.

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
