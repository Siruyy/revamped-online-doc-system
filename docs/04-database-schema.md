# 04 — Database Schema

> Engine: MySQL 8 / InnoDB / utf8mb4_unicode_ci. All tables include `created_at` and `updated_at` (Laravel timestamps) unless noted.

## Entity Relationship Overview

```
users ──┬─< document_requests >── document_types
        │
        ├─< payments
        │
        ├─< clearances
        │
        ├─< notifications
        │
        ├─< messages (sender / receiver)
        │
        └─< activity_logs

announcements (standalone, authored by users)
faqs (standalone, authored by users)
```

Public document requests may have `document_requests.user_id = NULL` and store requestor details directly on the request. Do not create hidden `student` users for public requestors.

## Tables

### `users`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK | |
| fullname | VARCHAR(150) | |
| email | VARCHAR(150) UNIQUE | |
| email_verified_at | TIMESTAMP NULL | |
| password | VARCHAR(255) | bcrypt |
| role | ENUM('student','admin','teacher','dean','accounting','sao','superadmin') | |
| status | ENUM('pending','active','suspended','rejected') | default `pending` |
| course | VARCHAR(100) NULL | for students |
| year_level | TINYINT UNSIGNED NULL | for students (1–4) |
| student_id | VARCHAR(50) NULL UNIQUE | school-issued ID |
| contact_number | VARCHAR(30) NULL | |
| avatar_path | VARCHAR(255) NULL | relative to `storage/app/public` |
| signature_path | VARCHAR(255) NULL | for department staff |
| approved_by | BIGINT UNSIGNED NULL FK→users.id | who approved legacy registration or staff account |
| approved_at | TIMESTAMP NULL | |
| remember_token | VARCHAR(100) NULL | |
| timestamps | | |

**Indexes:** `email`, `role`, `status`, `(role, status)`.

### `document_types`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK | |
| name | VARCHAR(150) | e.g., "Transcript of Records" |
| description | TEXT NULL | |
| category | VARCHAR(100) NULL | e.g., "Academic", "Clearance" |
| fee | DECIMAL(10,2) | default 0.00 |
| processing_days | TINYINT UNSIGNED | default 3 |
| is_active | BOOLEAN | default true |
| timestamps | | |

### `document_requests`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK | |
| reference_no | VARCHAR(20) UNIQUE | human-friendly, e.g., `REQ-2026-000123` |
| user_id | BIGINT UNSIGNED NULL FK→users.id | nullable for public request intake |
| requester_name | VARCHAR(150) | public requestor/student full name snapshot |
| requester_email | VARCHAR(150) NULL | used for email notifications when provided |
| requester_contact_number | VARCHAR(30) | |
| requester_student_id | VARCHAR(50) | school-issued ID entered on request |
| requester_course | VARCHAR(100) | |
| requester_year_level | TINYINT UNSIGNED | expected 1-8 by validation |
| document_type_id | BIGINT UNSIGNED FK→document_types.id | |
| status | ENUM('pending','approved','denied','cancelled','completed') | default `pending` |
| processing_stage | ENUM('not_started','processing','ready_for_pickup','released') | default `not_started` |
| denial_reason | TEXT NULL | |
| approved_by | BIGINT UNSIGNED NULL FK→users.id | |
| approved_at | TIMESTAMP NULL | |
| released_at | TIMESTAMP NULL | |
| purpose | TEXT NULL | student's reason for request |
| timestamps | | |

**Indexes:** `user_id`, `reference_no`, `requester_student_id`, `requester_email`, `status`, `processing_stage`, `(user_id, status)`, `(status, created_at)`.

### `payments`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK | |
| user_id | BIGINT UNSIGNED NULL FK→users.id | nullable for public request intake |
| document_request_id | BIGINT UNSIGNED FK→document_requests.id | required for public request intake |
| total_amount | DECIMAL(10,2) | |
| receipt_path | VARCHAR(255) NULL | |
| payment_method | VARCHAR(50) NULL | e.g., "Cash", "GCash", "Bank Transfer" |
| reference_number | VARCHAR(100) NULL | external reference (GCash ref, etc.) |
| status | ENUM('pending','pending_approval','approved','denied') | |
| denial_reason | TEXT NULL | |
| approved_by | BIGINT UNSIGNED NULL FK→users.id | |
| approved_at | TIMESTAMP NULL | |
| submitted_at | TIMESTAMP NULL | when receipt was uploaded |
| timestamps | | |

**Indexes:** `user_id`, `document_request_id`, `status`.

### `clearances`

One row per student per academic period (or per clearance request).

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK | |
| user_id | BIGINT UNSIGNED FK→users.id | |
| document_request_id | BIGINT UNSIGNED NULL FK→document_requests.id | links clearance to a request |
| teacher_status | ENUM('pending','cleared','denied') | default `pending` |
| teacher_remarks | TEXT NULL | |
| teacher_signed_by | BIGINT UNSIGNED NULL FK→users.id | |
| teacher_signed_at | TIMESTAMP NULL | |
| dean_status | ENUM('pending','cleared','denied') | |
| dean_remarks | TEXT NULL | |
| dean_signed_by | BIGINT UNSIGNED NULL FK→users.id | |
| dean_signed_at | TIMESTAMP NULL | |
| accounting_status | ENUM('pending','cleared','denied') | |
| accounting_remarks | TEXT NULL | |
| accounting_signed_by | BIGINT UNSIGNED NULL FK→users.id | |
| accounting_signed_at | TIMESTAMP NULL | |
| sao_status | ENUM('pending','cleared','denied') | |
| sao_remarks | TEXT NULL | |
| sao_signed_by | BIGINT UNSIGNED NULL FK→users.id | |
| sao_signed_at | TIMESTAMP NULL | |
| overall_status | ENUM('in_progress','completed','denied') | computed at write time |
| completed_at | TIMESTAMP NULL | |
| pdf_path | VARCHAR(255) NULL | generated certificate |
| uploaded_file_path | VARCHAR(255) NULL | optional supporting doc |
| timestamps | | |

**Indexes:** `user_id`, `overall_status`, `document_request_id`.

> **Why columns over rows for departments?** The four departments are fixed and small; columns simplify queries and policy checks. If departments become dynamic later, we'd refactor to a `clearance_signatures` table.

### `announcements`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK | |
| author_id | BIGINT UNSIGNED FK→users.id | |
| title | VARCHAR(200) | |
| body | TEXT | |
| audience | ENUM('all','student','staff') | default `all` |
| pinned | BOOLEAN | default false |
| published_at | TIMESTAMP NULL | null = draft |
| timestamps | | |

### `faqs`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK | |
| author_id | BIGINT UNSIGNED FK→users.id | |
| role | ENUM('student','staff','all') | who sees it |
| question | VARCHAR(500) | |
| answer | TEXT | |
| sort_order | INT UNSIGNED | default 0 |
| timestamps | | |

### `notifications` (Laravel native table)

Use Laravel's built-in `notifications` migration. Columns:

| Column | Type |
|--------|------|
| id | UUID PK |
| type | VARCHAR(255) |
| notifiable_type | VARCHAR(255) |
| notifiable_id | BIGINT UNSIGNED |
| data | JSON |
| read_at | TIMESTAMP NULL |
| created_at, updated_at | |

### `messages`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK | |
| sender_id | BIGINT UNSIGNED FK→users.id | |
| receiver_id | BIGINT UNSIGNED FK→users.id | |
| body | TEXT | |
| attachment_path | VARCHAR(255) NULL | |
| read_at | TIMESTAMP NULL | |
| timestamps | | |

**Indexes:** `(sender_id, receiver_id)`, `(receiver_id, read_at)`.

### `activity_logs`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK | |
| user_id | BIGINT UNSIGNED NULL FK→users.id | actor (NULL for system) |
| affected_user_id | BIGINT UNSIGNED NULL FK→users.id | |
| action | VARCHAR(100) | e.g., `request.approved` |
| description | TEXT | |
| ip_address | VARCHAR(45) NULL | |
| user_agent | VARCHAR(255) NULL | |
| metadata | JSON NULL | flexible payload |
| created_at | TIMESTAMP | (no updated_at) |

**Indexes:** `user_id`, `action`, `created_at`.

### Laravel-managed tables

- `password_reset_tokens`
- `sessions` (if using database session driver)
- `jobs` and `failed_jobs` (queue)
- `cache` and `cache_locks` (if using database cache)

## Foreign Key Strategy

- All FKs use `ON DELETE RESTRICT` to prevent accidental data loss.
- Soft-delete users (`deleted_at`) instead of hard-delete to preserve audit trails.
- For test/dev data, seeders handle deletion order explicitly.

## Migration Strategy from Legacy

1. Build new schema from Laravel migrations.
2. Write a **one-time port script** (`php artisan db:port-legacy`) that reads from the old `document_system` database and inserts into the new schema.
3. Map `requests` → `document_requests`, normalize statuses.
4. Reset all user passwords to a temporary hash and force password reset on first login (legacy hashes may not match Laravel's bcrypt config).
5. Validate row counts and spot-check critical records.

## Indexes Worth Highlighting

- `users(role, status)` — used by middleware and admin filters
- `document_requests(reference_no)` — public tracking lookup
- `document_requests(user_id, status)` — legacy student dashboard query
- `document_requests(status, created_at)` — admin queue
- `payments(status)` — pending payments queue
- `messages(receiver_id, read_at)` — unread count badge

## Phase 01 Implementation Notes

The Phase 01 implementation is largely aligned with this schema spec, with the following deliberate details captured for contributors:

- `payments.status` currently has a default of `pending` in migration implementation for safer row creation in seed/demo flows.
- `faqs.role` currently has a default of `all` in migration implementation.
- `sessions.user_id` remains Laravel default (indexed nullable column, no FK) to preserve framework compatibility and avoid session write friction.
- `users.year_level` is stored as `UNSIGNED TINYINT` and expected to be 1-4 by application/business-layer validation; a DB-level check constraint is not yet enforced in the current migration set.
- Phase 15 changes the requestor-facing model: public requests should store requestor snapshots on `document_requests`, make request/payment user links nullable, and avoid hidden user creation.
