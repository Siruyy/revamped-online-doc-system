# Phase 13 — Legacy Data Migration

> **Goal:** Port existing data from the legacy `document_system` MySQL database into the new schema, preserving history.

**Subagents:** `database-reviewer`, `code-reviewer`.
**Skills:** `database-migrations`.
**Depends on:** Phase 12 (production system live and stable).

> ⚠️ Skip this phase if the client opts to start with a fresh database. Confirm with client first.

---

## 13.1 Pre-Migration Audit

- [ ] Get a recent export of the legacy `document_system` database (mysqldump).
- [ ] Restore to a sandbox MySQL instance.
- [ ] Audit row counts per table.
- [ ] Identify orphaned rows (FKs that don't resolve).
- [ ] Identify duplicate emails / student IDs.
- [ ] Document data quality issues for client review.

## 13.2 Migration Plan

- [ ] Map legacy tables → new tables:
    - `users` → `users` (transform role values, set status='active' for existing)
    - `document_types` → `document_types`
    - `requests` → `document_requests` (status normalization)
    - `payments` → `payments`
    - `clearances` → `clearances`
    - `announcements` → `announcements`
    - `faqs` → `faqs`
    - `messages` → `messages`
    - `logs` → `activity_logs`
    - `notifications` → `notifications` (transform to Laravel format)
- [ ] Document field-level mappings in `docs/migration-mapping.md`.
- [ ] Decide: passwords kept or force-reset?
    - **Recommended:** Force reset. Old hashes may use different bcrypt config.
    - Generate password reset tokens for all migrated users; email reset links.

## 13.3 Migration Command

- [ ] Create `php artisan svci:port-legacy {--connection=legacy}` command.
- [ ] Steps:
    1. Connect to legacy DB (configured in `config/database.php` as a second connection)
    2. Disable foreign key checks
    3. Truncate target tables (with confirmation prompt)
    4. Port users → users (chunked 1000 at a time)
    5. Port document_types
    6. Port document_requests (resolve user FK)
    7. Port payments
    8. Port clearances
    9. Port announcements, faqs, messages, logs
    10. Re-enable foreign key checks
    11. Run validation queries
    12. Print summary report
- [ ] Idempotent — can be re-run safely (uses `updateOrCreate` with stable identifiers).

## 13.4 Data Transformation Rules

- [ ] Email lowercased and trimmed.
- [ ] Role values mapped (legacy may use different casing/spelling).
- [ ] Statuses mapped to new enum values.
- [ ] Reference numbers generated for legacy requests that don't have them.
- [ ] Receipt file paths verified to exist on disk; broken paths logged.
- [ ] HTML in announcements/FAQs sanitized (HTMLPurifier).

## 13.5 File Migration

- [ ] Copy `uploads/` directory from legacy server to new VPS.
- [ ] Reorganize files into new structure:
    - `uploads/payments/*` → `storage/app/private/payment-receipts/{userId}/*`
    - `uploads/clearances/*` → `storage/app/private/clearance-files/{userId}/*`
    - `uploads/avatars/*` → `storage/app/public/avatars/{userId}/*`
    - `uploads/signatures/*` → `storage/app/private/signatures/{userId}/*`
- [ ] Update DB rows to point to new paths.
- [ ] Verify random sample of files accessible.

## 13.6 Validation

- [ ] Row counts match (allowing for cleaned duplicates).
- [ ] Spot-check 10 random users — data intact.
- [ ] Spot-check 10 random requests — payment and clearance linked.
- [ ] All file paths resolvable.
- [ ] No broken FKs.

## 13.7 Cutover Plan

- [ ] **Pre-cutover (day before):**
    - [ ] Run migration against fresh sandbox copy of legacy data
    - [ ] Verify no errors
    - [ ] Brief client on cutover window
- [ ] **Cutover day:**
    - [ ] Take legacy system offline (display "migration in progress" page)
    - [ ] Final mysqldump from legacy
    - [ ] Restore to migration sandbox
    - [ ] Run migration command
    - [ ] Validate
    - [ ] Switch DNS / update bookmarks to new system
    - [ ] Send "we're back online" announcement
- [ ] **Post-cutover (week after):**
    - [ ] Monitor logs for missing data complaints
    - [ ] Keep legacy backup accessible for 90 days
    - [ ] Force password resets via mass email

## 13.8 Communication

- [ ] Email all users 1 week before: "We're upgrading the system."
- [ ] Email at cutover: "System is upgrading, will be back at X."
- [ ] Email after: "Welcome to the new system. Please reset your password using this link."

## 13.9 Rollback Plan

- [ ] Keep legacy server running (read-only) for 30 days.
- [ ] If critical issue: restore DNS to legacy temporarily.
- [ ] Document rollback steps clearly.

---

## Exit Criteria

- ✅ All eligible legacy data ported to new system.
- ✅ Files accessible via new paths.
- ✅ Users can log in (after password reset).
- ✅ No data loss outside documented quality issues.
- ✅ Cutover communication delivered.
