# Phase 00 — Project Setup & Tooling

> **Goal:** A working empty Laravel app with Inertia, Vue, Tailwind, Pest, and CI in place. No business logic yet.

**Subagents:** `planner` (expand into concrete steps), `code-reviewer` (after setup commit).
**Skills:** `coding-standards`, `agent-customization`.
**Estimated effort:** Low.

---

## 0.1 Repository Initialization

- [x] Create new Git repository `svci-doc-system` (or chosen name).
- [x] Add `.gitignore` (Laravel default + `.idea/`, `.vscode/`, `.env`, `storage/app/private/*`, `storage/logs/*`).
- [x] Add `README.md` with one-paragraph project description and link to docs.
- [x] Add `LICENSE` (proprietary or per client request).
- [x] Set up branch protection on `main` (require PR, require CI).

## 0.2 Laravel Installation

- [x] Install Laravel via Composer: `composer create-project laravel/laravel app` (started on Laravel 11, upgraded to Laravel 13).
- [x] Verify PHP 8.3+ installed locally.
- [x] Move generated files to repo root.
- [x] Run `php artisan key:generate`.
- [x] Confirm `php artisan serve` works.

## 0.3 Inertia + Vue 3 Setup

- [x] Install Breeze with Inertia + Vue: `composer require laravel/breeze --dev` then `php artisan breeze:install vue`.
- [x] Run `npm install && npm run build`.
- [x] Verify Inertia welcome page renders.
- [x] Configure Vite for HMR.

## 0.4 Tailwind CSS

- [x] Confirm Tailwind installed by Breeze.
- [x] Update `tailwind.config.js` with brand colors and typography from [`docs/09-frontend-design.md`](../../09-frontend-design.md).
- [x] Add Inter and Plus Jakarta Sans font links to `app.blade.php`.
- [x] Install Headless UI: `npm i @headlessui/vue @heroicons/vue`.

## 0.5 Database

- [x] Install MySQL 8 locally (or via Docker).
- [x] Create local DB `svci_dev` and user `svci_dev`.
- [x] Configure `.env` with local DB credentials.
- [x] Run `php artisan migrate` (default Laravel migrations).

## 0.6 Reverb

- [x] Install Reverb: `php artisan install:broadcasting --reverb`.
- [x] Configure Reverb env vars (placeholders for now).
- [x] Verify `php artisan reverb:start` runs.

## 0.7 Testing Setup

- [x] Install Pest 3: `composer require pestphp/pest --dev --with-all-dependencies` then `php artisan pest:install`.
- [x] Create `phpunit.xml` test database config (separate `svci_test` DB).
- [x] Add a smoke test that hits `/` and asserts 200.
- [x] Run `./vendor/bin/pest` — passes.

## 0.8 Static Analysis & Code Style

- [x] Install Larastan: `composer require larastan/larastan --dev`.
- [x] Add `phpstan.neon` with level 6.
- [x] Confirm Pint installed (Laravel default).
- [x] Add `pint.json` config matching Laravel preset.
- [x] Add `.editorconfig`.

## 0.9 Frontend Tooling

- [x] Install ESLint + Prettier: `npm i -D eslint prettier eslint-plugin-vue @vue/eslint-config-prettier`.
- [x] Add `.eslintrc.cjs` and `.prettierrc`.
- [x] Add npm scripts: `lint`, `lint:fix`, `format`.
- [x] Run lint — passes on starter code.

## 0.10 Required Composer Packages

- [x] `composer require barryvdh/laravel-dompdf`
- [x] `composer require maatwebsite/excel`
- [x] `composer require intervention/image-laravel`
- [x] `composer require spatie/laravel-permission` *(optional — we may use simple role enum + policies instead; decide here)*

## 0.11 GitHub Actions CI

- [x] Add `.github/workflows/ci.yml` per [`docs/15-testing-strategy.md`](../../15-testing-strategy.md).
- [x] Steps: checkout, PHP setup, composer install, npm install, build, migrate, pint --test, phpstan, pest.
- [x] CI passes on initial commit.

## 0.12 Local Docker (Dev Parity)

- [x] Create `docker-compose.yml` with services: `app`, `mysql`, `reverb`, `mailhog`.
- [x] Create `Dockerfile.dev` (separate from production Dockerfile).
- [x] Document `docker compose up` workflow in `README.md`.

## 0.13 Documentation Sync

- [x] Copy/link `revamped-online-doc-system/docs/` into the new app repo (or keep as a separate planning repo — decide).
- [x] Add `CONTRIBUTING.md` covering branch naming, commit format, PR template.

## 0.14 First PR

- [x] Open PR: "chore: initial Laravel + Inertia + Vue + Tailwind setup".
- [x] Invoke `code-reviewer` subagent on the diff.
- [x] Address feedback.
- [x] Merge to `main`.

---

## Exit Criteria

- ✅ `php artisan serve` shows the Inertia welcome page styled with Tailwind.
- ✅ `./vendor/bin/pest` passes.
- ✅ `npm run build` succeeds.
- ✅ CI is green on `main`.
- ✅ Docs accessible to all contributors.
