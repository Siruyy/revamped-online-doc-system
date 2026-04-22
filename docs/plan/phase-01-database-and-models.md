# Phase 01 — Database, Models & Seeders

> **Goal:** Complete schema migrations, Eloquent models with relationships, factories, and seeders. No HTTP layer yet.

**Subagents:** `database-reviewer` (after migrations), `tdd-guide` (model tests), `code-reviewer`.
**Skills:** `database-migrations`.
**Depends on:** Phase 00.

---

## 1.1 Migrations

Create in dependency order. One migration per table.

- [ ] `users` migration — extend default with: role enum, status enum, fullname, course, year_level, student_id, contact_number, avatar_path, signature_path, approved_by, approved_at, soft deletes.
- [ ] `document_types` migration.
- [ ] `document_requests` migration with FKs to `users` and `document_types`.
- [ ] `payments` migration with FKs.
- [ ] `clearances` migration with all department status columns.
- [ ] `announcements` migration.
- [ ] `faqs` migration.
- [ ] `messages` migration with FKs (sender, receiver).
- [ ] `activity_logs` migration.
- [ ] Default Laravel migrations: `notifications` (`php artisan notifications:table`), `jobs`, `failed_jobs`, `cache`, `sessions` if using DB sessions.
- [ ] Add all indexes per [`docs/04-database-schema.md`](../docs/04-database-schema.md).
- [ ] Run `php artisan migrate:fresh` — succeeds.
- [ ] Invoke `database-reviewer` subagent on migration files.

## 1.2 Eloquent Models

- [ ] `User` model — fillable, casts, relationships (requests, payments, clearances, messages, signedClearances).
- [ ] `User` — scopes: `students()`, `staff()`, `pending()`, `active()`.
- [ ] `User` — methods: `isStudent()`, `isAdmin()`, `isDepartment()`, `isSuperAdmin()`.
- [ ] `DocumentType` model.
- [ ] `DocumentRequest` model — relationships, scopes (`pending`, `approved`, `byUser`).
- [ ] `DocumentRequest` — generated `reference_no` via boot/creating event.
- [ ] `Payment` model — relationships, scopes.
- [ ] `Clearance` model — relationships including 4 signer relationships (teacherSigner, deanSigner, etc.).
- [ ] `Clearance` — `isComplete()`, `recomputeOverallStatus()` methods.
- [ ] `Announcement` model.
- [ ] `Faq` model.
- [ ] `Message` model — relationships (sender, receiver), scope `unreadFor($user)`.
- [ ] `ActivityLog` model — `creating` event captures IP and user agent automatically.

## 1.3 Factories

- [ ] `UserFactory` with states: `student()`, `admin()`, `teacher()`, `dean()`, `accounting()`, `sao()`, `superadmin()`, `pending()`, `suspended()`.
- [ ] `DocumentTypeFactory`.
- [ ] `DocumentRequestFactory` with states: `pending()`, `approved()`, `denied()`, `completed()`.
- [ ] `PaymentFactory` with states: `pending()`, `pendingApproval()`, `approved()`, `denied()`.
- [ ] `ClearanceFactory` with states: `inProgress()`, `completed()`, `denied()`.
- [ ] `AnnouncementFactory`.
- [ ] `FaqFactory`.
- [ ] `MessageFactory`.

## 1.4 Seeders

- [ ] `RolesSeeder` (if using spatie/permission, otherwise N/A).
- [ ] `DocumentTypeSeeder` — seed common SVCI documents (TOR, Good Moral, Cert of Enrollment, Diploma, etc.).
- [ ] `SuperAdminSeeder` — creates one default SuperAdmin (credentials in `.env`, never hardcoded).
- [ ] `DemoDataSeeder` — for development only: students, requests, payments, clearances at various states.
- [ ] `DatabaseSeeder` orchestrates above.
- [ ] `ProductionSeeder` — only the minimum needed (DocumentTypes, SuperAdmin via env).

## 1.5 Console Commands

- [ ] `php artisan svci:make-superadmin {email}` — interactive command to create a SuperAdmin with prompted password.
- [ ] `php artisan svci:make-staff {email} {role}` — for admin/department roles.

## 1.6 Model Tests

- [ ] Test `User` scopes return correct subsets.
- [ ] Test `User` role helper methods.
- [ ] Test `DocumentRequest` reference_no auto-generation.
- [ ] Test `Clearance::isComplete()` logic for all 16 status combinations (parametrized).
- [ ] Test `Clearance::recomputeOverallStatus()` transitions.
- [ ] Test `Message::unreadFor()` scope.
- [ ] All tests pass.

## 1.7 Documentation

- [ ] Generate ER diagram via `php artisan db:show` or external tool, save to `docs/diagrams/`.
- [ ] Update `docs/04-database-schema.md` with any deviations from initial spec.

---

## Exit Criteria

- ✅ `php artisan migrate:fresh --seed` succeeds.
- ✅ All factories produce valid records.
- ✅ All model unit tests pass.
- ✅ `database-reviewer` subagent has approved the schema.
- ✅ Tinker session can create/relate all entities without errors.
