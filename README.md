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

## Fresh Local Setup

Use this section when recreating the project on another machine.

### Prerequisites

- Git
- Docker Desktop or Docker Engine with Docker Compose
- Optional for native development: PHP 8.4, Composer 2, Node.js 20+, npm

Docker is the easiest option for a client demo because it includes the Laravel app, MySQL, Reverb, and MailHog.

### Option A: Docker Setup

Clone the repository and enter the project folder:

```bash
git clone <repo-url>
cd revamped-online-doc-system
```

Create the local environment file:

```bash
cp .env.example .env
```

For Docker, update these local values in `.env`:

```env
APP_URL=http://localhost:8000
REVERB_ALLOWED_ORIGINS=http://localhost:8000
VITE_REVERB_HOST=127.0.0.1
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

Start the containers:

```bash
docker compose up -d --build
```

Prepare the Laravel app inside the container:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan storage:link
docker compose exec app npm run build
```

Keep a queue worker running when testing notifications, email, PDFs, and realtime side effects:

```bash
docker compose exec app php artisan queue:work
```

Open the app at:

- App: `http://localhost:8000`
- Public request form: `http://localhost:8000/request-document`
- Public tracking: `http://localhost:8000/track-document`
- MailHog inbox: `http://localhost:8025`
- Reverb WebSocket service: `http://localhost:8080`

Stop the stack when done:

```bash
docker compose down
```

To delete the local MySQL data and start clean:

```bash
docker compose down -v
docker compose up -d --build
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app npm run build
```

### Option B: Native Laravel Setup

Use this option when PHP, Composer, Node, and a database are installed directly on the machine.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan storage:link
composer dev
```

The default `.env.example` uses SQLite for native local development. If you prefer MySQL, update these values in `.env` before running migrations:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=svci_dev
DB_USERNAME=svci_dev
DB_PASSWORD=svci_secret
```

For full manual testing in native mode, run these in separate terminals:

```bash
php artisan serve
php artisan queue:work
php artisan reverb:start
npm run dev
```

### Local Demo Accounts

After `php artisan migrate:fresh --seed`, these local-only accounts are available:

| Role | Email | Password |
|------|-------|----------|
| SuperAdmin | `superadmin.dummy@gmail.com` | `password` |
| Admin | `admin@example.com` | `password` |
| Teacher | `teacher@example.com` | `password` |
| Dean | `dean@example.com` | `password` |
| Accounting | `accounting@example.com` | `password` |
| SAO | `sao@example.com` | `password` |
| Legacy student | `student@example.com` | `password` |

Do not use these credentials in production.

### Common Setup Issues

- If the app shows a missing Vite manifest error, run `npm run build` or `docker compose exec app npm run build`.
- If login or forms fail after changing `.env`, run `php artisan config:clear` or `docker compose exec app php artisan config:clear`.
- If uploaded files or generated PDFs are not reachable through authorized routes, run `php artisan storage:link` or `docker compose exec app php artisan storage:link`.
- If email is expected but nothing arrives, make sure the queue worker is running and open MailHog at `http://localhost:8025`.
- If port `8000`, `8080`, `8025`, or `3306` is already in use, stop the other service or change the matching port in `docker-compose.yml`.

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
