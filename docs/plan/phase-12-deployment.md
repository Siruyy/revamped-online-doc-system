# Phase 12 — VPS / Dokploy Operational Closeout

> **Goal:** Preserve the existing deployment and complete operational handoff evidence.

**Status:** Deployed; operational closeout open. The 2026-10-06 deployment record supersedes the old “not started / no production artifacts” audit. Do not move this phase to `finished/` until the remaining acceptance checks are verified or explicitly deferred.

**Evidence:** [`../operations/dokploy-runbook.md`](../operations/dokploy-runbook.md) records the existing DigitalOcean VPS, Dokploy project/Compose identifiers, Git source on `develop`, manual deployment mode, DNS cutover, initial migrations, HTTP/private-file checks, queue/container checks, and Resend simulator delivery. These are dated records, not new live checks.

**Primary docs:** [`../14-deployment.md`](../14-deployment.md), [`../10-security.md`](../10-security.md), [`../11-file-storage.md`](../11-file-storage.md).

## Recorded implementation

- Production `Dockerfile`, `.dockerignore`, `docker-compose.dokploy.yml`, Nginx, entrypoint, and app/queue/Reverb Supervisor configs exist.
- App, queue, Reverb, and MySQL deployed with persistent application-storage and database volumes.
- Production domains and HTTPS configured; initial public and private-file smoke checks recorded.
- Document catalog/program seeders and SuperAdmin provisioning ran during initial setup. Later bundled staff setup was explicitly authorized; do not repeat it during ordinary releases.
- Resend configured and delivery simulator verified; real-inbox workflow verification remains separate.

## Agent Task 12.1 — Backup And Restore Evidence

**Delegate to:** deployment-patterns + database-migrations

**Read first:**
- `docs/operations/dokploy-runbook.md`
- `docker-compose.dokploy.yml`

**Files likely touched:**
- `docs/operations/backup-restore.md`
- `docs/operations/manual-verification-log.md`

**Steps:**
- [ ] Confirm the approved backup destination, retention, access controls, and current job configuration.
- [ ] Verify recoverable MySQL and private application-storage backups without replacing production volumes.
- [ ] Restore into a separate sandbox and verify records and private files.
- [ ] Record commands, date, environment, outcome, and responsible owner without secrets.

**Acceptance:**
- [ ] Scheduled backups and a successful sandbox restore are evidenced.

## Agent Task 12.2 — Production Workflow And Recovery Verification

**Delegate to:** deployment-patterns + security-review

**Read first:**
- `docs/14-deployment.md`
- `docs/operations/dokploy-runbook.md`
- `docs/manual-verification-checklist.md`

**Files likely touched:**
- `docs/operations/manual-verification-log.md`
- `docs/operations/hotfix.md`

**Steps:**
- [ ] With authorization for production test records, verify public intake, Registrar quote, clearance, later payment, Accounting validation, claim slip, and release.
- [ ] Verify real-inbox delivery and two-session Reverb behavior with polling fallback.
- [ ] Verify health/failed-job monitoring and record the operational owner.
- [ ] Record the actual host firewall, SSH key-only access, and Fail2ban configuration; the initial deployment record does not establish host-hardening completion.
- [ ] Demonstrate a rollback of a compatible revision in a sandbox, preserving volume identities and reviewing migration compatibility.
- [ ] Record remaining staff UAT and handoff items in Phase 14 without claiming unperformed checks.

**Acceptance:**
- [ ] Workflow, realtime/email, monitoring, and recovery evidence is dated and linked.
- [ ] Deployment is not recreated and existing production data is preserved.
