# SVCI Online Document System

A revamped document request and clearance management system for SVCI, built with **Laravel 11**, **Inertia.js**, **Vue 3**, and **Tailwind CSS**. Students can submit document requests, upload payment receipts, and track clearance status in real time. Admins, department heads, and super admins manage approvals, payments, and user accounts through role-specific dashboards.

> **Planning docs:** [`docs/`](./docs/) — architecture, roles, database schema, routes, security, and phased implementation plan.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 11, PHP 8.3 |
| Frontend | Vue 3, Inertia.js, Tailwind CSS 3 |
| Real-time | Laravel Reverb (WebSockets) |
| Database | MySQL 8 |
| Testing | Pest 3, Playwright |
| Deployment | DigitalOcean VPS via Dokploy |

## Local Development

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 20+
- MySQL 8 (or Docker)

### Setup

```bash
# Clone and install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Set up database (update .env with your DB credentials first)
php artisan migrate

# Start development server
composer dev        # starts Laravel, queue, pail, and Vite concurrently
```

### Docker (recommended for parity)

```bash
docker compose up -d
```

See [`docker-compose.yml`](./docker-compose.yml) for services: `app`, `mysql`, `reverb`, `mailhog`.

## Running Tests

```bash
# All tests
./vendor/bin/pest

# With coverage
composer test:coverage

# Static analysis
./vendor/bin/phpstan analyse

# Code style check
./vendor/bin/pint --test

# Frontend lint
npm run lint
```

Coverage needs Xdebug or PCOV locally. CI installs Xdebug and enforces the 80% minimum.

## Contributing

See [`CONTRIBUTING.md`](./CONTRIBUTING.md) for branch naming, commit format, and PR guidelines.
