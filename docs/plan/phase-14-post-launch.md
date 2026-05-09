# Phase 14 — Post-Launch

> **Goal:** Operate the launched system safely, support users, verify backups, and maintain a realistic v2 roadmap.

**Status:** Not started.

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

**Acceptance:**
- [ ] First-week monitoring log exists.

## Agent Task 14.2 — Support Workflow

**Delegate to:** doc-updater

**Files likely touched:**
- `docs/operations/support.md`
- `docs/operations/runbook.md`

**Steps:**
- [ ] Define support email or in-app support account.
- [ ] Define severity levels: P0 outage, P1 critical workflow blocked, P2 degraded behavior, P3 minor issue.
- [ ] Define response expectations for each severity.
- [ ] Define how staff report issues with request references and screenshots.

**Acceptance:**
- [ ] Client knows where and how to report issues.

## Agent Task 14.3 — User Guides And Training

**Delegate to:** doc-updater + frontend-patterns

**Files likely touched:**
- `docs/training/student-guide.md`
- `docs/training/admin-guide.md`
- `docs/training/department-guide.md`
- `docs/training/superadmin-guide.md`

**Steps:**
- [ ] Create student guide for registration, request submission, payment upload, tracking, and PDF download.
- [ ] Create admin guide for request/payment approval, document types, announcements, FAQs, and reports.
- [ ] Create department guide for clearance signing/denial and signatures.
- [ ] Create SuperAdmin guide for user approval, staff creation, logs, and reports.
- [ ] Prepare short training agenda or screen-recording script.

**Acceptance:**
- [ ] Guides cover the core role workflows in plain language.

## Agent Task 14.4 — Hotfix Process

**Delegate to:** deployment-patterns + code-reviewer

**Files likely touched:**
- `docs/operations/hotfix.md`

**Steps:**
- [ ] Define branch-from-main hotfix flow.
- [ ] Define minimum verification for hotfixes.
- [ ] Define emergency rollback path.
- [ ] Define user communication via announcement/email.

**Acceptance:**
- [ ] Hotfix process is clear before first incident.

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

**Steps:**
- [ ] Rank v2 candidates by client value, risk, and implementation cost.
- [ ] Separate must-have fixes from new features.
- [ ] Create a v2 roadmap document.

**Acceptance:**
- [ ] V2 roadmap is explicit and not mixed with launch support tasks.

## Agent Task 14.8 — Maintenance Cadence

**Delegate to:** deployment-patterns

**Steps:**
- [ ] Monthly: review Composer/npm security updates.
- [ ] Monthly: check disk usage and backup success.
- [ ] Quarterly: run backup restore drill.
- [ ] Quarterly: review audit logs and failed jobs.
- [ ] Annually: plan PHP/Laravel major version upgrades.

**Acceptance:**
- [ ] Maintenance calendar is documented.
