# Implementation Plan — SVCI Online Document System (Revamped)

> Phased plan for the full Laravel 11 + Inertia + Vue rewrite. Each phase has its own file with checkboxed tasks. Tick them as you complete.

## Phases

| # | Phase | File | Status |
|---|-------|------|:------:|
| 00 | Project Setup & Tooling | [`phase-00-setup.md`](./phase-00-setup.md) | ⬜ |
| 01 | Database, Models & Seeders | [`phase-01-database-and-models.md`](./phase-01-database-and-models.md) | ⬜ |
| 02 | Auth, Roles & Approval Workflow | [`phase-02-auth-and-roles.md`](./phase-02-auth-and-roles.md) | ⬜ |
| 03 | Student Features | [`phase-03-student-features.md`](./phase-03-student-features.md) | ⬜ |
| 04 | Admin Features | [`phase-04-admin-features.md`](./phase-04-admin-features.md) | ⬜ |
| 05 | Department Clearance | [`phase-05-department-clearance.md`](./phase-05-department-clearance.md) | ⬜ |
| 06 | SuperAdmin Features | [`phase-06-superadmin-features.md`](./phase-06-superadmin-features.md) | ⬜ |
| 07 | Real-Time (Reverb) & Notifications | [`phase-07-realtime-and-notifications.md`](./phase-07-realtime-and-notifications.md) | ⬜ |
| 08 | Messaging | [`phase-08-messaging.md`](./phase-08-messaging.md) | ⬜ |
| 09 | PDF Generation & Excel Exports | [`phase-09-pdf-and-exports.md`](./phase-09-pdf-and-exports.md) | ⬜ |
| 10 | UI/UX Polish & Design System | [`phase-10-ui-polish.md`](./phase-10-ui-polish.md) | ⬜ |
| 11 | Testing & Hardening | [`phase-11-testing-and-hardening.md`](./phase-11-testing-and-hardening.md) | ⬜ |
| 12 | Deployment to DigitalOcean + Dokploy | [`phase-12-deployment.md`](./phase-12-deployment.md) | ⬜ |
| 13 | Data Migration from Legacy | [`phase-13-legacy-data-migration.md`](./phase-13-legacy-data-migration.md) | ⬜ |
| 14 | Post-Launch | [`phase-14-post-launch.md`](./phase-14-post-launch.md) | ⬜ |

## How to Use This Plan

- **Sequential by default** — earlier phases unblock later ones, but some can run in parallel (e.g., 04 admin and 05 department).
- **TDD first** — write tests before implementation per the [testing strategy](../docs/15-testing-strategy.md).
- **Use subagents** — for parallel research, code review, and security audits (see "Subagent Usage" below).
- **Tick checkboxes** as you complete each task — partial completion is OK.
- **Update phase status** in this index when a phase is done.

## Subagent Usage Per Phase

| Subagent | When to invoke |
|----------|----------------|
| `planner` | Before starting any phase, to expand the high-level plan into concrete steps |
| `architect` | At Phase 02–03 boundary, to validate service-layer design |
| `tdd-guide` | Every feature implementation — write tests first |
| `code-reviewer` | After every PR / batch of related changes |
| `security-reviewer` | After Phase 02 (auth), Phase 03 (uploads), Phase 11 (final audit) |
| `database-reviewer` | After Phase 01 migrations are written |
| `build-error-resolver` | When CI fails or local build breaks |
| `e2e-runner` | Phase 11, after critical flows are stable |
| `refactor-cleaner` | End of Phase 10 |
| `verify-app` | Before each deployment in Phase 12 |
| `doc-updater` | After each phase, to keep docs aligned with reality |

## Skills To Activate Per Phase

| Phase | Skills |
|-------|--------|
| 00 | `coding-standards`, `agent-customization` |
| 01 | `database-migrations`, `postgres-patterns` (analogues apply to MySQL) |
| 02 | `security-review`, `tdd-workflow` |
| 03–06 | `tdd-workflow`, `frontend-patterns`, `backend-patterns` |
| 07–08 | `backend-patterns` |
| 09 | `backend-patterns` |
| 10 | `ui-ux-pro-max`, `frontend-patterns` |
| 11 | `tdd-workflow`, `e2e-testing`, `security-review` |
| 12 | `deployment-patterns`, `docker-patterns` |
| 13 | `database-migrations` |

## Hooks Recommendations

- **PreToolUse** — block writes to `vendor/`, `node_modules/`.
- **PostToolUse** — auto-run `pint` after PHP file edits, `prettier`/`eslint` after Vue edits.
- **Stop** — run `composer audit` and `npm audit` summary.

## Rules To Enforce (Per Project Rules in `~/.claude/rules/`)

- TypeScript/JS rules apply to Vue files.
- Common rules: agents, coding-style, development-workflow, git-workflow, hooks, patterns, performance, security, testing.
- PHP-specific rules: not currently in `~/.claude/rules/php/` — recommend creating one (Laravel-flavored).
