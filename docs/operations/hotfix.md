# Hotfix Process

Use this process for urgent production fixes after launch.

## Intake

1. Confirm severity using `docs/operations/support.md`.
2. Write the failing scenario in the ticket.
3. Identify the smallest safe fix.
4. Confirm whether the issue affects authorization, uploads, payments, private files, or user data.

## Branch And Review

1. Branch from the current release branch.
2. Add or update a regression test first.
3. Implement the smallest fix.
4. Run focused tests, then broaden based on risk.
5. Request code review before merge.

## Minimum Verification

- Backend fix: `php artisan test` for affected tests, then broaden if shared services changed.
- Frontend fix: `npm run lint` and `npm run build`.
- Authorization/upload/payment/private-file fix: include a security-focused review.
- Browser-flow fix: run the relevant `npm run test:e2e -- <spec>` command when feasible.

## Emergency Rollback Path

1. Stop new risky changes and identify the last known-good release commit.
2. If the hotfix introduced the incident, revert the hotfix commit with a new revert commit.
3. If data was changed, preserve the current database state before applying any corrective data script.
4. Re-run the minimum verification for the affected workflow.
5. Communicate the rollback result and any remaining workaround.

## Communication

- Announce user-visible workflow impact before the fix if P0/P1.
- Announce completion with the affected workflow, fixed behavior, and any user action needed.
- Do not include student PII, receipt paths, or private file names in announcements.
