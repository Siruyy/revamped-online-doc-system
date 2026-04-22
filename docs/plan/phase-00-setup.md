# Phase 00 — Project Setup & Tooling

> **Goal:** A working empty Laravel app with Inertia, Vue, Tailwind, Pest, and CI in place. No business logic yet.

**Subagents:** `planner` (expand into concrete steps), `code-reviewer` (after setup commit).
**Skills:** `coding-standards`, `agent-customization`.
**Estimated effort:** Low.

---

## 0.1 Repository Initialization

- [ ] Create new Git repository `svci-doc-system` (or chosen name).
- [ ] Add `.gitignore` (Laravel default + `.idea/`, `.vscode/`, `.env`, `storage/app/private/*`, `storage/logs/*`).
- [ ] Add `README.md` with one-paragraph project description and link to docs.
- [ ] Add `LICENSE` (proprietary or per client request).
- [ ] Set up branch protection on `main` (require PR, require CI).

## 0.2 Laravel Installation

- [ ] Install Laravel 11 via Composer: `composer create-project laravel/laravel app`.
- [ ] Verify PHP 8.3+ installed locally.
- [ ] Move generated files to repo root.
- [ ] Run `php artisan key:generate`.
- [ ] Confirm `php artisan serve` works.

## 0.3 Inertia + Vue 3 Setup

- [ ] Install Breeze with Inertia + Vue: `composer require laravel/breeze --dev` then `php artisan breeze:install vue`.
- [ ] Run `npm install && npm run build`.
- [ ] Verify Inertia welcome page renders.
- [ ] Configure Vite for HMR.

## 0.4 Tailwind CSS

- [ ] Confirm Tailwind installed by Breeze.
- [ ] Update `tailwind.config.js` with brand colors and typography from [`docs/09-frontend-design.md`](../docs/09-frontend-design.md).
- [ ] Add Inter and Plus Jakarta Sans font links to `app.blade.php`.
- [ ] Install Headless UI: `npm i @headlessui/vue @heroicons/vue`.

## 0.5 Database

- [ ] Install MySQL 8 locally (or via Docker).
- [ ] Create local DB `svci_dev` and user `svci_dev`.
- [ ] Configure `.env` with local DB credentials.
- [ ] Run `php artisan migrate` (default Laravel migrations).

## 0.6 Reverb

- [ ] Install Reverb: `php artisan install:broadcasting --reverb`.
- [ ] Configure Reverb env vars (placeholders for now).
- [ ] Verify `php artisan reverb:start` runs.

## 0.7 Testing Setup

- [ ] Install Pest 3: `composer require pestphp/pest --dev --with-all-dependencies` then `php artisan pest:install`.
- [ ] Create `phpunit.xml` test database config (separate `svci_test` DB).
- [ ] Add a smoke test that hits `/` and asserts 200.
- [ ] Run `./vendor/bin/pest` — passes.

## 0.8 Static Analysis & Code Style

- [ ] Install Larastan: `composer require larastan/larastan --dev`.
- [ ] Add `phpstan.neon` with level 6.
- [ ] Confirm Pint installed (Laravel default).
- [ ] Add `pint.json` config matching Laravel preset.
- [ ] Add `.editorconfig`.

## 0.9 Frontend Tooling

- [ ] Install ESLint + Prettier: `npm i -D eslint prettier eslint-plugin-vue @vue/eslint-config-prettier`.
- [ ] Add `.eslintrc.cjs` and `.prettierrc`.
- [ ] Add npm scripts: `lint`, `lint:fix`, `format`.
- [ ] Run lint — passes on starter code.

## 0.10 Required Composer Packages

- [ ] `composer require barryvdh/laravel-dompdf`
- [ ] `composer require maatwebsite/excel`
- [ ] `composer require intervention/image-laravel`
- [ ] `composer require spatie/laravel-permission` *(optional — we may use simple role enum + policies instead; decide here)*

## 0.11 GitHub Actions CI

- [ ] Add `.github/workflows/ci.yml` per [`docs/15-testing-strategy.md`](../docs/15-testing-strategy.md).
- [ ] Steps: checkout, PHP setup, composer install, npm install, build, migrate, pint --test, phpstan, pest.
- [ ] CI passes on initial commit.

## 0.12 Local Docker (Dev Parity)

- [ ] Create `docker-compose.yml` with services: `app`, `mysql`, `reverb`, `mailhog`.
- [ ] Create `Dockerfile.dev` (separate from production Dockerfile).
- [ ] Document `docker compose up` workflow in `README.md`.

## 0.13 Documentation Sync

- [ ] Copy/link `revamped-online-doc-system/docs/` into the new app repo (or keep as a separate planning repo — decide).
- [ ] Add `CONTRIBUTING.md` covering branch naming, commit format, PR template.

## 0.14 First PR

- [ ] Open PR: "chore: initial Laravel + Inertia + Vue + Tailwind setup".
- [ ] Invoke `code-reviewer` subagent on the diff.
- [ ] Address feedback.
- [ ] Merge to `main`.

---

## Exit Criteria

- ✅ `php artisan serve` shows the Inertia welcome page styled with Tailwind.
- ✅ `./vendor/bin/pest` passes.
- ✅ `npm run build` succeeds.
- ✅ CI is green on `main`.
- ✅ Docs accessible to all contributors.
