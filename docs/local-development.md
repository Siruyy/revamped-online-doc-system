# Local Development Guide

This is the client-facing guide for running the SVCI Online Document System locally.

The recommended path uses Docker Desktop. It runs Laravel, MySQL, Reverb, and MailHog without requiring PHP, Composer, Node.js, or MySQL on the host computer.

## Recommended setup: Docker Desktop

### Prerequisites

- Git
- Docker Desktop (or Docker Engine with Docker Compose)
- At least 4 GB of free memory

The bootstrap script is Bash. On Windows, run it from Git Bash or WSL.

### First setup

Clone the repository, enter the project directory, and run the bootstrap script:

```bash
git clone <repository-url>
cd revamped-online-doc-system
./scripts/setup-local.sh
```

The script creates `.env` when needed, adds local SuperAdmin defaults, builds and starts the containers, generates the app key, applies migrations, seeds demo data, creates the storage link, builds frontend assets, and starts Reverb.

Open these addresses:

| Service | Address |
|---------|---------|
| Application | <http://localhost:8000> |
| Public request form | <http://localhost:8000/request-document> |
| Public tracking | <http://localhost:8000/track-document> |
| MailHog inbox | <http://localhost:8025> |
| Reverb WebSocket | `ws://localhost:8080` |

### Queue worker

Start this in a second terminal when testing email, notifications, PDFs, or workflow side effects:

```bash
docker compose exec app php artisan queue:work
```

Leave it running. Local email is captured by MailHog and is not sent to real recipients.

### Local demo accounts

All accounts below use the password `password`:

| Role | Email |
|------|-------|
| SuperAdmin | `superadmin@svci.test` |
| Admin / Registrar | `admin@example.com` |
| Legacy student | `student@example.com` |
| Accounting | `accounting@svci.test` |
| BEC Principal | `principal@svci.test` |
| Dean | `dean@svci.test` |
| President's office | `president@svci.test` |
| Librarian | `librarian@svci.test` |
| Student Affairs | `student_affairs@svci.test` |
| Alumni | `alumni@svci.test` |
| Guidance | `guidance@svci.test` |

Department-specific dean accounts use `dean.<code>@svci.test`, where `<code>` is lowercase. Seeded codes include `gsd`, `asted`, `aed`, `bmed`, `csd`, `cesd`, `ihmd`, and `ccj`.

These credentials are for local testing only.

### Day-to-day commands

Stop containers and keep the database:

```bash
docker compose down
```

Start them again:

```bash
docker compose up -d
```

View logs:

```bash
docker compose logs -f app
docker compose logs -f reverb
```

Rebuild dependencies/assets or re-run setup after Docker changes:

```bash
./scripts/setup-local.sh
```

To erase the local Docker volumes and recreate all demo data:

```bash
./scripts/setup-local.sh --reset
```

Use `--reset` only when it is safe to discard local data.

### Common Docker issues

- **Port already in use:** stop the process using port `8000`, `3306`, `8025`, or `8080`.
- **Missing Vite manifest:** run `docker compose exec app npm run build`.
- **Stale dependencies:** run `docker compose exec app composer install` and `docker compose exec app npm ci`, then rebuild the frontend.
- **Reverb restarting:** inspect `docker compose logs reverb` and run `docker compose up -d --build reverb`.
- **No email in MailHog:** keep the queue worker running and inspect `docker compose logs app`.
- **Bad local database state:** use `./scripts/setup-local.sh --reset` only if the data can be discarded.

## Native setup (without Docker)

Use this only when PHP 8.4+, Composer 2, Node.js 20+, npm, and SQLite or MySQL are already installed.

Install dependencies and create the environment:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
```

Add these local SuperAdmin values to `.env` before seeding:

```env
SUPERADMIN_EMAIL=superadmin@svci.test
SUPERADMIN_PASSWORD=password
SUPERADMIN_FULLNAME=SVCI Local SuperAdmin
SUPERADMIN_USERNAME=local_superadmin
```

Then run:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

Run these in separate terminals:

```bash
php artisan serve --port=8000
php artisan queue:work
php artisan reverb:start
npm run dev
```

Use <http://localhost:8000>. Native mode keeps the default `log` mailer, so email is written to Laravel logs instead of MailHog. For native MySQL, set `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` before migrating.

## Verification

For the full manual walkthrough, use [`manual-verification-checklist.md`](./manual-verification-checklist.md). Automated checks:

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress
npm run lint
npm run build
```
