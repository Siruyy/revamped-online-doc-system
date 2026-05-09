# Plan Folder Agent Guide

Instructions for editing `docs/plan/` only.

## Purpose

Plan files are work packages for future agents. They must be small, explicit, and delegatable.

## Folder Policy

- Active work stays directly under `docs/plan/`.
- Completed historical phases stay under `docs/plan/finished/`.
- Do not add new work to `finished/`.
- If a finished phase needs follow-up, create an active closeout task in the relevant current phase.

## Task Format

Use this structure for every delegatable task:

```markdown
## Agent Task N.N — Clear Task Name

**Delegate to:** skill-or-subagent names

**Read first:**
- `exact/path.md`
- `exact/code/path.php`

**Files likely touched:**
- `exact/path.php`
- `exact/path.vue`
- `tests/exact/path.php`

**Steps:**
- [ ] One concrete action.
- [ ] One concrete action.
- [ ] One concrete verification.

**Acceptance:**
- [ ] Observable done condition.
- [ ] Test or manual verification condition.
```

## Granularity Rules

- One task should fit one focused subagent session.
- Prefer 4-8 concrete steps per task.
- Split backend, frontend, security, deployment, and docs work when they can be delegated independently.
- Include exact files when known. Use `Files likely touched` when code must be inspected first.
- Include commands when verification is known.
- Avoid vague tasks like “finish UI” or “add tests.” State which UI and which tests.

## Status Rules

- `Not started`: no meaningful implementation found.
- `Partial`: implementation exists but known acceptance checks are missing.
- `Active closeout`: MVP exists but plan/code need reconciliation and gaps remain.
- `Finished`: all acceptance criteria are complete or deferred to named active phases.

## Moving Files To Finished

Before moving a phase to `finished/`:

- [ ] Active phase acceptance criteria are complete.
- [ ] Relevant tests and build checks pass.
- [ ] Deferred items are moved to another active phase and named in Phase Notes.
- [ ] `docs/plan/README.md` status table is updated.
- [ ] Links are adjusted if needed.

## Link Rules

- From active phase files in `docs/plan/`, link root docs as `../10-security.md`.
- From archived files in `docs/plan/finished/`, link root docs as `../../10-security.md` if editing links.
- Prefer relative links.

## Verification For Plan Edits

For docs-only plan edits, verify with:

```bash
git diff -- docs/plan AGENTS.md
```

Also scan for broken path patterns:

```bash
rg "\.\./docs/" docs/plan AGENTS.md
```

No PHP/JS test run is required for docs-only changes unless code was touched.
