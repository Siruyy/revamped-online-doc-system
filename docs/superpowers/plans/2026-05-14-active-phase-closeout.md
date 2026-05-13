# Active Phase Closeout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete feasible active phase closeout for SuperAdmin routes, realtime/notification documentation, and UI polish, then archive finished phase plans while leaving Phase 12 and 13 out of scope.

**Architecture:** Keep the smallest possible implementation surface. Reuse existing Admin controllers/pages for SuperAdmin admin-resource aliases, preserve service-based notification side effects, and close UI polish with targeted consistency and verification rather than a redesign.

**Tech Stack:** Laravel 13, Inertia.js, Vue 3, Tailwind CSS, Pest/PHPUnit, Vite, Playwright where already configured.

---

### File Map

- Modify `routes/superadmin.php`: add SuperAdmin aliases for request overview, report exports, document types, announcements, and FAQs without colliding with admin route names.
- Modify `resources/js/Layouts/StaffLayout.vue`: expose only implemented SuperAdmin menu routes.
- Add or modify `tests/Feature/SuperAdmin/SuperAdminRouteCoverageTest.php`: prove SuperAdmin access and non-SuperAdmin denial for added aliases.
- Modify `docs/plan/phase-06-superadmin-features.md`: mark Task 6.3 complete and update closeout notes.
- Modify `docs/plan/phase-07-realtime-and-notifications.md`: document completed automated verification and any remaining manual Reverb/browser blocker.
- Modify `docs/plan/phase-10-ui-polish.md`: mark targeted UI closeout items complete or explicitly deferred out of scope.
- Modify `docs/plan/README.md`: archive finished phases and mark Phase 12/13 as not needed/deferred per user instruction.
- Move completed phase files into `docs/plan/finished/` after verification passes.

### Task 1: SuperAdmin Route Coverage

**Files:**
- Modify: `routes/superadmin.php`
- Modify: `resources/js/Layouts/StaffLayout.vue`
- Test: `tests/Feature/SuperAdmin/SuperAdminRouteCoverageTest.php`

- [ ] **Step 1: Write route coverage tests**

Add tests that authenticate a SuperAdmin and verify these routes return successful responses or redirects: `superadmin.requests.index`, `superadmin.reports.exports.requests`, `superadmin.reports.exports.payments`, `superadmin.document-types.index`, `superadmin.announcements.index`, `superadmin.faqs.index`. Add matching tests that an admin user receives forbidden or redirect behavior when trying `/superadmin/*`.

- [ ] **Step 2: Run focused test to verify failure**

Run: `php artisan test --filter=SuperAdminRouteCoverageTest`
Expected: FAIL for missing route names.

- [ ] **Step 3: Add minimal route aliases**

In `routes/superadmin.php`, import existing Admin controllers and add GET/resource-style routes under SuperAdmin names. Reuse Admin controllers only where the global SuperAdmin middleware already authorizes the route group.

- [ ] **Step 4: Add menu links**

In `StaffLayout.vue`, add SuperAdmin menu entries for Requests, Document Types, Announcements, and FAQs only if corresponding routes exist.

- [ ] **Step 5: Verify focused behavior**

Run: `php artisan test --filter=SuperAdminRouteCoverageTest`
Expected: PASS.

### Task 2: Realtime And Notification Closeout

**Files:**
- Modify: `docs/plan/phase-07-realtime-and-notifications.md`
- Possibly modify: `.env.example`, `docs/14-deployment.md` only if queue/mail instructions are missing and not Phase 12-specific.

- [ ] **Step 1: Verify automated checks**

Run: `php artisan test --filter=Broadcast`, `php artisan test --filter=Notification`, and `npm run build`.
Expected: PASS or documented blocker with exact output.

- [ ] **Step 2: Document queue/email path**

If automated queue/mail settings already exist, mark Task 7.6 as reproducible and note commands. If local worker/browser verification cannot be run in this environment, leave only manual Reverb checklist documented as manual blocker.

- [ ] **Step 3: Update phase status**

Set Phase 07 status to finished for automated implementation, with manual Reverb/browser verification documented as operational follow-up rather than an active coding blocker.

### Task 3: UI Polish Closeout

**Files:**
- Modify: `resources/js/Pages/**/*.vue` only for small targeted fixes found during checks.
- Modify: `docs/plan/phase-10-ui-polish.md`

- [ ] **Step 1: Run frontend verification**

Run: `npm run lint` and `npm run build`.
Expected: PASS.

- [ ] **Step 2: Check production console noise**

Search `resources/js` for `console.`. Expected: no matches.

- [ ] **Step 3: Apply minimal UI fixes if verification exposes concrete defects**

Only fix concrete lint/build/accessibility defects. Do not redesign pages.

- [ ] **Step 4: Update phase status**

Mark completed automated UI polish tasks and explicitly defer broader visual redesign/accessibility lab testing as out of scope unless requested.

### Task 4: Archive Finished Plans

**Files:**
- Move: `docs/plan/phase-05-department-clearance.md` to `docs/plan/finished/phase-05-department-clearance.md`
- Move: `docs/plan/phase-06-superadmin-features.md` to `docs/plan/finished/phase-06-superadmin-features.md`
- Move: `docs/plan/phase-07-realtime-and-notifications.md` to `docs/plan/finished/phase-07-realtime-and-notifications.md`
- Move: `docs/plan/phase-09-pdf-and-exports.md` to `docs/plan/finished/phase-09-pdf-and-exports.md`
- Move: `docs/plan/phase-10-ui-polish.md` to `docs/plan/finished/phase-10-ui-polish.md`
- Move: `docs/plan/phase-11-testing-and-hardening.md` to `docs/plan/finished/phase-11-testing-and-hardening.md`
- Modify: `docs/plan/README.md`

- [ ] **Step 1: Move only verified/deferred-complete phase files**

Use file moves after acceptance checks are updated.

- [ ] **Step 2: Update index**

Remove archived phases from Active Phases. Keep Phase 08 deferred if messaging remains out of v1. Mark Phase 12 and Phase 13 as out of scope/not needed per user instruction rather than active next work.

- [ ] **Step 3: Verify docs diff and bad paths**

Run: `git diff -- docs/plan docs/superpowers/plans/2026-05-14-active-phase-closeout.md` and `rg "\.\./docs/" docs/plan AGENTS.md`.
Expected: diff matches planned archive and no bad `../docs/` path matches.

### Task 5: Security And Final Verification

**Files:**
- Review pending changes, no expected code edits unless findings are confirmed.

- [ ] **Step 1: Run SuperAdmin focused tests**

Run: `php artisan test --filter=SuperAdmin`.
Expected: PASS.

- [ ] **Step 2: Run route listing**

Run: `php artisan route:list --path=superadmin`.
Expected: Added aliases visible with `superadmin.*` names.

- [ ] **Step 3: Run style/static/frontend checks**

Run: `./vendor/bin/pint --test`, `./vendor/bin/phpstan analyse --no-progress`, `npm run lint`, and `npm run build`.
Expected: PASS.

- [ ] **Step 4: Run security review**

Review SuperAdmin route aliases for authorization bypass, unsafe HTTP verbs, route name collision, and sensitive export exposure.

- [ ] **Step 5: Commit**

Commit with message: `feat: close active phase plans`.
