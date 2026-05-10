# Phase 10 — UI/UX Polish And Design System

> **Goal:** Make the existing UI consistent, accessible, mobile-safe, and production-presentable without changing core workflows.

**Status:** Partial.

**Phase Notes:** Responsive/accessibility hardening added shared UI primitives and Playwright coverage. A focused clearance status badge consistency slice now uses `StatusBadge` on admin, department, and student clearance screens; remaining Phase 10 tasks still need a full design drift audit and acceptance review before this phase can be marked finished.

**Depends on:** Stable feature pages from Phases 03-09.

**Primary docs:** [`09-frontend-design.md`](../09-frontend-design.md), [`17-design-system.md`](../17-design-system.md), [`18-uat-script.md`](../18-uat-script.md).

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
- [ ] Inventory shared UI primitives currently available.
- [ ] List pages not using shared `Card`, `StatusBadge`, `EmptyState`, `StatCard`, `Pagination`, or `ConfirmDialog` where applicable.
- [ ] List hardcoded colors that should become Tailwind tokens.
- [ ] List pages with table overflow risk on mobile.

**Acceptance:**
- [ ] Audit output identifies exact files for follow-up tasks.

## Agent Task 10.2 — Shared Component Consistency

**Delegate to:** frontend-patterns

**Files likely touched:**
- `resources/js/Components/UI/*`
- `resources/js/Pages/Admin/**/*`
- `resources/js/Pages/Student/**/*`
- `resources/js/Pages/Department/**/*`
- `resources/js/Pages/SuperAdmin/**/*`

**Steps:**
- [ ] Use `StatusBadge` for every request, payment, clearance, user, and account status. Clearance status screens are done; request, payment, user, and account status screens still need a full sweep.
- [ ] Use `EmptyState` for empty lists.
- [ ] Use `ConfirmDialog` for destructive actions.
- [ ] Use consistent card containers for dashboards and detail pages.
- [ ] Ensure flash/toast patterns are consistent.

**Acceptance:**
- [ ] Similar pages use same primitives and visual language.

## Agent Task 10.3 — Mobile Responsiveness Sweep

**Delegate to:** frontend-patterns + ui-ux-pro-max

**Viewports:** 375px, 430px, 768px, 1024px, 1440px.

**Steps:**
- [ ] Test student dashboard, request create, request show, payments, clearance.
- [ ] Test admin dashboard, requests, payments, document types, reports.
- [ ] Test department dashboard, clearance list/detail.
- [ ] Test SuperAdmin users, pending users, logs, reports.
- [ ] Convert overflowing tables to stacked cards or horizontally safe containers on small screens.
- [ ] Ensure touch targets are at least 44px.

**Acceptance:**
- [ ] No core page requires horizontal scrolling on mobile except intentionally scrollable data tables.

## Agent Task 10.4 — Accessibility Pass

**Delegate to:** frontend-patterns + code-reviewer

**Steps:**
- [ ] Ensure all inputs have labels and visible validation errors.
- [ ] Ensure modals trap focus and close with Escape.
- [ ] Ensure destructive buttons have clear accessible names.
- [ ] Ensure focus rings are visible.
- [ ] Ensure status color is paired with text, not color-only meaning.
- [ ] Run Lighthouse or axe on representative pages.

**Acceptance:**
- [ ] No critical accessibility issue remains on core flows.

## Agent Task 10.5 — Loading, Empty, Error, And Print States

**Delegate to:** frontend-patterns

**Files likely touched:**
- `resources/js/Pages/Error.vue` or Laravel error views
- `resources/js/Components/UI/Skeleton.vue`
- `resources/js/Pages/**/*`

**Steps:**
- [ ] Add or verify themed 403, 404, 419, and 500 pages.
- [ ] Use skeletons or stable placeholders for slow-loading sections.
- [ ] Add useful empty-state copy for requests, payments, notifications, messages, logs, and reports.
- [ ] Add print styles for request detail and clearance status pages.

**Acceptance:**
- [ ] Empty and error states look intentional, not broken.

## Agent Task 10.6 — Frontend Performance And Cleanup

**Delegate to:** frontend-patterns + code-reviewer

**Commands:**

```bash
npm run lint
npm run build
```

**Steps:**
- [ ] Remove stray `console.log` calls from production code.
- [ ] Remove dead components identified by audit.
- [ ] Verify images are optimized and use proper dimensions.
- [ ] Verify production build size is reasonable and no accidental heavy dependency was added.

**Acceptance:**
- [ ] `npm run build` passes.
- [ ] No production console noise remains.
