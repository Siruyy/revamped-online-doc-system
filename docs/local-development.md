# Local Development Guide

This is the client-facing guide for running the SVCI Online Document System locally.

The recommended path uses Docker Desktop. It runs Laravel, MySQL, and Reverb without requiring PHP, Composer, Node.js, or MySQL on the host computer. Email is delivered through Resend.

## Recommended setup: Docker Desktop

### Prerequisites

- Git
- Docker Desktop (or Docker Engine with Docker Compose)
- At least 4 GB of free memory

The bootstrap script is Bash. On Windows, run it from Git Bash or WSL.

### First setup

Clone the repository, enter the project directory, create the environment file, configure Resend, and run the bootstrap script:

```bash
git clone <repository-url>
cd revamped-online-doc-system
cp .env.example .env
```

Edit `.env` and set:

```env
MAIL_MAILER=resend
RESEND_KEY=re_your_resend_api_key
MAIL_FROM_ADDRESS=noreply@your-verified-domain.com
MAIL_FROM_NAME="SVCI Document System"
```

`MAIL_FROM_ADDRESS` must use a domain that has been verified in Resend. Keep `RESEND_KEY` private and never commit `.env`.

Then run:

```bash
./scripts/setup-local.sh
```

The script validates the Resend settings, adds local SuperAdmin defaults, builds and starts the containers, generates the app key, applies migrations, seeds demo data, creates the storage link, builds frontend assets, and starts Reverb.

Open these addresses:

| Service | Address |
|---------|---------|
| Application | <http://localhost:8000> |
| Public request form | <http://localhost:8000/request-document> |
| Public tracking | <http://localhost:8000/track-document> |
| Reverb WebSocket | `ws://localhost:8080` |

### Queue worker

Start this in a second terminal when testing email, notifications, PDFs, or workflow side effects:

```bash
docker compose exec app php artisan queue:work
```

Leave it running. Workflow emails are sent through Resend to the requestor or staff email addresses used during testing.

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

- **`failed to read dockerfile: invalid file request Dockerfile.dev`:** update the repository before running setup. The project must contain `Dockerfile.dev` at the repository root. From Git Bash, run `git pull origin main`, confirm with `ls Dockerfile.dev`, and run `./scripts/setup-local.sh` again.
- **Port already in use:** stop the process using port `8000`, `3306`, or `8080`.
- **Missing Vite manifest:** run `docker compose exec app npm run build`.
- **Stale dependencies:** run `docker compose exec app composer install` and `docker compose exec app npm ci`, then rebuild the frontend.
- **Reverb restarting:** inspect `docker compose logs reverb` and run `docker compose up -d --build reverb`.
- **No email received:** keep the queue worker running, inspect `docker compose logs app`, and check Resend delivery events for rejected, bounced, or delivered messages.
- **Resend rejects the message:** confirm `RESEND_KEY` is valid and `MAIL_FROM_ADDRESS` belongs to a verified Resend domain.
- **Bad local database state:** use `./scripts/setup-local.sh --reset` only if the data can be discarded.

The first request-form screen asks whether the records are from College / Graduate School or Basic Education Campus. Select one division to reveal its document categories. If the categories still do not appear after a successful setup, run `./scripts/setup-local.sh --reset` to recreate the local database and seed the document catalog.

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

Use <http://localhost:8000>. Native mode keeps the default `log` mailer unless you set `MAIL_MAILER=resend`, `RESEND_KEY`, and a verified `MAIL_FROM_ADDRESS` in `.env`. For native MySQL, set `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` before migrating.

## Verification

For the full manual walkthrough, use [`manual-verification-checklist.md`](./manual-verification-checklist.md). Automated checks:

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress
npm run lint
npm run build
```
