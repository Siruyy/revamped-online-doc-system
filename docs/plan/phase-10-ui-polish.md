# Phase 10 — UI/UX Polish & Design System

> **Goal:** Bring the visual design to production-quality — consistent components, animations, empty states, accessibility, mobile polish.

**Subagents:** `code-reviewer`, `refactor-cleaner`.
**Skills:** `ui-ux-pro-max`, `frontend-patterns`.
**Depends on:** Phases 03–08 (UI exists).

---

## 10.1 Design Token Audit

- [ ] Verify all pages use Tailwind tokens from config (no hardcoded hex codes).
- [ ] Replace inline colors with brand palette references.
- [ ] Standardize spacing scale (multiples of 4).

## 10.2 Component Library Review

For each shared component, confirm:
- [ ] `<PageHeader>` consistent across all pages.
- [ ] `<Card>` used for all surface containers.
- [ ] `<DataTable>` covers all list views (no one-off tables).
- [ ] `<StatusBadge>` used everywhere statuses are shown.
- [ ] `<StatCard>` consistent across dashboards.
- [ ] `<EmptyState>` with friendly copy on every list.
- [ ] `<ConfirmDialog>` on every destructive action.
- [ ] `<FormField>` wrapping all inputs (label + error consistency).
- [ ] `<Toast>` for all flash messages.
- [ ] `<Skeleton>` loaders (no spinners).
- [ ] `<Pagination>` consistent.

## 10.3 Mobile Responsiveness Sweep

Test every page on:
- [ ] iPhone SE (375px)
- [ ] iPhone 14 Pro Max (430px)
- [ ] iPad (768px)
- [ ] iPad Pro (1024px)
- [ ] Desktop (1440px)

Fix any:
- [ ] Tables overflowing — convert to card layout on small screens
- [ ] Sidebars not collapsing
- [ ] Modals not fitting
- [ ] Touch targets <44px

## 10.4 Accessibility (WCAG AA)

- [ ] All interactive elements keyboard-accessible (Tab navigation works everywhere).
- [ ] Focus rings visible on all focusable elements.
- [ ] Color contrast ≥4.5:1 for text.
- [ ] Form inputs have associated labels.
- [ ] Error messages announced via `aria-live`.
- [ ] Modals trap focus.
- [ ] Images have alt text.
- [ ] Run automated audit with **axe DevTools** or **Lighthouse** — fix all critical issues.

## 10.5 Animations & Transitions

- [ ] Modal fade + scale (Headless UI defaults).
- [ ] Sidebar slide animation.
- [ ] Toast slide-in / fade-out.
- [ ] Tab transitions.
- [ ] Skeleton shimmer.
- [ ] No animation longer than 300ms.

## 10.6 Loading & Error States

- [ ] Inertia progress bar configured (NProgress style, brand color).
- [ ] Skeleton placeholders for initial loads.
- [ ] Error pages: 403, 404, 419 (CSRF expired), 500 — all themed Inertia pages.
- [ ] Network error toast on Inertia request failure.

## 10.7 Empty States with Illustrations

- [ ] Source SVG illustrations from undraw.co (free, themeable).
- [ ] Themed to brand color.
- [ ] Placed on: empty requests list, empty notifications, empty messages, empty announcements.

## 10.8 Print Styles

- [ ] Request detail page printable.
- [ ] Clearance status page printable.
- [ ] Invoice / receipt printable (if added).

## 10.9 Favicon & Branding

- [ ] Custom favicon.
- [ ] App icons for PWA (`apple-touch-icon`, etc.).
- [ ] OpenGraph meta tags for shareable links.
- [ ] PWA manifest (basic).

## 10.10 Performance

- [ ] Run Lighthouse on key pages, target 90+ scores.
- [ ] Lazy-load Vue routes (`defineAsyncComponent`).
- [ ] Image optimization (WebP, lazy loading).
- [ ] Tailwind build is JIT (no unused CSS).
- [ ] Verify `npm run build` output is reasonable (~500 KB JS for entry).

## 10.11 Cleanup

- [ ] Invoke `refactor-cleaner` subagent to find dead code, unused components.
- [ ] Run `npx knip` (or equivalent) to find unused exports.
- [ ] Remove all `console.log` and `dd()` calls.
- [ ] Update `package.json` and `composer.json` — remove unused deps.

---

## Exit Criteria

- ✅ Every page looks polished and consistent.
- ✅ Mobile experience is excellent.
- ✅ Accessibility audit passes.
- ✅ Lighthouse scores 90+ across the board.
- ✅ No dead code, no console noise.
