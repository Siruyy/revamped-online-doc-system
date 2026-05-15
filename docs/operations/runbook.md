# Operations Runbook

This runbook covers routine checks for the SVCI document request system. Deployment platform commands are intentionally excluded from this closeout.

## Daily Checks

- Review Laravel logs for new errors.
- Review failed jobs and retry only after confirming the root cause.
- Confirm queues are processing notifications, PDFs, and broadcasts.
- Spot-check admin request, payment, and department clearance queues.

## Weekly Checks

- Review audit logs for unusual account, payment, or document-type changes.
- Confirm private file downloads still require authorization.
- Confirm CSV exports open for SuperAdmin and remain forbidden to non-SuperAdmin users.
- Review support tickets for repeated questions that should become FAQ updates.

## Manual Verification Log

Use `docs/operations/manual-verification-log.md` for each manual verification pass. Keep one dated section per run.

## Escalation

- Security or authorization issue: stop processing affected workflow and notify the project owner.
- Payment or clearance data mismatch: preserve current records, avoid manual database edits, and reproduce in test before fixing.
- Realtime issue only: confirm polling fallback works before classifying as critical.
