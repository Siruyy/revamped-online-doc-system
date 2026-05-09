# Phase 13 — Legacy Data Migration

> **Goal:** If the client requires it, migrate eligible legacy data and files into the new Laravel schema with a repeatable, auditable process.

**Status:** Not started. Client confirmation required before implementation.

**Depends on:** Stable deployed app, schema freeze, client-approved migration scope.

**Primary docs:** [`04-database-schema.md`](../04-database-schema.md), [`16-policy-matrix.md`](../16-policy-matrix.md), [`10-security.md`](../10-security.md), [`11-file-storage.md`](../11-file-storage.md).

**Legacy sources:**
- `../document_system/` — raw MySQL/MariaDB table files, not a portable dump.
- `../document-request-system/` — legacy PHP app and upload folders.

---

## Agent Task 13.1 — Client Migration Decision

**Delegate to:** planner

**Steps:**
- [ ] Ask client whether legacy data must be migrated or system starts fresh.
- [ ] Confirm which data categories matter: users, document types, requests, payments, clearances, announcements, FAQs, messages, logs, notifications, uploads.
- [ ] Confirm whether old passwords should be invalidated. Recommended: force reset for all migrated users.
- [ ] Confirm retention requirements for legacy audit data and uploaded receipts.

**Acceptance:**
- [ ] Migration is explicitly approved or explicitly skipped.
- [ ] Scope is documented before code is written.

## Agent Task 13.2 — Obtain Portable Legacy Dump

**Delegate to:** database-migrations

**Steps:**
- [ ] Get recent `mysqldump` from the legacy production database.
- [ ] Restore dump into isolated sandbox MySQL.
- [ ] Record table list and row counts.
- [ ] Do not run migration directly against raw `.frm`/table files.
- [ ] Keep dump outside git and document storage location securely.

**Acceptance:**
- [ ] Sandbox DB is queryable and row counts are documented.

## Agent Task 13.3 — Legacy Data Quality Audit

**Delegate to:** database-reviewer

**Known risks from audit:**
- Two request concepts: `requests` and `document_requests`.
- Payments link inconsistently by `request_id`, user, or latest payment logic.
- Clearances mostly link by user, not request/payment.
- Status names differ across tables.
- Upload paths are raw public filenames.

**Steps:**
- [ ] Find duplicate emails and student IDs.
- [ ] Find orphaned requests, payments, clearances, messages, and notifications.
- [ ] Find missing upload files referenced by DB.
- [ ] Count each status value per legacy table.
- [ ] Produce client-readable issue report.

**Acceptance:**
- [ ] Client knows what will be imported, skipped, merged, or cleaned.

## Agent Task 13.4 — Mapping Document

**Delegate to:** database-migrations + code-reviewer

**Files likely touched:**
- `docs/migration-mapping.md`

**Steps:**
- [ ] Map `users` to new `users` including role/status normalization.
- [ ] Map `document_types` to new `document_types`.
- [ ] Decide how `requests` and `document_requests` merge into new request tables.
- [ ] Map `payments` to new `payments` and define unresolved-link behavior.
- [ ] Map `clearances` to new `clearances` and define request linkage behavior.
- [ ] Map `announcements`, `faqs`, `messages`, and `logs`.
- [ ] Decide whether legacy notifications are imported or archived only.
- [ ] Define password reset strategy.

**Acceptance:**
- [ ] Every imported field has a target field or explicit skip reason.

## Agent Task 13.5 — Migration Command Skeleton

**Delegate to:** tdd-workflow + database-migrations

**Files likely touched:**
- `app/Console/Commands/PortLegacyData.php`
- `config/database.php`
- `tests/Feature/Console/PortLegacyDataCommandTest.php`

**Steps:**
- [ ] Add `legacy` DB connection config using env vars only.
- [ ] Create `php artisan svci:port-legacy --connection=legacy --dry-run`.
- [ ] Require confirmation before destructive target-table changes.
- [ ] Add dry-run summary for row counts and planned inserts/updates/skips.
- [ ] Add structured migration report output.

**Acceptance:**
- [ ] Dry run works without mutating new DB.
- [ ] No hardcoded legacy credentials exist.

## Agent Task 13.6 — Import Users And Reference Data

**Delegate to:** database-migrations + tdd-workflow

**Steps:**
- [ ] Import users with normalized email, role, status, course, year level, contact fields, avatar path, and signature path.
- [ ] Force reset passwords or mark migrated users for reset per approved strategy.
- [ ] Import document types with fee/category/description.
- [ ] Import announcements and FAQs after sanitizing content.
- [ ] Preserve stable legacy IDs in metadata or a mapping table for later imports.

**Acceptance:**
- [ ] Users and reference data import idempotently.

## Agent Task 13.7 — Import Requests, Payments, And Clearances

**Delegate to:** database-migrations + backend-patterns

**Steps:**
- [ ] Import request rows with generated reference numbers where missing.
- [ ] Normalize request statuses and processing stages.
- [ ] Link payments to imported requests when possible; log unresolved payments.
- [ ] Link clearances to imported users/requests where possible; log ambiguous records.
- [ ] Preserve denied/approved timestamps and actor IDs when resolvable.

**Acceptance:**
- [ ] Imported workflow history is internally consistent or documented as unresolved.

## Agent Task 13.8 — Import Files Safely

**Delegate to:** security-review + backend-patterns

**Legacy paths to consider:**
- `../document-request-system/uploads/`
- `../document-request-system/uploads/receipts/`
- `../document-request-system/uploads/avatars/`
- `../document-request-system/uploads/signatures/`

**Steps:**
- [ ] Scan legacy uploads for executable files and reject them.
- [ ] Copy receipts and clearance files to private disk.
- [ ] Copy avatars to public avatar disk after MIME validation.
- [ ] Copy signatures to private disk after MIME validation.
- [ ] Rename files to UUID-based paths.
- [ ] Log missing, rejected, and copied files.

**Acceptance:**
- [ ] No executable legacy upload is served by the new app.
- [ ] DB paths point to new storage layout.

## Agent Task 13.9 — Validation And Cutover Rehearsal

**Delegate to:** verify-app + database-reviewer

**Steps:**
- [ ] Run migration against fresh sandbox copy.
- [ ] Validate row counts against approved mapping.
- [ ] Spot-check 10 users, 10 requests, 10 payments, 10 clearances, and 10 files.
- [ ] Verify login/reset flow for migrated users.
- [ ] Verify imported files can be downloaded only by authorized users.
- [ ] Time the migration and document cutover window.

**Acceptance:**
- [ ] Migration can be repeated predictably before production cutover.

## Agent Task 13.10 — Cutover And Rollback Plan

**Delegate to:** deployment-patterns + doc-updater

**Files likely touched:**
- `docs/migration-cutover.md`
- `docs/operations/runbook.md`

**Steps:**
- [ ] Document pre-cutover backup and freeze steps.
- [ ] Document final dump, restore, migration, validation, and app switch steps.
- [ ] Document user communication before, during, and after migration.
- [ ] Keep legacy system read-only for agreed retention period.
- [ ] Define rollback trigger and rollback steps.

**Acceptance:**
- [ ] Client approves cutover and rollback plan before production migration.

## Legacy Security Warning

The legacy PHP app has known critical risks: SQL injection, unrestricted public upload risk, missing CSRF, and hardcoded DB credentials. Do not expose it publicly during migration except in a controlled maintenance/cutover window.
