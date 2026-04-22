# Phase 12 — Deployment to DigitalOcean + Dokploy

> **Goal:** Live, accessible production deployment on DigitalOcean droplet via Dokploy. No domain initially; access via IP.

**Subagents:** `verify-app` (pre-deploy), `code-reviewer` (Dockerfile review), `security-reviewer` (production env check).
**Skills:** `deployment-patterns`, `docker-patterns`.
**Depends on:** Phase 11 passed.

---

## 12.1 Provision DigitalOcean Droplet

- [ ] Create droplet:
    - Image: Ubuntu 22.04 LTS
    - Plan: 2 vCPU / 4 GB RAM / 80 GB SSD
    - Region: closest to school (Singapore for PH-based)
    - Add SSH key
    - Enable monitoring
    - Enable backups (paid, recommended)
- [ ] SSH in, update: `apt update && apt upgrade -y`.
- [ ] Set hostname.
- [ ] Configure UFW firewall: allow 22, 80, 443, 3000 (Dokploy panel).
- [ ] Create non-root user with sudo.

## 12.2 Install Dokploy

- [ ] Run install script: `curl -sSL https://dokploy.com/install.sh | sh`.
- [ ] Access panel at `http://<droplet-ip>:3000`.
- [ ] Create Dokploy admin account.
- [ ] Configure SMTP for Dokploy notifications (optional).

## 12.3 Production Dockerfiles

- [ ] Create `Dockerfile` per [`docs/14-deployment.md`](../docs/14-deployment.md).
- [ ] Create `docker/nginx.conf`, `docker/supervisord.conf`, `docker/supervisord-queue.conf`, `docker/supervisord-reverb.conf`.
- [ ] Create `docker/entrypoint.sh`.
- [ ] Test build locally: `docker build -t svci .`.
- [ ] Test run locally with docker-compose.

## 12.4 Repository Configuration

- [ ] Push code to GitHub (private repo).
- [ ] Add Dokploy webhook for auto-deploy on push to `main`.
- [ ] Create `.dockerignore` (vendor, node_modules, tests, .env, .git).

## 12.5 Dokploy Project Setup

- [ ] Create project `svci-document-system`.
- [ ] Add **Application** → connect GitHub repo, select `Dockerfile`.
- [ ] Add **MySQL** service:
    - Strong root password
    - Create app database `svci`
    - Create app user `svci_app` with restricted privileges
- [ ] Add **Reverb** service (same image, override CMD).
- [ ] Add **Queue Worker** service (same image, override CMD).
- [ ] Configure persistent volumes:
    - `app-storage` → `/var/www/html/storage/app`
    - `mysql-data` → `/var/lib/mysql`

## 12.6 Environment Variables

- [ ] In Dokploy UI, set all `.env` variables per [`docs/14-deployment.md`](../docs/14-deployment.md).
- [ ] Generate `APP_KEY` via `php artisan key:generate --show`.
- [ ] Generate strong Reverb keys.
- [ ] Configure SMTP credentials.
- [ ] `APP_DEBUG=false`, `APP_ENV=production`.

## 12.7 Reverse Proxy Configuration

- [ ] Configure Dokploy to proxy:
    - `:80` / → app container
    - `:80` /app/* → reverb container with WebSocket upgrade
- [ ] Verify with browser: app loads, Reverb connects.

## 12.8 Initial Deployment

- [ ] Trigger first deploy via Dokploy.
- [ ] Watch logs for errors.
- [ ] Migrations run automatically via entrypoint.
- [ ] Application accessible via `http://<droplet-ip>`.

## 12.9 Post-Deploy Setup

- [ ] Run seeder: `dokploy exec app php artisan db:seed --class=ProductionSeeder`.
- [ ] Create initial SuperAdmin: `dokploy exec app php artisan svci:make-superadmin`.
- [ ] Log in as SuperAdmin, verify dashboard loads.
- [ ] Test all critical flows in production.

## 12.10 Monitoring

- [ ] Configure Dokploy healthcheck on `/health` endpoint.
- [ ] Set up Dokploy alerts (email on container restart).
- [ ] Tail application logs: `dokploy logs app`.
- [ ] Verify queue worker processing: `dokploy logs queue`.
- [ ] Verify Reverb running: `dokploy logs reverb`.

## 12.11 Backups

- [ ] Configure daily MySQL backup in Dokploy.
- [ ] Backup destination: DigitalOcean Spaces or external SFTP.
- [ ] Test restore procedure on a separate VPS.
- [ ] Document restore steps in `docs/14-deployment.md`.

## 12.12 Security Hardening

- [ ] Verify `.env` not web-accessible (test `curl http://<ip>/.env` returns 404).
- [ ] Verify `APP_DEBUG=false`.
- [ ] Verify default SuperAdmin password changed.
- [ ] SSH key-only login (disable password auth).
- [ ] Fail2ban installed for SSH.
- [ ] Run `lynis audit system` on droplet — address findings.

## 12.13 SSL (Once Domain Available)

- [ ] Point domain A record to droplet IP.
- [ ] In Dokploy → Application → Domains → add domain.
- [ ] Enable HTTPS (Let's Encrypt auto-issues).
- [ ] Update env: `APP_URL=https://...`, `SESSION_SECURE_COOKIE=true`, `REVERB_SCHEME=https`.
- [ ] Re-deploy.
- [ ] Verify HTTPS works, HTTP redirects.

## 12.14 Smoke Test Checklist

- [ ] Landing page loads
- [ ] Register new student → pending page
- [ ] SuperAdmin approves → email arrives → student can log in
- [ ] Submit document request
- [ ] Upload payment receipt
- [ ] Admin approves payment + request (real-time visible)
- [ ] Department signs clearance
- [ ] Student downloads clearance PDF
- [ ] Send chat message both directions

## 12.15 Documentation

- [ ] Document final deployment URL and credentials (securely shared with client).
- [ ] Document backup/restore procedure.
- [ ] Document common operations (restart container, clear cache, run migrations).

---

## Exit Criteria

- ✅ Application accessible via VPS IP.
- ✅ All services running (app, mysql, reverb, queue).
- ✅ Smoke tests pass on production.
- ✅ Backups configured and tested.
- ✅ Monitoring in place.
- ✅ Security hardening complete.
