# Dokploy Runbook

Use this runbook for the SVCI deployment on the DigitalOcean VPS managed by Dokploy. DNS cutover requires explicit authorization before changing existing records.

## Target

- VPS: `178.128.97.151`
- Dokploy panel: `dokploy.siruyy.cloud`
- App domain: `svciregistrar.com`
- Reverb domain: `ws.svciregistrar.com`
- Compose file: `docker-compose.dokploy.yml`
- Health check path: `/up`

Use a separate Dokploy project and dedicated MySQL and app-storage volumes. Never deploy with fresh volumes or remove production volumes.

- Dokploy project: `SVCI Registrar` (`dBxJJQhgzWxMR0Zuk6FNG`)
- Compose ID: `ofwoZ5fTVZUGXZ9igv8Tq`
- Compose app name: `svci-registrar-mrtrcb`
- Source: `https://github.com/Siruyy/revamped-online-doc-system.git`, branch `develop`
- Automatic deployments: disabled; deployments are manual.
- Initial application revision: `f89dc80ab1bd48b8e073c3ef77be93a7ec0b0769`

## DNS

In Cloudflare zone `svciregistrar.com` (`cca7dbf0f223dbd7fc3a3ad3a9ae9ebc`), switch these existing proxied A records only after origin checks pass and DNS cutover is authorized:

```text
@         A      178.128.97.151
ws        A      178.128.97.151
```

Preserve the `www` CNAME to the apex and all MX, SPF, and DKIM records. The previous apex and WebSocket origin was `168.144.106.39`; retain it for DNS rollback. Do not change the apex or wildcard records of `siruyy.cloud`.

## Required Dokploy Environment

Generate secrets outside the repo:

```bash
php artisan key:generate --show
openssl rand -base64 32
openssl rand -base64 32
openssl rand -base64 32
```

Set these variables in Dokploy:

```env
APP_NAME="SVCI Document System"
APP_KEY=base64:replace_with_php_artisan_key
APP_URL=https://svciregistrar.com
APP_TIMEZONE=Asia/Manila

DB_DATABASE=svci
DB_USERNAME=svci_app
DB_PASSWORD=replace_with_random_secret
MYSQL_ROOT_PASSWORD=replace_with_different_random_secret

REVERB_APP_ID=svci
REVERB_APP_KEY=replace_with_random_public_key
REVERB_APP_SECRET=replace_with_random_secret
REVERB_ALLOWED_ORIGINS=https://svciregistrar.com,https://www.svciregistrar.com
VITE_REVERB_HOST=ws.svciregistrar.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

SESSION_SECURE_COOKIE=true

MAIL_MAILER=resend
RESEND_KEY=re_replace_with_resend_api_key
MAIL_FROM_ADDRESS=noreply@your-verified-domain.com
MAIL_FROM_NAME="SVCI Document System"
```

The `MAIL_FROM_ADDRESS` domain must be verified in Resend. Keep `RESEND_KEY` secret and provide it to both the `app` and `queue` services through Dokploy environment variables.

## Dokploy Setup

1. Create a new Dokploy project, for example `svci-document-system`.
2. Add a Compose application from the Git repository.
3. Set the Compose file path to `docker-compose.dokploy.yml`.
4. Add the environment variables above.
5. Add the app domain `svciregistrar.com` to the `app` service on container port `80`.
6. Add the Reverb domain `ws.svciregistrar.com` to the `reverb` service on container port `8080`.
7. Enable HTTPS for both domains.
8. Configure health check path `/up` for the app service.
9. Deploy.

The `app` container runs migrations on startup. The `queue` and `reverb` containers use the same image with different Supervisor commands and do not run migrations.

## First-Run Commands

After the first successful deploy, open a Dokploy terminal for the `app` container:

```bash
php artisan db:seed --class=DocumentTypeSeeder --force
php artisan db:seed --class=AcademicProgramSeeder --force
php artisan svci:make-superadmin admin@example.com
```

Replace `admin@example.com` with the authorized SuperAdmin email. The command prompts for account details and password.

Do **not** run `ProductionSeeder`, `DatabaseSeeder`, `ClearanceSignatorySeeder`, `DemoDataSeeder`, or `E2eSeeder` on production: the clearance seeder currently creates active demo staff with a known password. Create real staff through the approved administration workflow.

A new app key, database passwords, and Reverb credentials are stored in Dokploy environment settings. Local recovery copy: `~/.codex/deployment-secrets/svci-registrar.env` (mode `0600`). Never print or commit it.

No Resend API key was available in this workspace during initial setup. Mail initially uses `log`; configure `MAIL_MAILER=resend` and a valid `RESEND_KEY` in Dokploy before verifying real delivery. DNS verification records alone do not verify API access or delivery.

## Smoke Test

1. Visit `https://svciregistrar.com/up`; expect HTTP 200.
2. Visit `https://svciregistrar.com`; expect the landing page.
3. Verify the public document-request form and reference-number tracking.
4. Sign in with an authorized staff account.
5. Submit an authorized test request, quote it, and verify sequential clearance.
6. Verify later payment upload, accounting validation, processing, and release.
7. Confirm the queue worker processes notifications.
8. Open two browser sessions and confirm Reverb updates or polling fallback works.

## Verification And Cutover

1. Verify `/up`, landing, login, and public request form at the new origin with the production Host header.
2. Confirm migrations finished and app, queue, Reverb, and MySQL are running.
3. Confirm `.env`, private storage, and private file routes do not disclose files anonymously.
4. With explicit DNS authorization, switch the two A records above.
5. Verify HTTPS certificates and public requests through Cloudflare, including a WebSocket handshake.
6. Verify the existing CIHM service remains healthy.

Never run `migrate:fresh`, `db:wipe`, Compose `down -v`, or volume pruning on this deployment. Backups, restore drills, real-inbox delivery, and staff workflow UAT must be recorded separately before operational handoff.

## Initial Deployment Verification — 2026-10-06

- All 35 migrations applied to the new MySQL database; no legacy import.
- Document types and academic programs seeded individually; no demo staff seeded.
- Temporary SuperAdmin: username `svciadmin`, email `admin@svciregistrar.com`. Credentials are in `~/.codex/deployment-secrets/svci-admin.json` (mode `0600`); replace the placeholder mailbox when provisioning real staff. Login and authenticated dashboard checks passed.
- Main and `ws` DNS records cut over to `178.128.97.151` with explicit authorization; `www` and mail records preserved.
- Let’s Encrypt certificates issued for apex, `www`, and `ws`. Cloudflare SSL mode remains `full`.
- Public `/up`, landing, login, request form, and tracking routes returned HTTP 200. Anonymous `.env` access returned 403, direct private storage returned 404, and private receipt access redirected to login.
- App, queue, and Reverb running; MySQL healthy; no failed queue jobs. Existing CIHM public endpoint returned HTTP 200.
- Initial storage-volume copy race resolved by retrying with existing volumes; no volume reset.
- Local predeploy tests: 428 tests / 2378 assertions; Pint, PHPStan, ESLint, and Vite build passed. Realtime CSP regression fix: 26 focused security tests / 116 assertions passed.

Mail delivery remains unverified until a Resend API key is configured. Cloudflare’s injected analytics beacon is blocked by the application CSP; realtime must use the exact public WebSocket host rather than the internal Docker host.
