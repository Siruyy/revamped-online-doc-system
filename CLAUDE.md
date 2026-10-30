# SVCI project context for Claude

Read [`AGENTS.md`](AGENTS.md) first and follow its project rules and `.cursor/rules/00-project-core.mdc` / `10-workflow-quality.mdc`. Then read [`docs/plan/README.md`](docs/plan/README.md) and the relevant active phase. Prefer current code and dated deployment evidence over older planning examples. Explicit user directions take precedence, including requests to work without subagents.

## The app is already deployed

This Laravel 13 / Inertia / Vue 3 application runs on a DigitalOcean VPS managed by **Dokploy**. Do not provision a replacement installation to make an ordinary code change.

- Deployment overview: [`docs/14-deployment.md`](docs/14-deployment.md).
- Authoritative recorded setup, identifiers, environment, DNS, and deployment history: [`docs/operations/dokploy-runbook.md`](docs/operations/dokploy-runbook.md).
- Recorded production: `178.128.97.151`; panel `dokploy.siruyy.cloud`; app `https://svciregistrar.com`; WebSocket host `ws.svciregistrar.com`.
- Git provider uses **`develop`** and **manual deployments**. Pushing does not automatically deploy.
- Runtime: root `Dockerfile` + `docker-compose.dokploy.yml`; app/Nginx, queue, Reverb, MySQL. App alone runs migrations on startup. Preserve existing `app_storage` and `mysql_data` volumes, the Compose project, and production secrets.
- Vite public Reverb settings are build-time values. Browser WebSockets use the public host; server broadcasts use internal `reverb:8080`. Allowed origins are hostnames without schemes.
- Production mail uses Resend. The documented simulator delivery check does not prove delivery to a real staff inbox.
- Secrets are in Dokploy and private recovery files identified in the runbook. Never print or commit their contents. Do not rerun seeders, reset volumes, regenerate the production app key, change DNS, or deploy unless that action is requested.
- Backup scheduling, restore drills, full staff UAT, real-inbox delivery, and rollback/monitoring evidence remain open. The 2026-10-06 deployment record is historical evidence, not a live health check.

## Current product behavior

Public requestors do not register accounts. Intake collects details and required files → Registrar evaluation and locked quote → clearance → payment receipt → Accounting validation → processing → claim slip and release. College/Graduate and Basic Education have distinct records and routing. Legacy student routes remain hidden, not removed.

The landing page links to `/pricelist`, reading active `DocumentType` rates directly. Admin and SuperAdmin maintain the catalog through Document Types. Accounting edits fees through Price List (`/department/prices`), without document-type create/delete/configuration privileges. Rates have formula-specific units and are reference amounts until the Registrar confirms the quote; existing confirmed quotes retain their amounts. Required intake fields have asterisks, including conditional fields. See [`docs/client-feedback-2026-10-30.md`](docs/client-feedback-2026-10-30.md).

## Work and verification

Controllers stay thin, validation uses Form Requests, authorization uses policies and role middleware, and business logic lives in `app/Services`. Receipts, requirements, signatures, and PDFs remain private and use authorized file routes. Keep audit logs for staff changes.

Use [`docs/local-development.md`](docs/local-development.md) for development only; its reset/demo setup is not a production deployment procedure. Use existing dependencies and checks. Behavior changes require tests; authorization, uploads, payment, private files, or user-data changes require a security review.

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress
npm run lint
npm run build
```

Run focused checks first. Local PHPUnit configuration defaults to isolated in-memory SQLite; production uses MySQL. Do not claim MySQL, browser, production, email, or realtime verification unless actually performed. The existing browser suite contains older workflow assumptions; inspect relevant specs before treating it as current UAT evidence.

Create focused local commits after completed, verified tasks when authorized by the user's task request. Preserve unrelated changes. A local commit does not authorize a push or production deployment.
