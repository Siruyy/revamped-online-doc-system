# Phase 12 — Deployment To DigitalOcean And Dokploy

> **Goal:** Ship a production-ready deployment on a DigitalOcean VPS managed by Dokploy, with app, MySQL, queue worker, Reverb, backups, and smoke tests.

**Status:** Not started. Audit found only `Dockerfile.dev`; production deployment artifacts are missing.

**Depends on:** Phase 11 verification passing.

**Primary docs:** [`14-deployment.md`](../14-deployment.md), [`10-security.md`](../10-security.md), [`11-file-storage.md`](../11-file-storage.md).

---

## Agent Task 12.1 — Production Deployment Design Review

**Delegate to:** architect + deployment-patterns

**Steps:**
- [ ] Confirm target droplet: Ubuntu 22.04, 2 vCPU, 4 GB RAM, 80 GB SSD.
- [ ] Confirm no domain initially; app served by IP first.
- [ ] Confirm services: app, MySQL 8, queue worker, Reverb.
- [ ] Confirm persistent volumes: app storage and MySQL data.
- [ ] Confirm backup destination and retention with client.

**Acceptance:**
- [ ] Deployment topology is confirmed before Docker work starts.

## Agent Task 12.2 — Production Docker Artifacts

**Delegate to:** docker-patterns + code-reviewer

**Files likely touched:**
- `Dockerfile`
- `.dockerignore`
- `docker/nginx.conf`
- `docker/supervisord.conf`
- `docker/supervisord-queue.conf`
- `docker/supervisord-reverb.conf`
- `docker/entrypoint.sh`

**Steps:**
- [ ] Build production image with PHP extensions, Composer deps, Node build assets, and optimized Laravel cache path.
- [ ] Exclude `.env`, `.git`, `node_modules`, tests if not needed, local storage, and dev artifacts from image context.
- [ ] Add Nginx config serving `public/` only.
- [ ] Add entrypoint that runs safe cache/migration steps.
- [ ] Add separate supervisor configs for app, queue, and Reverb if using same image with command override.

**Commands:**

```bash
docker build -t svci-doc-system .
```

**Acceptance:**
- [ ] Production image builds locally.
- [ ] Image does not include secrets.

## Agent Task 12.3 — Local Container Smoke Test

**Delegate to:** verify-app + docker-patterns

**Files likely touched:**
- `docker-compose.prod-test.yml` or equivalent local smoke config

**Steps:**
- [ ] Run app and MySQL locally from production image/config.
- [ ] Run migrations.
- [ ] Run production seeder or create SuperAdmin.
- [ ] Verify app loads through container web server.
- [ ] Verify queue worker and Reverb containers start.

**Acceptance:**
- [ ] Production container setup works before Dokploy deployment.

## Agent Task 12.4 — Dokploy Project Setup Runbook

**Delegate to:** deployment-patterns

**Files likely touched:**
- `docs/14-deployment.md`
- `docs/operations/runbook.md`

**Steps:**
- [ ] Document droplet provisioning steps.
- [ ] Document Dokploy install and admin setup.
- [ ] Document GitHub repo connection and deploy webhook.
- [ ] Document MySQL service setup with restricted app user.
- [ ] Document app, queue, and Reverb service setup.
- [ ] Document persistent volume mounts.

**Acceptance:**
- [ ] A future deploy agent can follow the runbook without guessing.

## Agent Task 12.5 — Production Environment And Security

**Delegate to:** security-review + deployment-patterns

**Files likely touched:**
- `.env.example`
- `docs/14-deployment.md`

**Steps:**
- [ ] List required env vars for app, DB, mail, queue, Reverb, session, and filesystem.
- [ ] Ensure `APP_DEBUG=false`, `APP_ENV=production`, strong `APP_KEY`, strong DB passwords.
- [ ] Configure secure cookies only after HTTPS/domain exists.
- [ ] Verify `.env` and private files are not web-accessible.
- [ ] Configure UFW, SSH key-only login, and Fail2ban.

**Acceptance:**
- [ ] Production env checklist is explicit and security-reviewed.

## Agent Task 12.6 — Backup And Restore Procedure

**Delegate to:** deployment-patterns + database-migrations

**Files likely touched:**
- `docs/14-deployment.md`
- `docs/operations/backup-restore.md`

**Steps:**
- [ ] Configure daily MySQL backup.
- [ ] Configure storage backup for private uploaded files.
- [ ] Test restore to a separate sandbox.
- [ ] Document restore commands and validation checklist.

**Acceptance:**
- [ ] Restore has been tested, not only configured.

## Agent Task 12.7 — Production Smoke Test

**Delegate to:** verify-app

**Checklist:**
- [ ] Landing page loads.
- [ ] Register student and see pending page.
- [ ] SuperAdmin approves student.
- [ ] Student logs in and submits request.
- [ ] Student uploads payment receipt.
- [ ] Admin approves request/payment.
- [ ] Department signs clearance.
- [ ] Student downloads clearance PDF.
- [ ] Reverb updates visible in two browser windows.
- [ ] Queue worker processes notification/email jobs.

**Acceptance:**
- [ ] Smoke test passes on production IP or staging equivalent.

## Agent Task 12.8 — Deployment Closeout

**Delegate to:** doc-updater + code-reviewer

**Steps:**
- [ ] Record deployment URL securely outside repo if credentials are involved.
- [ ] Update docs with learned commands and operational caveats.
- [ ] Confirm health endpoint exists and Dokploy monitors it.
- [ ] Confirm rollback plan is documented.

**Acceptance:**
- [ ] App, queue, Reverb, MySQL, backups, monitoring, and rollback are documented and verified.
