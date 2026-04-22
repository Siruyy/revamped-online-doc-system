# Cursor Rules Guide

Use this folder for project-specific Cursor AI behavior.

## Rule Files

- `00-project-core.mdc`
  - Core technical constraints and non-negotiables.
  - Includes phase discipline, architecture boundaries, security baseline, and data/query rules.

- `10-workflow-quality.mdc`
  - Workflow defaults and quality expectations.
  - Includes skills/subagent defaults, hook intent, definition-of-done, and commit discipline.

## When to Edit

- Edit `00-project-core.mdc` when changing technical constraints or implementation guardrails.
- Edit `10-workflow-quality.mdc` when changing team process, verification flow, or delivery standards.

## What Not to Put Here

- Do not move product specs or feature requirements into rules.
- Keep feature and phase requirements in `docs/` and `docs/plan/`.

## Sync Note

If rule intent changes significantly, confirm `AGENTS.md` still summarizes and points to the correct rule files.
