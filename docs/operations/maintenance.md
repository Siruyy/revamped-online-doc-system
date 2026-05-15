# Maintenance Cadence

Use this cadence after launch to keep the system healthy.

## Monthly

- Review Composer security advisories and Laravel patch releases.
- Review npm security advisories and Vite/Vue patch releases.
- Review failed jobs and repeated support tickets.
- Confirm storage growth is expected for receipts, clearance files, signatures, and PDFs.
- Confirm backup success through the client-approved backup process.

## Quarterly

- Run a backup restore drill in a sandbox.
- Review audit logs for unusual user, payment, or document-type changes.
- Review browser/E2E coverage for workflows that changed during the quarter.
- Update role training guides when workflows change.

## Annually

- Plan PHP, Laravel, Node, and MySQL major-version upgrades.
- Review role permissions with stakeholders.
- Revisit deferred roadmap items in `docs/operations/v2-roadmap.md`.

## After Every Incident

- Record root cause, affected workflow, duration, fix commit, and prevention action.
- Add a regression test or manual verification item when feasible.
- Update support and training docs if user-facing behavior changed.
