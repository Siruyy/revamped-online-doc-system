# SVCI Online Document System - Project Brief

## 1. Project Summary

### What this project is

SVCI Online Document System is a role-based web application for managing academic document requests, offline payment verification, document release, and multi-department clearance workflows for St. Vincent College Incorporated.

Evidence: `docs/00-overview.md`, `docs/05-features.md`, `routes/*.php`, `resources/js/Pages/**`, `app/Services/**`, and `database/migrations/**`.

### Who it is for

- Students and alumni requesting school documents.
- Registrar/admin staff processing requests, payments, document types, releases, FAQs, announcements, and reports.
- Department officers in teacher, dean, accounting, and SAO roles signing clearance steps.
- SuperAdmins managing users, registration approvals, reports, logs, and high-level system data.

### What problem it solves

It replaces a manual and legacy PHP-based process with a modern Laravel app where students can request documents online, upload proof of offline payment, track clearance status, and receive notifications while staff coordinate approvals and release workflows from dashboards.

### Current status

In progress, with a mostly complete MVP. Finished plan phases cover setup, schema, auth, student/admin/department/SuperAdmin flows, realtime notifications, PDFs/exports, UI polish, and hardening. Messaging is explicitly deferred. Deployment and legacy migration are skipped for the current scope. Post-launch operations docs are partially complete.

## 2. Tech Stack

### Frontend framework/libraries

- Vue 3 with Inertia.js.
- Tailwind CSS 3.
- Headless UI for Vue.
- Heroicons.
- Laravel Echo and Pusher JS for Reverb-compatible realtime subscriptions.
- Axios, Ziggy, Vite.

### Backend framework/libraries

- PHP 8.3.
- Laravel 13.
- Inertia Laravel.
- Laravel Breeze auth scaffolding.
- Laravel Sanctum.
- Laravel Reverb.
- DomPDF via `barryvdh/laravel-dompdf`.
- Intervention Image Laravel.
- Tighten Ziggy.

### Database/storage

- MySQL 8 in Docker Compose for local development.
- `.env.example` defaults to SQLite, but `docker-compose.yml` configures MySQL.
- Laravel database-backed sessions, cache, and queue tables are present.
- Laravel local filesystem storage is used for payment receipts, payment QR images, clearance supporting files, signatures, avatars, and generated PDFs.

### Auth, APIs, integrations, jobs, queues, file handling

- Auth uses Laravel session auth with Breeze-style login, registration, email verification, password reset, and profile routes.
- Accounts have roles and approval statuses; non-active users are blocked by `EnsureApprovedAccount`.
- Role-based route groups use `EnsureRole`.
- Policies exist for document requests, payments, clearances, users, payment profiles, and activity logs.
- Realtime events and notifications use Reverb broadcast channels in `routes/channels.php`.
- Queue connection defaults to `database`.
- File access is routed through `FileController`, not raw public paths.
- No public third-party API integration is visible in the current code.
- Inferred from docs: production target is a DigitalOcean VPS with Dokploy, Nginx/PHP-FPM, MySQL, queue worker, and Reverb. Repo contains local Docker dev artifacts, not a complete production deployment bundle.

### Package managers, build tools, testing tools

- Composer for PHP dependencies.
- npm for frontend dependencies.
- Vite for frontend build/HMR.
- Pest/PHPUnit for PHP tests.
- Playwright for browser tests under `tests/Browser`.
- Larastan/PHPStan for static analysis.
- Laravel Pint for PHP formatting.
- ESLint and Prettier for Vue/JS formatting.

## 3. Architecture

### Main app structure

- `routes/web.php` defines entry points and loads role-specific route files.
- `routes/student.php`, `routes/admin.php`, `routes/department.php`, and `routes/superadmin.php` define feature areas per role.
- `app/Http/Controllers/**` contains role-specific controllers.
- `app/Http/Requests/**` contains validation requests.
- `app/Services/**` holds business logic for requests, payments, clearances, PDFs, exports, audit logging, SLA calculations, claim slips, and policy rules.
- `app/Policies/**` contains resource authorization.
- `app/Events/**` and `app/Notifications/**` drive realtime and user notifications.
- `resources/js/Pages/**` contains Inertia Vue pages grouped by role.
- `resources/js/Components/**` and `resources/js/Layouts/**` contain shared UI primitives and role layouts.

### Key modules/features

- Authentication and account approval.
- Student document request and payment upload.
- Admin request, payment, release, document type, announcement, FAQ, report, and payment profile management.
- Department clearance review, signing, denial, and signature profile flow.
- SuperAdmin users, pending registrations, logs, and reports.
- Notifications and broadcast channel authorization.
- PDF and CSV output.
- Activity logging and security hardening.

### Important data flow

1. Student submits a document request through Inertia/Vue.
2. Laravel validates the request with a Form Request.
3. `RequestService` creates the request, request items, requirements, and payment row in a transaction.
4. The system logs activity and dispatches a request event/notification.
5. Admin reviews and approves or denies the request.
6. Student uploads an offline payment receipt after approval.
7. Admin approves or denies payment.
8. Clearance and release workflows progress through department signing, claim/release handling, and optional PDF generation.
9. Status changes are recorded in the database, logged, and surfaced through notifications/broadcasts.

### API routes/endpoints

This is not a separate JSON API app. It uses server-side Laravel routes that render Inertia Vue pages and handle form posts.

Key route groups:

- `/student/dashboard`, `/student/requests`, `/student/payments`, `/student/clearance`, `/student/notifications`, `/student/faq`
- `/admin/dashboard`, `/admin/requests`, `/admin/payments`, `/admin/clearances`, `/admin/document-types`, `/admin/announcements`, `/admin/faqs`, `/admin/reports`, `/admin/releases`, `/admin/settings/payment-profile`
- `/department/dashboard`, `/department/clearances`, `/department/faq`, `/department/notifications`
- `/superadmin/dashboard`, `/superadmin/users`, `/superadmin/logs`, `/superadmin/requests`, `/superadmin/reports`, `/superadmin/document-types`, `/superadmin/announcements`, `/superadmin/faqs`

### Database models/schema

Major models:

- `User`
- `DocumentRequest`
- `DocumentRequestItem`
- `DocumentType`
- `RequestRequirement`
- `Payment`
- `PaymentProfile`
- `Clearance`
- `ClaimSlip`
- `Announcement`
- `Faq`
- `Message`
- `ActivityLog`

Important relationships:

- Users have document requests, payments, clearances, sent/received messages, and signed clearances.
- Document requests belong to users and document types, and have items, payments, clearances, requirements, and a claim slip.
- Clearances store fixed department states for teacher, dean, accounting, and SAO.

### Authentication/authorization model

- Laravel session auth with email verification.
- `User` implements `MustVerifyEmail`.
- Roles: `student`, `admin`, `teacher`, `dean`, `accounting`, `sao`, `superadmin`.
- Account statuses: `pending`, `active`, `suspended`, `rejected`.
- Middleware gates route groups by role and active approval status.
- Policies control resource-level actions.
- Broadcast channel auth restricts user, role, department, and placeholder chat channels.

### Notable design patterns

- Thin controllers with service-layer business logic.
- Form Requests for validation.
- Policies for authorization.
- Events and notifications for side effects.
- Transactions and row locking in request/clearance workflows.
- Role-scoped Inertia pages and layouts.
- Private file access through authorized Laravel controllers.
- Server-side filtering/pagination patterns in list screens.

## 4. Core Features

- **Student registration and approval:** Students register and wait for SuperAdmin approval before gaining access.
- **Role-based dashboards:** Each role lands on a tailored dashboard for its work queue and status overview.
- **Document request workflow:** Students select document types, submit a purpose, generate reference numbers, and track request status.
- **Policy-aware request rules:** Request eligibility, fee calculations, requirements, SLA timing, and claim slip behavior are handled in services under `app/Services/Policy`.
- **Offline payment verification:** Students upload receipts, while admins review, approve, or deny payment submissions.
- **Department clearance:** Teacher, dean, accounting, and SAO roles sign or deny clearance steps with remarks and timestamps.
- **Document release management:** Admin routes and services support release tracking, claim slips, and honorable dismissal return handling.
- **Notifications and realtime updates:** User and role channels support live notification delivery through Laravel Reverb.
- **Reports and exports:** Admin/SuperAdmin report views and CSV exports are implemented for requests, payments, users, and logs.
- **PDF generation:** Completed clearances can generate downloadable PDFs.
- **Content management:** Admin/SuperAdmin users can manage document types, announcements, FAQs, and payment profiles.
- **Security and hardening:** Role middleware, account approval checks, policies, throttled sensitive actions, CSP/security headers, private files, and automated tests are present.

## 5. Technical Highlights

- **Multi-role workflow:** The app coordinates students, registrar admins, department officers, and SuperAdmins through distinct route groups and dashboards.
- **Complex request lifecycle:** Requests carry items, requirements, payment status, SLA state, release state, and audit logs.
- **Clearance signing model:** Fixed department columns keep the four required clearance approvals simple and queryable.
- **Realtime infrastructure:** Laravel Reverb, Echo, broadcast events, and private channels are wired for notifications and role/user updates.
- **Private file handling:** Sensitive receipts, clearance files, signatures, and PDFs are served through authorized Laravel controllers.
- **Security posture:** The app includes CSRF-backed Laravel forms, role/status middleware, policies, throttles, CSP/security headers, and tests around auth/security behavior.
- **Testing depth:** Repo includes PHP unit/feature tests plus Playwright browser tests for registration, request/payment, clearance, SuperAdmin security, and responsive UI.
- **Dev runtime parity:** Docker Compose provides app, MySQL, Reverb, and MailHog services for local development.
- **Operational docs:** Support, maintenance, runbook, hotfix, manual verification, training guides, and v2 roadmap docs are present.

No AI/ML usage is supported by the repository evidence.

## 6. Screenshots / Demo Opportunities

Best portfolio screenshots or demo flows:

- **Welcome/login/register:** Show polished auth and student registration flow.
- **Registration pending and SuperAdmin approval:** Demonstrates account gating and administrative approval.
- **Student dashboard:** Capture request stats, announcements, FAQ/status cards, and notification bell.
- **Student request wizard/create flow:** Strong CRUD/workflow example with document types, fees, and purpose.
- **Student request detail:** Shows lifecycle tracking, requirements, payment, clearance, and release status.
- **Payment upload and admin payment review:** Good before/after evidence for offline payment verification.
- **Admin dashboard/request queue:** Best screenshot for operational workflow.
- **Admin request detail:** Shows approval/denial, requirements, SLA/release controls, and history.
- **Department clearance detail:** Shows role-specific signing/denial with remarks and status.
- **SuperAdmin users pending page:** Shows registration approval and user governance.
- **SuperAdmin reports/logs:** Good for auditability and system management.
- **PDF clearance output:** Demonstrates generated document capability.
- **Mobile views:** Student request list, request detail, payment upload, and clearance status are the best responsive captures.
- **Architecture view:** Use route groups, services, policies, events, notifications, storage, queue, and Reverb as the diagram anchors.

## 7. Portfolio Case Study Angle

### Main headline

Modernized a college document request and clearance system into a secure Laravel 13 workflow platform.

### Short description

A full-stack Laravel/Inertia/Vue application that digitizes student document requests, offline payment verification, department clearance signing, admin reporting, and realtime status notifications for a school records office.

### Problem

Students and staff relied on manual or legacy-PHP workflows for document requests, payment proof, clearance coordination, and request tracking. The process created delays, weak auditability, and security risks.

### Solution

Rebuilt the system as a role-based Laravel 13 application with Inertia/Vue dashboards, service-layer business logic, private file handling, realtime notifications, PDF generation, CSV exports, and automated tests.

### Role/ownership

Full-stack developer responsible for architecture, Laravel backend, Vue/Inertia frontend, database schema, authorization model, workflows, testing, documentation, and local deployment tooling.

### Outcome

MVP implementation is largely complete for the main document request, payment, clearance, reporting, and hardening flows. Messaging remains deferred, and launch-dependent operational checks remain future work.

### Strong bullets

- Built role-specific dashboards and workflows for students, admins, department officers, and SuperAdmins.
- Implemented document request, requirement, payment, clearance, claim/release, PDF, and export flows with service-layer business logic.
- Added policy-based authorization, account approval gating, private file delivery, throttled sensitive actions, and CSP/security headers.
- Wired realtime notifications with Laravel Reverb, Echo, broadcast events, and private channel authorization.
- Backed the app with PHP feature/unit tests, Playwright browser tests, PHPStan, Pint, ESLint, Vite build checks, and manual verification docs.

## 8. README Recommendation

Recommended README structure:

1. **Project Title and One-Line Summary**
   - Name, institution/context, and the main workflow the app solves.

2. **Status**
   - MVP complete/in progress, deferred messaging, skipped deployment/legacy migration, remaining manual checks.

3. **Problem**
   - Manual document request and clearance process, legacy PHP risks, slow status visibility.

4. **Solution**
   - Laravel/Inertia/Vue role-based workflow platform with realtime updates and private file handling.

5. **Core Features**
   - Auth/approval, student requests, payments, clearance, admin tools, SuperAdmin tools, notifications, PDFs/exports.

6. **Tech Stack**
   - Backend, frontend, database/storage, realtime, queues, testing, tooling.

7. **Architecture Overview**
   - Route groups, controllers, services, policies, events, notifications, storage, data layer.

8. **Local Setup**
   - Composer/npm install, env, key, migrate/seed, dev server.

9. **Docker Development**
   - Compose services and ports.

10. **Verification**
   - PHP tests, Pint, PHPStan, ESLint, Vite build, Playwright, coverage caveat.

11. **Documentation Map**
   - Link key docs under `docs/`.

12. **Known Gaps / Deferred Scope**
   - Messaging, deployment artifacts, legacy migration, manual Reverb/queue/mail checks.

## 9. Architecture Diagram Data

Layers:

- **Interface:** Vue 3 Inertia pages, shared components, role layouts, Tailwind UI.
- **Application:** Laravel route groups, controllers, Form Requests, Inertia responses.
- **Services/API:** RequestService, PaymentService, ClearanceService, PdfService, CsvExportService, policy services, events, notifications.
- **Data:** MySQL, Eloquent models, migrations, database queues/sessions/cache, local/private filesystem storage.
- **Auth/Permissions:** Laravel session auth, email verification, account approval status, role middleware, policies, broadcast channel authorization.
- **Deployment/Runtime:** Local Docker Compose app/MySQL/Reverb/MailHog, Vite dev server, database queue worker; inferred production target is DigitalOcean/Dokploy from docs.

Connections:

- **Interface -> Application:** Inertia Vue pages submit forms and receive server-rendered props through Laravel routes.
- **Application -> Services/API:** Controllers validate input with Form Requests, authorize actions, and delegate workflow decisions to services.
- **Services/API -> Data:** Services use Eloquent transactions, relationships, events, and storage writes to persist workflow state.
- **Services/API -> Interface:** Events and notifications broadcast updates through Reverb/Echo to role/user channels.
- **Auth/Permissions -> Application:** Middleware gates role route groups and approved account access before controllers run.
- **Auth/Permissions -> Services/API:** Policies enforce resource-level actions such as viewing, approving, signing, releasing, and exporting.
- **Deployment/Runtime -> Services/API:** Queue workers process queued jobs/notifications; Reverb serves WebSocket broadcasts; Docker provides local MySQL and mail capture.

Technologies:

- **Laravel 13:** Backend MVC framework, routing, Eloquent, queues, notifications, middleware, policies.
- **Inertia.js:** Connects Laravel controllers to Vue pages without a separate JSON API.
- **Vue 3:** Frontend page and component framework.
- **Tailwind CSS:** Utility-first styling and responsive UI.
- **MySQL 8:** Main relational database in Docker development.
- **Laravel Reverb:** Self-hosted WebSocket server for realtime updates.
- **Laravel Echo + Pusher JS:** Browser-side subscription layer for Reverb-compatible channels.
- **DomPDF:** Clearance PDF generation.
- **Intervention Image:** Image processing support for uploaded profile/signature assets.
- **Laravel filesystem:** Stores private receipts, QR images, clearance files, signatures, and PDFs.
- **Pest/PHPUnit:** PHP unit and feature tests.
- **Playwright:** Browser end-to-end and responsive UI tests.
- **PHPStan/Larastan:** Static analysis for Laravel/PHP code.
- **Pint:** PHP code style.
- **ESLint/Prettier:** JavaScript/Vue linting and formatting.
- **Vite:** Frontend bundling and HMR.
- **Docker Compose:** Local app, database, Reverb, and MailHog services.

## 10. Gaps / Questions

- Confirm whether the portfolio should present the project as commissioned client work, academic/institutional work, or a private rewrite.
- Confirm whether deployment to DigitalOcean/Dokploy actually happened; repo evidence mostly supports local Docker and deployment planning.
- Confirm whether screenshots can include school/client branding and realistic student data, or whether demo data must be anonymized.
- Confirm the exact production database choice if deployed; docs emphasize MySQL, while `.env.example` defaults to SQLite for local scaffolding.
- Confirm whether CSV-only exports are enough to mention; docs mention Excel in places, but installed packages and code evidence support custom CSV exports.
- Messaging is deferred; avoid advertising chat until Phase 08 is implemented.
- No AI/ML features are present; avoid AI framing.
- Manual Reverb/browser, queue worker, and MailHog/Mailpit verification remain tracked separately.
- Deployment and legacy migration phases are skipped for current scope; avoid claiming completed production launch or completed migration unless confirmed.
- README currently links to repository docs, but a portfolio page would benefit from real screenshots or a short demo video.
