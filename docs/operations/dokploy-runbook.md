# Dokploy Runbook

Use this runbook for the first VPS deployment on the temporary `siruyy.cloud` subdomains. Replace these with the permanent app domain once it is available.

## Target

- VPS: `100.109.80.94`
- Dokploy panel: `panel.siruyy.cloud`
- Temporary app domain: `docs.siruyy.cloud`
- Temporary Reverb domain: `docs-ws.siruyy.cloud`
- Compose file: `docker-compose.dokploy.yml`
- Health check path: `/up`

Use subdomains so the app does not collide with the Dokploy panel. These are temporary staging domains, not the final production branding.

## DNS

Create these A records in the DNS provider for `siruyy.cloud`:

```text
docs      A      100.109.80.94
docs-ws   A      100.109.80.94
```

When the permanent domain is available, change `APP_URL`, `REVERB_ALLOWED_ORIGINS`, and `VITE_REVERB_HOST` to match it.

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
APP_URL=https://docs.siruyy.cloud
APP_TIMEZONE=Asia/Manila

DB_DATABASE=svci
DB_USERNAME=svci_app
DB_PASSWORD=replace_with_random_secret
MYSQL_ROOT_PASSWORD=replace_with_different_random_secret

REVERB_APP_ID=svci
REVERB_APP_KEY=replace_with_random_public_key
REVERB_APP_SECRET=replace_with_random_secret
REVERB_ALLOWED_ORIGINS=https://docs.siruyy.cloud
VITE_REVERB_HOST=docs-ws.siruyy.cloud
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

SESSION_SECURE_COOKIE=true

MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@siruyy.cloud
MAIL_FROM_NAME="SVCI Document System"
```

Switch `MAIL_MAILER` and SMTP variables to the real provider before testing email delivery.

## Dokploy Setup

1. Create a new Dokploy project, for example `svci-document-system`.
2. Add a Compose application from the Git repository.
3. Set the Compose file path to `docker-compose.dokploy.yml`.
4. Add the environment variables above.
5. Add the app domain `docs.siruyy.cloud` to the `app` service on container port `80`.
6. Add the Reverb domain `docs-ws.siruyy.cloud` to the `reverb` service on container port `8080`.
7. Enable HTTPS for both domains.
8. Configure health check path `/up` for the app service.
9. Deploy.

The `app` container runs migrations on startup. The `queue` and `reverb` containers use the same image with different Supervisor commands and do not run migrations.

## First-Run Commands

After the first successful deploy, open a Dokploy terminal for the `app` container:

```bash
php artisan db:seed --class=ProductionSeeder --force
php artisan svci:make-superadmin admin@example.com
```

Replace `admin@example.com` with the real SuperAdmin email. The command prompts for the display name and password.

## Smoke Test

1. Visit `https://docs.siruyy.cloud/up`; expect HTTP 200.
2. Visit `https://docs.siruyy.cloud`; expect the landing page.
3. Register a student and confirm the account reaches pending approval.
4. Approve the student as SuperAdmin.
5. Submit a document request.
6. Upload a payment receipt.
7. Confirm the queue worker processes notifications.
8. Open two browser sessions and confirm Reverb updates or polling fallback works.

## Permanent Domain Cutover

When the real domain is ready:

1. Add DNS records for the permanent app and Reverb/WebSocket domains pointing to `100.109.80.94`.
2. Replace the Dokploy domains:
   - `docs.siruyy.cloud` -> permanent app domain
   - `docs-ws.siruyy.cloud` -> permanent Reverb domain
3. Update Dokploy env:
   - `APP_URL`
   - `REVERB_ALLOWED_ORIGINS`
   - `VITE_REVERB_HOST`
4. Redeploy. This is required because Vite embeds `VITE_REVERB_HOST` during image build.
5. Repeat the smoke test against the permanent domain.
