# SVCI Design System

This document is the single source of truth for visual and interaction
patterns across the SVCI Online Document System. It complements
`16-policy-matrix.md` (which defines *what* the system enforces) by
defining *how* it is presented to users.

The system is built on **Tailwind CSS + Inertia + Vue 3** and ships a
small library of reusable UI primitives in
`resources/js/Components/UI/`.

## 1. Brand Tokens

Defined in `tailwind.config.js`:

| Token        | Hex       | Use                                              |
| ------------ | --------- | ------------------------------------------------ |
| `brand-50`   | `#eff6ff` | Tinted backgrounds, hover halos                  |
| `brand-100`  | `#dbeafe` | Badges, pills, soft icon backplates              |
| `brand-500`  | `#3b82f6` | Borders, focus rings                             |
| `brand-600`  | `#2563eb` | Primary buttons, links, active nav                |
| `brand-700`  | `#1d4ed8` | Hover state, pressed buttons                     |
| `brand-900`  | `#1e3a8a` | High contrast text on light                      |
| `accent-500` | `#f97316` | Secondary CTA accents only — sparingly           |
| `slate-*`    | —         | Neutral surfaces, text, dividers                 |
| `emerald-*`  | —         | Success states, positive deltas                  |
| `amber-*`    | —         | Warnings, pending, awaiting action               |
| `rose-*`     | —         | Errors, destructive actions, overdue             |
| `sky-*`      | —         | Informational, in-progress                       |

Fonts:

- **Display**: Rubik (used for headings, KPIs)
- **Body**: Nunito Sans (UI text, paragraphs)

## 2. Spacing & Radius Scale

Use the Tailwind defaults. Concrete conventions:

- Page horizontal padding: `px-4 sm:px-6 lg:px-8`.
- Section vertical rhythm: `space-y-6` to `space-y-8`.
- Card padding: `p-5` (compact), `p-6` (standard), `p-8` (hero).
- Card radius: `rounded-2xl` (containers), `rounded-xl` (inner widgets),
  `rounded-lg` (buttons / inputs).
- Shadows: `shadow-sm` for cards, `shadow-md` on hover, `shadow-lg` for
  hero / claim-slip surfaces.

## 3. Typography Scale

| Role                  | Class                                                |
| --------------------- | ---------------------------------------------------- |
| Page H1               | `text-2xl font-display font-bold text-slate-900`     |
| Section H3            | `font-display text-lg font-semibold text-slate-900`  |
| Card title            | `font-display font-semibold text-slate-900`          |
| Eyebrow / kicker      | `text-xs font-semibold uppercase tracking-widest text-slate-500` |
| Body                  | `text-sm text-slate-700`                             |
| Helper                | `text-xs text-slate-500`                             |
| KPI digit             | `text-3xl font-display font-bold text-slate-900`     |

## 4. Surfaces

All container surfaces follow:

```html
<div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">…</div>
```

Hero / call-out surfaces use a brand or status gradient:

```html
<div class="rounded-3xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg">…</div>
```

## 5. Status Tones

Every status across the system maps to one of these tones. Use the
`StatusBadge.vue` component when possible.

| Tone      | Used for                                    |
| --------- | ------------------------------------------- |
| `success` | approved, validated, completed, released    |
| `warning` | pending, missing, awaiting payment          |
| `danger`  | denied, overdue, rejected, voided           |
| `info`    | in_progress, processing                     |
| `neutral` | not_started, n/a, draft                     |
| `brand`   | active link / in-focus filter chips         |

## 6. Component Library

Located in `resources/js/Components/UI/`:

- `StatusBadge.vue` — chip with consistent tone.
- `EmptyState.vue` — empty-list placeholder with optional CTA slot.
- `Card.vue` — standard surface with header, eyebrow, actions slot.
- `StatCard.vue` — KPI tile with optional icon, tone, and CTA.

Existing project-wide components also include:

- `NotificationBell.vue` — global notifications dropdown.
- `Composables/useEchoPrivateChannel.js` and
  `Composables/useRealtimeOrPoll.js` — realtime UX baseline.

## 7. Interaction States

- **Focus visible**: All interactive elements should rely on
  `focus:ring-2 focus:ring-brand-500` (or contextual tone).
- **Hover (links / buttons)**: Slight elevation
  (`hover:-translate-y-0.5`) on cards, color darkening by one shade on
  buttons, soft background tint (`hover:bg-brand-50/40`) on table rows.
- **Active**: Use solid tone background with white text.
- **Disabled**: `disabled:opacity-60 disabled:cursor-not-allowed`.

## 8. Loading, Empty & Error

- Use skeleton placeholders for first paint:
  `animate-pulse rounded-2xl bg-slate-200/70`.
- Always render an `EmptyState` for empty lists. Avoid dead-blank
  table cells.
- For inline form errors, keep the message *next to* the input with
  `text-rose-600 text-xs`.
- For destructive feedback (denials, overdue), use a callout card with
  rose accent and an icon.

## 9. Accessibility

- Color is never the only signal — pair tone with text or icon (e.g.
  status badge text).
- Maintain WCAG AA contrast: brand-600 / white, slate-900 / white,
  amber-800 / amber-100.
- Use `aria-hidden="true"` for decorative icons, and provide
  `aria-label` on icon-only buttons.
- Forms: every `<input>` has a paired `<label>`; required fields use
  `required` attribute and explicit asterisk.
- Keyboard: dialogs and disclosure widgets close on `Esc`; focus
  returns to trigger.
- Reduced motion: avoid large kinetic animations; use
  `transition-colors` and `transition` on hover only.

## 10. Responsive Patterns

- Mobile-first: build single column, then layer `sm:`, `md:`, `lg:`
  breakpoints.
- Sidebar (staff): collapses behind a hamburger on `<lg`.
- Tables: horizontally scroll inside `overflow-x-auto` on small
  screens.
- Hero CTAs: stack vertically below `md`.
- Touch targets: minimum 40×40px.

## 11. Page Anatomy

Use this structure for every page:

```html
<Layout>
  <template #header>
    <h2 class="text-2xl font-display font-bold text-slate-900">Page title</h2>
    <p class="text-sm text-slate-500">One-sentence description.</p>
  </template>

  <div class="mx-auto max-w-7xl space-y-6 px-4 pb-12 sm:px-6 lg:px-8">
    <!-- 1) Hero / next-best-action (optional) -->
    <!-- 2) KPI strip -->
    <!-- 3) Primary work surface (queue / form / wizard) -->
    <!-- 4) Secondary panels (announcements, FAQ, history) -->
  </div>
</Layout>
```

## 12. Acceptance Criteria

A page is "design-system compliant" if:

1. It follows Section 11 page anatomy.
2. It uses tone-mapped statuses and the `StatusBadge` palette.
3. Every empty list renders an `EmptyState`.
4. Every interactive element has a focus state.
5. Every icon-only button has an `aria-label`.
6. Cards use `rounded-2xl bg-white shadow-sm ring-1 ring-slate-200`.
7. Color contrast passes WCAG AA in both `light` mode and printed
   form (claim slips, receipts).
