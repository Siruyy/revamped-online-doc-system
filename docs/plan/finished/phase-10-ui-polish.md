# Phase 10 — UI/UX Polish And Design System

> **Goal:** Make the existing UI consistent, accessible, mobile-safe, and production-presentable without changing core workflows.

**Status:** Finished for v1 closeout. Automated lint/build checks pass and no production console noise remains. Broad visual redesign, manual mobile/accessibility lab testing, full ConfirmDialog adoption, advanced loading/print states, and remaining design-system sweeps are deferred/not-v1 rather than active blockers.

**Phase Notes:** Responsive/accessibility hardening added shared UI primitives and Playwright coverage. A focused clearance status badge consistency slice now uses `StatusBadge` on admin, department, and student clearance screens. On 2026-05-13, a Phase 10 audit identified exact primitive gaps and added shared `Pagination.vue` across admin requests, admin releases, student requests, notifications, SuperAdmin logs, and SuperAdmin users. On 2026-05-14, closeout verification found no `console.` calls in `resources/js`; `npm run lint` and `npm run build` pass. Remaining broad polish items are intentionally deferred/not-v1 because they require manual device/browser/accessibility review or wider redesign beyond this closeout.

**Deferred / Not V1:**
- Full request, payment, user, and account status sweep beyond currently converted screens.
- Full ConfirmDialog rollout for destructive actions.
- Manual mobile viewport lab across all role dashboards and data-heavy pages.
- Lighthouse/axe accessibility pass on representative authenticated flows.
- Themed error pages, skeleton rollout, print styles, image optimization, and broader visual redesign acceptance review.

**Depends on:** Stable feature pages from Phases 03-09.

**Primary docs:** [`09-frontend-design.md`](../../09-frontend-design.md), [`17-design-system.md`](../../17-design-system.md), [`18-uat-script.md`](../../18-uat-script.md).

---

## Agent Task 10.1 — UI Inventory And Design Drift Audit

**Delegate to:** code-explorer + frontend-patterns

**Read first:**
- `resources/js/Layouts/*`
- `resources/js/Components/UI/*`
- `resources/js/Pages/**/*`
- `tailwind.config.js`
- `docs/17-design-system.md`

**Steps:**
- [x] Inventory shared UI primitives currently available.
- [x] List pages not using shared `Card`, `StatusBadge`, `EmptyState`, `StatCard`, `Pagination`, or `ConfirmDialog` where applicable.
- [x] List hardcoded colors that should become Tailwind tokens.
- [x] List pages with table overflow risk on mobile.

**Acceptance:**
- [x] Audit output identifies exact files for follow-up tasks.

## Agent Task 10.2 — Shared Component Consistency

**Delegate to:** frontend-patterns

**Files likely touched:**
- `resources/js/Components/UI/*`
- `resources/js/Pages/Admin/**/*`
- `resources/js/Pages/Student/**/*`
- `resources/js/Pages/Department/**/*`
- `resources/js/Pages/SuperAdmin/**/*`

**Steps:**
- [ ] Use `StatusBadge` for every request, payment, clearance, user, and account status. Clearance, request, and payment status screens have visible coverage; full user/account sweep is deferred/not-v1.
- [ ] Use `EmptyState` for empty lists. Coverage exists on key request, notification, user, log, report, dashboard, payment, FAQ, and clearance pages; exhaustive sweep is deferred/not-v1.
- [ ] Use `ConfirmDialog` for destructive actions. Deferred/not-v1.
- [x] Use shared `Pagination` for known manual pagination blocks in admin requests, admin releases, student requests, notifications, SuperAdmin logs, and SuperAdmin users.
- [ ] Use consistent card containers for dashboards and detail pages. Deferred/not-v1 broad visual sweep.
- [ ] Ensure flash/toast patterns are consistent. Deferred/not-v1 broad visual sweep.

**Acceptance:**
- [ ] Similar pages use same primitives and visual language. Partial coverage complete; exhaustive acceptance is deferred/not-v1.

## Agent Task 10.3 — Mobile Responsiveness Sweep

**Delegate to:** frontend-patterns + ui-ux-pro-max

**Viewports:** 375px, 430px, 768px, 1024px, 1440px.

**Steps:**
- [ ] Test student dashboard, request create, request show, payments, clearance. Deferred/not-v1 manual viewport lab.
- [ ] Test admin dashboard, requests, payments, document types, reports. Deferred/not-v1 manual viewport lab.
- [ ] Test department dashboard, clearance list/detail. Deferred/not-v1 manual viewport lab.
- [ ] Test SuperAdmin users, pending users, logs, reports. Deferred/not-v1 manual viewport lab.
- [ ] Convert overflowing tables to stacked cards or horizontally safe containers on small screens. Deferred/not-v1 unless a concrete overflow bug is reported.
- [ ] Ensure touch targets are at least 44px. Deferred/not-v1 manual viewport lab.

**Acceptance:**
- [ ] No core page requires horizontal scrolling on mobile except intentionally scrollable data tables. Deferred/not-v1 manual viewport lab.

## Agent Task 10.4 — Accessibility Pass

**Delegate to:** frontend-patterns + code-reviewer

**Steps:**
- [ ] Ensure all inputs have labels and visible validation errors. Deferred/not-v1 accessibility lab.
- [ ] Ensure modals trap focus and close with Escape. Deferred/not-v1 accessibility lab.
- [ ] Ensure destructive buttons have clear accessible names. Deferred/not-v1 accessibility lab.
- [ ] Ensure focus rings are visible. Deferred/not-v1 accessibility lab.
- [ ] Ensure status color is paired with text, not color-only meaning. `StatusBadge` pairs labels with tone where used; exhaustive sweep is deferred/not-v1.
- [ ] Run Lighthouse or axe on representative pages. Deferred/not-v1 accessibility lab.

**Acceptance:**
- [ ] No critical accessibility issue remains on core flows. Deferred/not-v1 accessibility lab.

## Agent Task 10.5 — Loading, Empty, Error, And Print States

**Delegate to:** frontend-patterns

**Files likely touched:**
- `resources/js/Pages/Error.vue` or Laravel error views
- `resources/js/Components/UI/Skeleton.vue`
- `resources/js/Pages/**/*`

**Steps:**
- [ ] Add or verify themed 403, 404, 419, and 500 pages. Deferred/not-v1.
- [ ] Use skeletons or stable placeholders for slow-loading sections. Deferred/not-v1.
- [ ] Add useful empty-state copy for requests, payments, notifications, messages, logs, and reports. Key pages have `EmptyState` coverage; exhaustive copy review is deferred/not-v1.
- [ ] Add print styles for request detail and clearance status pages. Deferred/not-v1.

**Acceptance:**
- [ ] Empty and error states look intentional, not broken. Partial coverage complete; exhaustive acceptance is deferred/not-v1.

## Agent Task 10.6 — Frontend Performance And Cleanup

**Delegate to:** frontend-patterns + code-reviewer

**Commands:**

```bash
npm run lint
npm run build
```

**Steps:**
- [x] Remove stray `console.log` calls from production code.
- [ ] Remove dead components identified by audit. Deferred/not-v1; no dead component removal was required for closeout.
- [ ] Verify images are optimized and use proper dimensions. Deferred/not-v1.
- [x] Verify production build size is reasonable and no accidental heavy dependency was added.

**Acceptance:**
- [x] `npm run lint` passes.
- [x] `npm run build` passes.
- [x] No production console noise remains.
