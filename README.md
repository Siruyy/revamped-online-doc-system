# SVCI Online Document System

A Laravel 13 + Inertia + Vue rewrite of the St. Vincent College Incorporated online document request and clearance system.

The app lets public requestors submit academic document requests without creating a student account, upload offline payment receipts, track status by reference number, and receive public-safe updates. Staff users manage requests, payments, document releases, department clearance signing, reports, users, and activity logs from role-specific dashboards.

## Status

Core MVP flows are implemented for auth, student requests, admin processing, department clearance, SuperAdmin management, notifications, PDF generation, CSV exports, UI polish, and hardening.

Deferred or out of current scope:

- Messaging UI and routes are deferred in `docs/plan/phase-08-messaging.md`.
- Deployment artifacts and legacy migration are skipped for the current scope.
- Manual Reverb, queue worker, and mail capture checks are tracked in `docs/manual-verification-checklist.md`.

## Tech Stack

- **Backend:** PHP 8.4, Laravel 13, Inertia Laravel, Laravel Sanctum, Laravel Reverb
- **Frontend:** Vue 3, Inertia.js, Tailwind CSS, Headless UI, Heroicons
- **Database and storage:** MySQL 8 in Docker, Laravel filesystem storage for private receipts, clearance files, signatures, and PDFs
- **Async and realtime:** database queues, Laravel notifications, Laravel Echo, Reverb WebSockets
- **Documents and exports:** DomPDF for clearance PDFs, custom CSV exports
- **Tooling:** Composer, npm, Vite, Pest/PHPUnit, Playwright, PHPStan/Larastan, Pint, ESLint, Prettier

## Main Features

- Public document request intake with reference-number tracking
- Legacy student registration pages retained but hidden from public navigation
- Role-based dashboards for student, admin, department officers, and SuperAdmin
- Multi-item document request flow with fees, requirements, and reference numbers
- Offline payment receipt upload and admin payment review
- Department clearance workflow for teacher, dean, accounting, and SAO sign-off
- Request stage tracking through approval, processing, pickup readiness, and release
- Private file delivery through authorized controllers
- Notifications, broadcast channels, and role/user-scoped realtime updates
- Clearance PDF generation and admin/SuperAdmin CSV exports
- Activity logging, security headers, throttled sensitive actions, and automated tests

## Local Development

For the client setup, run the one-command Docker bootstrap from [`docs/local-development.md`](docs/local-development.md):

```bash
./scripts/setup-local.sh
```

It builds the local containers, prepares the database, creates storage links, builds the frontend, and starts Reverb. The guide also includes the queue-worker command, MailHog, demo accounts, reset instructions, troubleshooting, and the native PHP fallback.

## Verification

Run focused checks first, then broaden before release:

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress
npm run lint
npm run build
npm run test:e2e
```

Coverage requires Xdebug or PCOV:

```bash
composer test:coverage
```

## Documentation

- Project overview: `docs/00-overview.md`
- Architecture: `docs/02-architecture.md`
- Roles and permissions: `docs/03-roles-and-permissions.md`
- Database schema: `docs/04-database-schema.md`
- Routes and controllers: `docs/07-routes-and-controllers.md`
- Security: `docs/10-security.md`
- Implementation plan: `docs/plan/README.md`
