# SVCI Revamp Agent Guide

This repository uses `AGENTS.md` as the main AI instruction entrypoint.

## Primary Goal

Implement the SVCI Online Document System revamp according to:
- `docs/README.md`
- `docs/00-overview.md` through `docs/15-testing-strategy.md`
- `docs/plan/README.md`
- `docs/plan/phase-00-setup.md` through `docs/plan/phase-14-post-launch.md`

## Required Read Order Per Task

1. `docs/plan/README.md`
2. Active phase file in `docs/plan/`
3. `docs/02-architecture.md`
4. `docs/03-roles-and-permissions.md`
5. `docs/07-routes-and-controllers.md`
6. Feature-specific docs (security, storage, realtime, testing, deployment)

## Project Rule Sources

Detailed Cursor rules are in:
- `.cursor/rules/00-project-core.mdc`
- `.cursor/rules/10-workflow-quality.mdc`

Always follow those before implementation.

## Commit Cadence

After each completed atomic task, create a small focused commit.
Avoid large mixed commits spanning unrelated concerns.

## Default Skills / Agents

Prefer these first:
- Skills: `coding-standards`, `backend-patterns`, `frontend-patterns`, `tdd-workflow`, `security-review`, `verification-loop`
- Subagents: `planner`, `tdd-guide`, `database-reviewer`, `security-reviewer`, `code-reviewer`, `verify-app`, `doc-updater`

## High-Relevance Paths

- `docs/plan/`
- `docs/02-architecture.md`
- `docs/03-roles-and-permissions.md`
- `docs/04-database-schema.md`
- `docs/07-routes-and-controllers.md`
- `docs/10-security.md`
- `docs/15-testing-strategy.md`

Expected app layout once implementation is underway:
- `app/Http/Controllers/`, `app/Http/Requests/`, `app/Policies/`, `app/Services/`
- `app/Events/`, `app/Notifications/`
- `resources/js/Pages/`, `resources/js/Components/`, `resources/js/Layouts/`
- `routes/student.php`, `routes/admin.php`, `routes/department.php`, `routes/superadmin.php`, `routes/channels.php`
- `database/migrations/`, `database/factories/`, `database/seeders/`
- `tests/Unit/`, `tests/Feature/`, `tests/Browser/`
