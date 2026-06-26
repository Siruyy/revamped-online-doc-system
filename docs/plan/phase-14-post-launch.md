# Phase 14 — Post-Launch

> **Goal:** Operate the launched system safely, support users, verify backups, and maintain a realistic v2 roadmap.

**Status:** Partial. Support, role training, hotfix, maintenance, manual verification, and v2 roadmap docs exist. Launch-dependent monitoring, backup restore drill, and 30-day review remain future work.

**Depends on:** Phase 12 launch. Phase 13 only if legacy migration is in scope.

---

## Agent Task 14.1 — First Week Operations

**Delegate to:** verify-app + deployment-patterns

**Steps:**
- [ ] Check Laravel logs daily.
- [ ] Check failed jobs daily.
- [ ] Check Dokploy container restarts daily.
- [ ] Check disk usage for app storage and MySQL volumes.
- [ ] Record production incidents and fixes.

**Note:** Daily/weekly check procedures are documented in `docs/operations/runbook.md`. These checkboxes remain unchecked because they require execution during the first launch week.

**Acceptance:**
- [ ] First-week monitoring log exists.

## Agent Task 14.2 — Support Workflow

**Delegate to:** doc-updater

**Files likely touched:**
- `docs/operations/support.md`
- `docs/operations/runbook.md`

**Steps:**
- [x] Define support email or in-app support account.
- [x] Define severity levels: P0 outage, P1 critical workflow blocked, P2 degraded behavior, P3 minor issue.
- [x] Define response expectations for each severity.
- [x] Define how staff report issues with request references and screenshots.

**Acceptance:**
- [x] Client knows where and how to report issues.

## Agent Task 14.3 — User Guides And Training

**Delegate to:** doc-updater + frontend-patterns

**Files likely touched:**
- `docs/training/student-guide.md`
- `docs/training/admin-guide.md`
- `docs/training/department-guide.md`
- `docs/training/superadmin-guide.md`
- `docs/training/training-agenda.md`

**Steps:**
- [x] Create requestor guide for public request submission, receipt upload, and reference-number tracking.
- [x] Create admin guide for request/payment approval, document types, announcements, FAQs, and reports.
- [x] Create department guide for clearance signing/denial and signatures.
- [x] Create SuperAdmin guide for user approval, staff creation, logs, and reports.
- [x] Prepare short training agenda or screen-recording script.

**Acceptance:**
- [x] Guides cover the core role workflows in plain language.

## Agent Task 14.4 — Hotfix Process

**Delegate to:** deployment-patterns + code-reviewer

**Files likely touched:**
- `docs/operations/hotfix.md`

**Steps:**
- [x] Define branch-from-main hotfix flow.
- [x] Define minimum verification for hotfixes.
- [x] Define emergency rollback path.
- [x] Define user communication via announcement/email.

**Acceptance:**
- [x] Hotfix process is clear before first incident.

## Agent Task 14.5 — Backup Restore Drill

**Delegate to:** database-migrations + verify-app

**Steps:**
- [ ] Restore latest DB backup to sandbox.
- [ ] Restore app storage backup to sandbox.
- [ ] Run smoke test against restored sandbox.
- [ ] Record restore duration and issues.

**Acceptance:**
- [ ] Backup integrity is proven, not assumed.

## Agent Task 14.6 — 30-Day Review

**Delegate to:** doc-updater + backend-patterns

**Steps:**
- [ ] Review account counts, request counts, payment approval time, clearance completion time, and failed jobs.
- [ ] Review most common user questions and update FAQs.
- [ ] Review incidents and root causes.
- [ ] Document performance or UX bottlenecks.

**Acceptance:**
- [ ] 30-day report exists with action items.

## Agent Task 14.7 — V2 Roadmap

**Delegate to:** architect + doc-updater

**Candidate items:**
- Online payment gateway.
- SMS notifications.
- Bulk request approval.
- Two-factor authentication.
- Document version history.
- Notification preferences.
- Dark mode.
- PWA hardening.
- Analytics dashboard.

**Files likely touched:**
- `docs/operations/v2-roadmap.md`

**Steps:**
- [x] Rank v2 candidates by client value, risk, and implementation cost.
- [x] Separate must-have fixes from new features.
- [x] Create a v2 roadmap document.

**Acceptance:**
- [x] V2 roadmap is explicit and not mixed with launch support tasks.

## Agent Task 14.8 — Maintenance Cadence

**Delegate to:** deployment-patterns

**Files likely touched:**
- `docs/operations/maintenance.md`

**Steps:**
- [x] Monthly: review Composer/npm security updates.
- [x] Monthly: check disk usage and backup success.
- [x] Quarterly: run backup restore drill.
- [x] Quarterly: review audit logs and failed jobs.
- [x] Annually: plan PHP/Laravel major version upgrades.

**Acceptance:**
- [x] Maintenance calendar is documented.
