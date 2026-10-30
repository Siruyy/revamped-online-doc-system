# 14 — Deployment

The application is already deployed on a DigitalOcean VPS through Dokploy. The deployment and DNS cutover were recorded on 2026-10-06. This guide describes the checked-in deployment artifacts and the recorded setup; it does not claim a new live infrastructure verification.

## Authoritative operations context

Use [the Dokploy runbook](operations/dokploy-runbook.md) for project/Compose identifiers, environment settings, DNS records, setup history, and smoke checks. It takes precedence over historical plans and generic examples.

| Setting | Recorded value |
|---|---|
| VPS | `178.128.97.151` |
| Dokploy panel | `dokploy.siruyy.cloud` |
| App | `https://svciregistrar.com` |
| WebSocket host | `ws.svciregistrar.com` |
| Git source | `https://github.com/Siruyy/revamped-online-doc-system.git`, branch `develop` |
| Compose file | `docker-compose.dokploy.yml` |
| Deploy trigger | Manual in Dokploy; automatic deployments disabled |
| Health route | `/up` |

The old `100.109.80.94` address and `docs.siruyy.cloud` / `docs-ws.siruyy.cloud` domains were planning values. Do not use them for the deployed application. The Compose file also contains fallback domains and placeholder secrets; the explicit Dokploy environment is required.

## Checked-in runtime

- `Dockerfile`: multi-stage Composer and Vite build; PHP 8.4-FPM, Nginx, and Supervisor runtime. Use this file rather than copying a Dockerfile example from a document.
- `docker-compose.dokploy.yml`: `app`, `queue`, `reverb`, and MySQL 8.0 services. The three application services build the same source with different Supervisor commands.
- `docker/entrypoint.sh`: prepares writable directories, waits for MySQL, creates the public storage link, and refreshes Laravel caches. Only `app` sets `RUN_MIGRATIONS=true` and applies `migrate --force`.
- `docker/supervisord-queue.conf`: persistent database queue worker, restarted by Supervisor after its maximum runtime.
- `docker/supervisord-reverb.conf`: Reverb listens on internal port `8080`.
- Dokploy domains route HTTPS to `app:80` and WebSocket traffic to `reverb:8080`.
- `app_storage` mounts `/var/www/html/storage/app` on all three application services; `mysql_data` persists the database. Keep the existing Compose project and volume identities when redeploying. Logs under `storage/logs` are outside this persistent mount; inspect/export them before replacing containers when needed for incident evidence.

Private receipts, requirements, signatures, and PDFs use private storage and authorized controller routes. The public storage link does not authorize access to private files.

## Environment and rebuilds

The [runbook environment section](operations/dokploy-runbook.md#required-dokploy-environment) lists the required values. Secrets belong in Dokploy environment settings and the existing private recovery files, never in tracked documentation.

Keep the existing `APP_KEY`, database credentials, Reverb credentials, and storage volumes for an existing installation. Configure production mode, secure cookies, database sessions/cache/queues, and the Resend transport. Both `app` and `queue` need the same mail settings.

Server broadcasts use internal `REVERB_HOST=reverb`, port `8080`, scheme `http`. Browsers use `VITE_REVERB_HOST=ws.svciregistrar.com`, port `443`, scheme `https`. `REVERB_ALLOWED_ORIGINS` contains hostnames without schemes: `svciregistrar.com,www.svciregistrar.com`.

The Docker build supplies the public Reverb key and `VITE_REVERB_*` variables to Vite. Changes to those values require rebuilding and redeploying the frontend image; editing runtime settings alone does not rebuild browser assets.

## Updating the existing deployment

1. Verify the change locally with the relevant tests, Pint, PHPStan, ESLint, and Vite build. Review any migration and its compatibility with existing records.
2. Record the current deployed revision and confirm recoverable database and private-file backups before state-changing releases. Backup configuration and restore drills still require separate evidence.
3. Once publishing/deployment is requested, push the reviewed revision to the configured `develop` source and manually deploy the existing Compose application in Dokploy. A push alone does not deploy. Routine local edits and commits do not authorize production changes.
4. Verify the Dokploy deployment log, migrations, and all four services. Recreate/restart persistent queue and Reverb services with the new revision as well as the app.
5. Check `/up`, landing, `/pricelist`, request form, tracking, login, and authorized private files. Run the relevant staff workflow, queued-email, and two-session realtime checks from the runbook.
6. Record the revision, environment, checks, and outstanding items in [the verification log](operations/manual-verification-log.md).

Do not rerun seeders for ordinary feature updates. `ClearanceSignatorySeeder` resets the bundled staff passwords; `DemoDataSeeder` and `E2eSeeder` add sample records. First-run commands and the historical explicit demo-account authorization are documented separately in the runbook.

## Recovery and unfinished operational work

For an application regression, deploy the previous compatible revision through the same Compose application and preserve its existing volumes. A code rollback does not reverse database migrations. Review schema compatibility before rollback, and preserve the current data before any separately authorized correction or restore. See [the hotfix process](operations/hotfix.md).

Never run `migrate:fresh`, `db:wipe`, Compose `down -v`, or volume pruning against production. Keep DNS and mail records unchanged unless a cutover is explicitly requested.

The initial deployment record establishes container startup, migrations, public route/private-file smoke checks, and a Resend delivery-simulator check. It does not establish current uptime, real-inbox delivery, full staff UAT, scheduled off-server backups, a successful restore drill, or tested rollback/monitoring. Track that remaining work in [Phase 12](plan/phase-12-deployment.md) and [Phase 14](plan/phase-14-post-launch.md).
