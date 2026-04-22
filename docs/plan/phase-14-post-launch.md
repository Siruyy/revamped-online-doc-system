# Phase 14 — Post-Launch

> **Goal:** Stability, monitoring, support handoff, and roadmap for v2.

**Subagents:** `code-reviewer` (post-mortems), `doc-updater`.
**Depends on:** Phase 12 + 13.

---

## 14.1 First Week Monitoring

- [ ] Tail logs daily for errors.
- [ ] Monitor failed jobs queue.
- [ ] Watch DB performance (slow query log).
- [ ] Address any user-reported bugs within 24 hours.

## 14.2 Hotfix Workflow

- [ ] Document hotfix process: branch from `main`, fix, PR, deploy.
- [ ] Establish severity levels (P0, P1, P2, P3).
- [ ] Communicate fixes via in-app announcement.

## 14.3 User Onboarding

- [ ] Create user guide PDF for students (1-2 pages).
- [ ] Create admin user guide.
- [ ] Create department officer guide.
- [ ] Host a brief training session (or screen recording) for staff.

## 14.4 Support Channel

- [ ] Set up support email or messaging channel (e.g., dedicated SuperAdmin account).
- [ ] Document SLA expectations.
- [ ] FAQ updates based on common questions.

## 14.5 Post-Launch Review

- [ ] After 30 days, review:
    - Number of accounts created
    - Number of requests processed
    - Average time per stage
    - Most common errors
    - User satisfaction (informal survey)
- [ ] Document learnings.

## 14.6 Backup Verification

- [ ] Quarterly: restore a backup to a sandbox to verify integrity.

## 14.7 Dependency Updates

- [ ] Monthly: review Renovate/Dependabot PRs.
- [ ] Quarterly: bump major versions where safe.
- [ ] Annual: PHP version upgrade plan.

## 14.8 V2 Roadmap

Items deferred from v1, prioritized for future:

- [ ] Online payment gateway integration (PayMongo / GCash API).
- [ ] SMS notifications (Twilio / Semaphore).
- [ ] Bulk request approval.
- [ ] Two-factor authentication.
- [ ] Document version history.
- [ ] Audit log export with date range.
- [ ] Notification preferences UI.
- [ ] Dark mode.
- [ ] Native mobile apps (or PWA hardening).
- [ ] Multi-tenant support (if SVCI expands branches).
- [ ] Analytics dashboard with charts.

## 14.9 Tech Debt Register

- [ ] Maintain a `TECH_DEBT.md` file in repo for known shortcuts.
- [ ] Review quarterly, plan repayment.

## 14.10 Handoff Documentation

- [ ] Document credentials handoff procedure.
- [ ] Document admin operations runbook.
- [ ] Document common troubleshooting.
- [ ] Provide architecture overview to any future developers.

---

## Exit Criteria

- ✅ System stable for 30+ days post-launch.
- ✅ Client / users trained.
- ✅ Support process running.
- ✅ Backup integrity verified.
- ✅ V2 roadmap documented.
