# Phase 01 — Database, Models & Seeders

> **Goal:** Complete schema migrations, Eloquent models with relationships, factories, and seeders. No HTTP layer yet.

**Subagents:** `database-reviewer` (after migrations), `tdd-guide` (model tests), `code-reviewer`.
**Skills:** `database-migrations`.
**Depends on:** Phase 00.

---

## 1.1 Migrations

Create in dependency order. One migration per table.

- [x] `users` migration — extend default with: role enum, status enum, fullname, course, year_level, student_id, contact_number, avatar_path, signature_path, approved_by, approved_at, soft deletes.
- [x] `document_types` migration.
- [x] `document_requests` migration with FKs to `users` and `document_types`.
- [x] `payments` migration with FKs.
- [x] `clearances` migration with all department status columns.
- [x] `announcements` migration.
- [x] `faqs` migration.
- [x] `messages` migration with FKs (sender, receiver).
- [x] `activity_logs` migration.
- [x] Default Laravel migrations: `notifications` (`php artisan notifications:table`), `jobs`, `failed_jobs`, `cache`, `sessions` if using DB sessions.
- [x] Add all indexes per [`docs/04-database-schema.md`](../../04-database-schema.md).
- [x] Run `php artisan migrate:fresh` — succeeds.
- [x] Invoke `database-reviewer` subagent on migration files.

## 1.2 Eloquent Models

- [x] `User` model — fillable, casts, relationships (requests, payments, clearances, messages, signedClearances).
- [x] `User` — scopes: `students()`, `staff()`, `pending()`, `active()`.
- [x] `User` — methods: `isStudent()`, `isAdmin()`, `isDepartment()`, `isSuperAdmin()`.
- [x] `DocumentType` model.
- [x] `DocumentRequest` model — relationships, scopes (`pending`, `approved`, `byUser`).
- [x] `DocumentRequest` — generated `reference_no` via boot/creating event.
- [x] `Payment` model — relationships, scopes.
- [x] `Clearance` model — relationships including 4 signer relationships (teacherSigner, deanSigner, etc.).
- [x] `Clearance` — `isComplete()`, `recomputeOverallStatus()` methods.
- [x] `Announcement` model.
- [x] `Faq` model.
- [x] `Message` model — relationships (sender, receiver), scope `unreadFor($user)`.
- [x] `ActivityLog` model — `creating` event captures IP and user agent automatically.

## 1.3 Factories

- [x] `UserFactory` with states: `student()`, `admin()`, `teacher()`, `dean()`, `accounting()`, `sao()`, `superadmin()`, `pending()`, `suspended()`.
- [x] `DocumentTypeFactory`.
- [x] `DocumentRequestFactory` with states: `pending()`, `approved()`, `denied()`, `completed()`.
- [x] `PaymentFactory` with states: `pending()`, `pendingApproval()`, `approved()`, `denied()`.
- [x] `ClearanceFactory` with states: `inProgress()`, `completed()`, `denied()`.
- [x] `AnnouncementFactory`.
- [x] `FaqFactory`.
- [x] `MessageFactory`.

## 1.4 Seeders

- [x] `RolesSeeder` (if using spatie/permission, otherwise N/A). *(N/A: current implementation uses role enum + policies)*
- [x] `DocumentTypeSeeder` — seed common SVCI documents (TOR, Good Moral, Cert of Enrollment, Diploma, etc.).
- [x] `SuperAdminSeeder` — creates one default SuperAdmin (credentials in `.env`, never hardcoded).
- [x] `DemoDataSeeder` — for development only: students, requests, payments, clearances at various states.
- [x] `DatabaseSeeder` orchestrates above.
- [x] `ProductionSeeder` — only the minimum needed (DocumentTypes, SuperAdmin via env).

## 1.5 Console Commands

- [x] `php artisan svci:make-superadmin {email}` — interactive command to create a SuperAdmin with prompted password.
- [x] `php artisan svci:make-staff {email} {role}` — for admin/department roles.

## 1.6 Model Tests

- [x] Test `User` scopes return correct subsets.
- [x] Test `User` role helper methods.
- [x] Test `DocumentRequest` reference_no auto-generation.
- [x] Test `Clearance::isComplete()` logic for all 16 status combinations (parametrized).
- [x] Test `Clearance::recomputeOverallStatus()` transitions.
- [x] Test `Message::unreadFor()` scope.
- [x] All tests pass.

## 1.7 Documentation

- [x] Generate ER diagram via `php artisan db:show` or external tool, save to `docs/diagrams/`.
- [x] Update `docs/04-database-schema.md` with any deviations from initial spec.

---

## Exit Criteria

- ✅ `php artisan migrate:fresh --seed` succeeds.
- ✅ All factories produce valid records.
- ✅ All model unit tests pass.
- ✅ `database-reviewer` subagent has approved the schema.
- ✅ Tinker session can create/relate all entities without errors.
