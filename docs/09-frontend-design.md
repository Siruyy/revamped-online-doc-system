# 09 — Frontend Design

## Design Philosophy

- **Clean, modern, professional** — appropriate for an academic institution.
- **Mobile-first** — students primarily access from phones.
- **Information dense but never cluttered** — admin views show a lot of data; pad with whitespace.
- **Accessibility** — WCAG AA color contrast, keyboard navigation, focus states.
- **Consistent** — same patterns repeat (status badges, action buttons, modals).

## Brand Identity

| Element | Value |
|---------|-------|
| Primary color | Deep navy `#1e3a5f` (SVCI institutional feel) |
| Accent color | Warm amber `#f59e0b` (calls to action, highlights) |
| Success | Emerald `#10b981` |
| Warning | Amber `#f59e0b` |
| Danger | Rose `#e11d48` |
| Info | Sky `#0ea5e9` |
| Background | Slate `#f8fafc` |
| Surface | White `#ffffff` |
| Text primary | Slate `#0f172a` |
| Text secondary | Slate `#64748b` |
| Border | Slate `#e2e8f0` |

## Tailwind Config Tokens

```js
// tailwind.config.js
theme: {
    extend: {
        colors: {
            brand: {
                50:  '#f0f5fa',
                100: '#dbe6f1',
                500: '#3b6ea3',
                600: '#2c5984',
                700: '#1e3a5f', // primary
                800: '#172d4a',
                900: '#0f1e33',
            },
            accent: {
                500: '#f59e0b',
                600: '#d97706',
            },
        },
        fontFamily: {
            sans: ['Inter', 'ui-sans-serif', 'system-ui'],
            display: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
        },
    },
}
```

## Typography

- **Body:** Inter (Google Fonts), 14–16px base.
- **Headings:** Plus Jakarta Sans, semibold/bold.
- **Monospace** (reference numbers, IDs): JetBrains Mono.

## Layout Patterns

### Public / Auth Layout (`Layouts/GuestLayout.vue`)

- Centered card on a soft gradient background.
- SVCI logo top-center.
- Width: max-w-md.

### Student Layout (`Layouts/StudentLayout.vue`)

- **Top navbar** (sticky): logo, dashboard, requests, payments, clearance, FAQ, notification bell, message bell, avatar dropdown.
- **Mobile:** hamburger menu collapses nav into a slide-over drawer.
- Main content max-w-7xl, padding y-8.

### Admin / Department / SuperAdmin Layout (`Layouts/StaffLayout.vue`)

- **Sidebar** (collapsible on desktop, drawer on mobile):
    - Dashboard
    - Section-specific items
    - Settings
    - Profile
- **Top bar:** breadcrumbs, search, notification bell, message bell, user dropdown.
- Main content uses full width with internal max-w-7xl on individual pages.

## Reusable Vue Components

| Component | Purpose |
|-----------|---------|
| `<AppLayout>` | Wraps content with appropriate role layout |
| `<PageHeader>` | Title + subtitle + actions slot |
| `<Card>` | Surface container with optional title and footer |
| `<DataTable>` | Sortable, searchable, paginated table |
| `<StatusBadge>` | Colored pill for statuses (pending/approved/denied/etc.) |
| `<StatCard>` | Big number with icon, label, optional trend |
| `<EmptyState>` | Friendly illustration + message when list is empty |
| `<Modal>` | Headless UI dialog wrapper |
| `<ConfirmDialog>` | "Are you sure?" modal with destructive button styling |
| `<FormField>` | Label + input + error message |
| `<FileUpload>` | Drag-and-drop with preview |
| `<NotificationBell>` | Dropdown with recent notifications, real-time updates |
| `<MessageBell>` | Same for unread messages |
| `<UserAvatar>` | Avatar image or initials fallback |
| `<Timeline>` | Vertical timeline for request progress |
| `<Pagination>` | Server-side pagination control |
| `<FilterBar>` | Search + dropdowns for list filtering |
| `<Skeleton>` | Loading placeholder |
| `<Toast>` | Flash messages (success/error) via composable |
| `<Tabs>` | Headless UI tabs |
| `<Dropdown>` | Headless UI menu |

## Component Examples

### `<StatusBadge>`

```vue
<script setup>
const props = defineProps({ status: String });

const variants = {
    pending: 'bg-amber-100 text-amber-700 ring-amber-600/20',
    approved: 'bg-emerald-100 text-emerald-700 ring-emerald-600/20',
    denied: 'bg-rose-100 text-rose-700 ring-rose-600/20',
    completed: 'bg-sky-100 text-sky-700 ring-sky-600/20',
    cancelled: 'bg-slate-100 text-slate-600 ring-slate-500/20',
};

const classes = computed(() => variants[props.status] ?? variants.pending);
</script>

<template>
    <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset capitalize', classes]">
        {{ status }}
    </span>
</template>
```

## Page Structure Template

Every Inertia page follows this skeleton:

```vue
<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineOptions({ layout: StaffLayout });

const props = defineProps({ /* ... */ });
</script>

<template>
    <PageHeader title="Page Title" subtitle="Description">
        <template #actions>
            <!-- buttons -->
        </template>
    </PageHeader>

    <div class="mt-6 space-y-6">
        <!-- content -->
    </div>
</template>
```

## Loading & Empty States

- Use **skeleton loaders** for initial data, never spinners.
- Use **`<EmptyState>`** with friendly copy and a primary action when lists are empty.
- Inertia's progress bar shows for navigations (NProgress style).

## Forms

- Use Inertia's `useForm()` helper.
- Error messages shown inline beneath fields.
- Submit buttons disabled while `processing`.
- Server validation errors automatically populated.

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    documents: [],
    purpose: '',
});

const submit = () => {
    form.post(route('student.requests.store'));
};
</script>
```

## Toasts / Flash Messages

Server flashes `success` or `error` via `Inertia::share()`. A root `<Toast>` component watches for changes and shows them.

## Icons

- **Heroicons (Vue 3 outline + solid)** for UI icons.
- Avoid icon-font libraries.

## Animations

- Use Tailwind's `transition-*` utilities for hover/focus.
- Use **Headless UI's transition components** for modals, dropdowns, drawers.
- No heavy animation libraries.

## Responsiveness Breakpoints

Tailwind defaults:
- `sm` 640px — phone landscape
- `md` 768px — tablet
- `lg` 1024px — small laptop
- `xl` 1280px — desktop

Test on iPhone SE (375px), Pixel 5 (393px), iPad (768px), Desktop (1440px).

## Dark Mode

Out of scope for v1, but use Tailwind's `dark:` prefix where natural so it's easy to enable later.
