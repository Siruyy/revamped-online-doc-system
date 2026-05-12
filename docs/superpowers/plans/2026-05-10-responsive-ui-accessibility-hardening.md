# Responsive UI Accessibility Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the SVCI Laravel/Inertia/Vue UI mobile-safe, accessible, and visually consistent across public, auth, student, admin, department, and superadmin flows.

**Architecture:** Add regression coverage first, then harden shared layout/component primitives, then migrate high-risk pages in focused batches. Mobile data views use compact stacked cards for user-facing lists and horizontal scroll only for log/report-style data where card conversion would reduce scanability.

**Tech Stack:** Laravel 13, Inertia.js, Vue 3, Tailwind CSS, Playwright, Heroicons.

---

## Confirmed Product Decisions

- Mobile tables: use compact stacked cards for user-facing lists; use horizontal scroll only for admin logs/report-style data.
- Auth pages: use a simple branded card that matches the landing page; do not build a heavy split-screen marketing layout.
- Staff messages: keep staff notification utilities as-is unless Phase 08 messaging explicitly requires staff messaging; do not add `MessageBell` to staff by default.

## Acceptance Criteria

- No horizontal page overflow at `375x812`, `390x844`, `430x932`, `768x1024`, `1024x768`, and desktop widths.
- All icon-only buttons have useful `aria-label` text.
- All primary touch targets are at least `44x44px` or have equivalent hit area.
- All form controls have associated labels via `for` and `id`.
- Validation errors are announced and associated with invalid fields using `aria-invalid` and `aria-describedby`.
- File upload controls are keyboard reachable and announce selected files.
- Role pages use current role layouts; no production role page falls back to legacy Breeze-era `AuthenticatedLayout` unless intentionally retained for compatibility.
- User-facing data tables have compact mobile cards and desktop tables.
- Log/report-style tables have safe horizontal scrolling with visible affordance.
- `npm run lint`, `npm run build`, focused Playwright UI checks, and relevant Laravel tests pass before completion.

## Files And Responsibilities

### New Shared UI Files

- `resources/js/Components/UI/PageHeader.vue` — consistent page title, subtitle, badge, and action layout.
- `resources/js/Components/UI/DataTableShell.vue` — accessible desktop table wrapper with optional horizontal scroll.
- `resources/js/Components/UI/ResponsiveRecordList.vue` — paired mobile-card and desktop-table shell for index pages.
- `resources/js/Components/UI/FormField.vue` — label/help/error wrapper that centralizes `aria-describedby` and `aria-invalid` patterns.
- `resources/js/Components/UI/IconButton.vue` — icon-only button/link wrapper with required accessible label and `44x44px` hit area.
- `tests/Browser/ui-responsive.spec.ts` — Playwright regression checks for responsive overflow, mobile navigation, and touch target guardrails.
- `tests/Browser/helpers/auth.ts` — reusable test login helpers for seeded E2E users.

### Existing Shared UI Files To Modify

- `resources/js/Layouts/StudentLayout.vue` — mobile menu semantics, touch targets, safe content spacing.
- `resources/js/Layouts/StaffLayout.vue` — drawer/sidebar semantics, `dvh` sizing, safe areas, touch targets.
- `resources/js/Layouts/GuestLayout.vue` — branded auth shell aligned with `Welcome.vue`.
- `resources/js/Components/Modal.vue` — mobile-safe viewport sizing and accessible title/description hooks.
- `resources/js/Components/InputError.vue` — alert/live-region semantics.
- `resources/js/Components/TextInput.vue` — invalid/described-by props and focus ring consistency.
- `resources/js/Components/PrimaryButton.vue` — focus, disabled, and loading-friendly styling.
- `resources/js/Components/SecondaryButton.vue` — focus and disabled styling.
- `resources/js/Components/DangerButton.vue` — focus and destructive-state consistency.
- `resources/js/Components/FileUpload.vue` — keyboard-reachable upload primitive if current page-local upload controls are consolidated.
- `resources/js/Components/NotificationBell.vue` — accessible label and minimum touch target.
- `resources/js/Components/MessageBell.vue` — accessible label and minimum touch target.
- `resources/js/Components/UserAvatar.vue` — accessible trigger semantics where used as menu trigger.

### High-Risk Pages To Migrate First

- `resources/js/Pages/Student/Requests/Index.vue`
- `resources/js/Pages/Admin/Requests/Index.vue`
- `resources/js/Pages/Admin/Clearances/Index.vue`
- `resources/js/Pages/Department/Clearances/Index.vue`
- `resources/js/Pages/SuperAdmin/Users/Index.vue`
- `resources/js/Pages/SuperAdmin/Users/Pending.vue`
- `resources/js/Pages/SuperAdmin/Logs/Index.vue`
- `resources/js/Pages/Admin/Dashboard.vue`

### Form/Upload Pages To Migrate

- `resources/js/Pages/Auth/Login.vue`
- `resources/js/Pages/Auth/Register.vue`
- `resources/js/Pages/Auth/ForgotPassword.vue`
- `resources/js/Pages/Auth/ResetPassword.vue`
- `resources/js/Pages/Auth/ConfirmPassword.vue`
- `resources/js/Pages/Auth/VerifyEmail.vue`
- `resources/js/Pages/Auth/RegistrationPending.vue`
- `resources/js/Pages/Student/Requests/Create.vue`
- `resources/js/Pages/Student/Requests/Show.vue`
- `resources/js/Pages/Student/Payments/Index.vue`
- `resources/js/Pages/Admin/Payments/Index.vue`
- `resources/js/Pages/Admin/Settings/PaymentProfile.vue`
- `resources/js/Pages/Admin/DocumentTypes/Index.vue`
- `resources/js/Pages/Admin/Releases/Index.vue`
- `resources/js/Pages/SuperAdmin/Users/Create.vue`
- `resources/js/Pages/SuperAdmin/Users/Edit.vue`

---

### Task 1: Add Responsive UI Regression Tests

**Files:**
- Create: `tests/Browser/helpers/auth.ts`
- Create: `tests/Browser/ui-responsive.spec.ts`
- Modify only if needed: `playwright.config.ts`

- [ ] **Step 1: Create auth helper**

Create `tests/Browser/helpers/auth.ts`:

```ts
import type { Page } from '@playwright/test';

export type RoleName = 'student' | 'admin' | 'department' | 'superadmin';

const accounts: Record<RoleName, { email: string; password: string }> = {
    student: { email: 'e2e.student@example.com', password: 'password' },
    admin: { email: 'e2e.admin@example.com', password: 'password' },
    department: { email: 'e2e.teacher@example.com', password: 'password' },
    superadmin: { email: 'e2e.superadmin@example.com', password: 'password' },
};

export async function loginAs(page: Page, role: RoleName): Promise<void> {
    const account = accounts[role];

    await page.goto('/login');
    await page.getByLabel('Email').fill(account.email);
    await page.getByLabel('Password').fill(account.password);
    await page.getByRole('button', { name: /log in/i }).click();
    await page.waitForLoadState('networkidle');
}
```

- [ ] **Step 2: Add responsive audit spec**

Create `tests/Browser/ui-responsive.spec.ts`:

```ts
import { expect, test, type Page } from '@playwright/test';
import { loginAs, type RoleName } from './helpers/auth';

const viewports = [
    { name: 'small-phone', width: 375, height: 812 },
    { name: 'large-phone', width: 430, height: 932 },
    { name: 'tablet', width: 768, height: 1024 },
];

const publicRoutes = ['/', '/login', '/register'];

const roleRoutes: Record<RoleName, string[]> = {
    student: ['/student/dashboard', '/student/requests', '/student/payments', '/student/clearance', '/student/faq'],
    admin: [
        '/admin/dashboard',
        '/admin/requests',
        '/admin/payments',
        '/admin/clearances',
        '/admin/document-types',
        '/admin/settings/payment-profile',
    ],
    department: ['/department/dashboard', '/department/clearances', '/department/faq'],
    superadmin: ['/superadmin/dashboard', '/superadmin/users', '/superadmin/users/pending', '/superadmin/logs', '/superadmin/reports'],
};

async function expectNoHorizontalOverflow(page: Page): Promise<void> {
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    expect(overflow).toBeLessThanOrEqual(1);
}

async function expectPrimaryTouchTargets(page: Page): Promise<void> {
    const offenders = await page.evaluate(() => {
        const interactive = Array.from(document.querySelectorAll('button, a, input, select, textarea'));

        return interactive
            .map((element) => {
                const rect = element.getBoundingClientRect();
                const text = (element.textContent || element.getAttribute('aria-label') || element.getAttribute('name') || '').trim();

                return {
                    tag: element.tagName.toLowerCase(),
                    text,
                    width: Math.round(rect.width),
                    height: Math.round(rect.height),
                    visible: rect.width > 0 && rect.height > 0,
                    inlineTextLink: element.tagName.toLowerCase() === 'a' && rect.height < 30 && text.length > 12,
                };
            })
            .filter((item) => item.visible)
            .filter((item) => !item.inlineTextLink)
            .filter((item) => item.width < 44 || item.height < 44)
            .slice(0, 10);
    });

    expect(offenders).toEqual([]);
}

for (const viewport of viewports) {
    test.describe(`responsive public ${viewport.name}`, () => {
        test.use({ viewport: { width: viewport.width, height: viewport.height } });

        for (const route of publicRoutes) {
            test(`${route} has no horizontal overflow`, async ({ page }) => {
                await page.goto(route);
                await page.waitForLoadState('networkidle');
                await expectNoHorizontalOverflow(page);
            });
        }
    });

    for (const [role, routes] of Object.entries(roleRoutes) as [RoleName, string[]][]) {
        test.describe(`responsive ${role} ${viewport.name}`, () => {
            test.use({ viewport: { width: viewport.width, height: viewport.height } });

            test.beforeEach(async ({ page }) => {
                await loginAs(page, role);
            });

            for (const route of routes) {
                test(`${route} has no horizontal overflow`, async ({ page }) => {
                    await page.goto(route);
                    await page.waitForLoadState('networkidle');
                    await expectNoHorizontalOverflow(page);
                });
            }
        });
    }
}

test.describe('mobile interaction guardrails', () => {
    test.use({ viewport: { width: 375, height: 812 } });

    test('student navigation exposes accessible mobile controls', async ({ page }) => {
        await loginAs(page, 'student');
        await page.goto('/student/dashboard');

        await page.getByRole('button', { name: /navigation|menu/i }).click();
        await expect(page.getByRole('link', { name: /my requests/i })).toBeVisible();
    });

    test('admin navigation exposes accessible mobile controls', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/admin/dashboard');

        await page.getByRole('button', { name: /navigation|menu/i }).click();
        await expect(page.getByRole('link', { name: /requests/i })).toBeVisible();
    });

    test('student dashboard primary controls meet touch target minimums', async ({ page }) => {
        await loginAs(page, 'student');
        await page.goto('/student/dashboard');
        await expectPrimaryTouchTargets(page);
    });
});
```

- [ ] **Step 3: Run tests to verify failures expose current issues**

Run:

```bash
E2E_REFRESH_DB=1 npm run test:e2e -- ui-responsive.spec.ts
```

Expected: tests may fail on touch targets, mobile menu accessible names, and/or horizontal overflow. Preserve failures as evidence for following tasks.

- [ ] **Step 4: Commit tests**

```bash
git add tests/Browser/helpers/auth.ts tests/Browser/ui-responsive.spec.ts playwright.config.ts
git commit -m "test: add responsive UI audit coverage"
```

---

### Task 2: Harden Shared Layouts And Modal

**Files:**
- Modify: `resources/js/Layouts/StudentLayout.vue`
- Modify: `resources/js/Layouts/StaffLayout.vue`
- Modify: `resources/js/Components/Modal.vue`
- Modify: `resources/js/Pages/Profile/Edit.vue`

- [ ] **Step 1: Update student mobile menu semantics and touch targets**

In `StudentLayout.vue`, update the mobile menu button pattern to include accessible state and a larger hit area:

```vue
<button
    type="button"
    class="md:hidden inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
    :aria-label="showMobileMenu ? 'Close navigation menu' : 'Open navigation menu'"
    :aria-expanded="showMobileMenu"
    aria-controls="student-mobile-navigation"
    @click="showMobileMenu = !showMobileMenu"
>
    <Bars3Icon v-if="!showMobileMenu" class="h-5 w-5" aria-hidden="true" />
    <XMarkIcon v-else class="h-5 w-5" aria-hidden="true" />
</button>
```

Add `id="student-mobile-navigation"` to the mobile menu container.

- [ ] **Step 2: Update staff drawer semantics and touch targets**

In `StaffLayout.vue`, update the mobile hamburger:

```vue
<button
    type="button"
    class="inline-flex min-h-11 min-w-11 -ml-2 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
    :aria-label="showSidebar ? 'Close navigation menu' : 'Open navigation menu'"
    :aria-expanded="showSidebar"
    aria-controls="staff-mobile-navigation"
    @click="showSidebar = !showSidebar"
>
    <Bars3Icon class="h-5 w-5" aria-hidden="true" />
</button>
```

Set desktop sidebar height to `h-dvh` and mobile drawer to `max-h-dvh`/`min-h-dvh`:

```vue
<aside
    class="hidden lg:flex lg:flex-col bg-slate-900 text-slate-300 shrink-0 shadow-xl fixed h-dvh z-50 transition-all duration-300"
    :class="sidebarCollapsed ? 'w-16' : 'w-64'"
>
```

Add `id="staff-mobile-navigation"` to the mobile drawer `<aside>`.

- [ ] **Step 3: Make modal mobile-safe**

In `Modal.vue`, change the dialog/wrapper classes to use `dvh` and safe scrolling:

```vue
<dialog ref="dialog" class="z-50 m-0 min-h-dvh min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent">
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0" scroll-region>
        <div
            v-show="show"
            class="mb-6 max-h-[calc(100dvh-3rem)] transform overflow-y-auto rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5 transition-all sm:mx-auto sm:w-full"
            :class="maxWidthClass"
        >
            <slot v-if="showSlot" />
        </div>
    </div>
</dialog>
```

- [ ] **Step 4: Route profile pages through role layouts**

In `Profile/Edit.vue`, replace legacy layout branching with explicit role mapping:

```js
import StudentLayout from '@/Layouts/StudentLayout.vue';
import StaffLayout from '@/Layouts/StaffLayout.vue';

const page = usePage();
const role = computed(() => page.props.auth?.user?.role ?? 'student');
const layoutComponent = computed(() => (role.value === 'student' ? StudentLayout : StaffLayout));
```

Wrap the template root with:

```vue
<component :is="layoutComponent">
    <template #header>
        <h2 class="text-2xl font-display font-bold text-slate-900">Profile</h2>
        <p class="mt-1 text-sm text-slate-600">Manage your account details, avatar, password, and signature.</p>
    </template>

    <!-- existing profile content -->
</component>
```

- [ ] **Step 5: Verify layout fixes**

Run:

```bash
npm run lint
E2E_REFRESH_DB=1 npm run test:e2e -- ui-responsive.spec.ts
```

Expected: mobile navigation accessible-name failures should be reduced or resolved.

- [ ] **Step 6: Commit layout fixes**

```bash
git add resources/js/Layouts/StudentLayout.vue resources/js/Layouts/StaffLayout.vue resources/js/Components/Modal.vue resources/js/Pages/Profile/Edit.vue
git commit -m "fix: harden responsive app layouts"
```

---

### Task 3: Add Shared UI Primitives

**Files:**
- Create: `resources/js/Components/UI/IconButton.vue`
- Create: `resources/js/Components/UI/PageHeader.vue`
- Create: `resources/js/Components/UI/DataTableShell.vue`
- Create: `resources/js/Components/UI/ResponsiveRecordList.vue`
- Create: `resources/js/Components/UI/FormField.vue`
- Modify: `resources/js/Components/InputError.vue`
- Modify: `resources/js/Components/TextInput.vue`

- [ ] **Step 1: Add IconButton**

Create `resources/js/Components/UI/IconButton.vue`:

```vue
<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    as: { type: String, default: 'button' },
    href: { type: String, default: null },
    label: { type: String, required: true },
    variant: { type: String, default: 'subtle' },
    type: { type: String, default: 'button' },
});

const variantClasses = {
    subtle: 'text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-brand-600',
    primary: 'bg-brand-600 text-white hover:bg-brand-500 focus-visible:outline-brand-600',
    danger: 'text-rose-700 hover:bg-rose-50 focus-visible:outline-rose-600',
};
</script>

<template>
    <Link
        v-if="href"
        :href="href"
        :aria-label="label"
        :class="[
            'inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
            variantClasses[variant] ?? variantClasses.subtle,
        ]"
    >
        <slot />
    </Link>

    <button
        v-else
        :type="type"
        :aria-label="label"
        :class="[
            'inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
            variantClasses[variant] ?? variantClasses.subtle,
        ]"
    >
        <slot />
    </button>
</template>
```

- [ ] **Step 2: Add PageHeader**

Create `resources/js/Components/UI/PageHeader.vue`:

```vue
<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
});
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <p v-if="eyebrow" class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">{{ eyebrow }}</p>
            <h1 class="text-2xl font-display font-bold tracking-tight text-slate-950 sm:text-3xl">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">{{ subtitle }}</p>
        </div>

        <div v-if="$slots.actions" class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <slot name="actions" />
        </div>
    </div>
</template>
```

- [ ] **Step 3: Add DataTableShell**

Create `resources/js/Components/UI/DataTableShell.vue`:

```vue
<script setup>
defineProps({
    minWidth: { type: String, default: 'min-w-[48rem]' },
    label: { type: String, default: 'Data table' },
});
</script>

<template>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto" tabindex="0" :aria-label="`${label}. Scroll horizontally if needed.`">
            <div :class="minWidth">
                <slot />
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 4: Add ResponsiveRecordList**

Create `resources/js/Components/UI/ResponsiveRecordList.vue`:

```vue
<script setup>
defineProps({
    empty: { type: Boolean, default: false },
});
</script>

<template>
    <div v-if="empty">
        <slot name="empty" />
    </div>

    <div v-else>
        <div class="space-y-3 md:hidden">
            <slot name="cards" />
        </div>

        <div class="hidden md:block">
            <slot name="table" />
        </div>
    </div>
</template>
```

- [ ] **Step 5: Add FormField**

Create `resources/js/Components/UI/FormField.vue`:

```vue
<script setup>
import { computed } from 'vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    error: { type: String, default: '' },
    help: { type: String, default: '' },
    required: { type: Boolean, default: false },
});

const helpId = computed(() => (props.help ? `${props.id}-help` : undefined));
const errorId = computed(() => (props.error ? `${props.id}-error` : undefined));
const describedBy = computed(() => [helpId.value, errorId.value].filter(Boolean).join(' ') || undefined);
</script>

<template>
    <div>
        <label :for="id" class="block text-sm font-medium text-slate-700">
            {{ label }}
            <span v-if="required" class="text-rose-600" aria-hidden="true">*</span>
        </label>

        <div class="mt-1">
            <slot :id="id" :described-by="describedBy" :invalid="Boolean(error)" />
        </div>

        <p v-if="help" :id="helpId" class="mt-1 text-xs leading-5 text-slate-500">{{ help }}</p>
        <InputError :id="errorId" class="mt-1" :message="error" />
    </div>
</template>
```

- [ ] **Step 6: Update InputError**

In `InputError.vue`, ensure the rendered error has an ID and live-region semantics:

```vue
<template>
    <p v-show="message" :id="id" class="text-sm text-rose-600" role="alert" aria-live="polite">
        {{ message }}
    </p>
</template>
```

Add prop:

```js
defineProps({
    message: { type: String, default: '' },
    id: { type: String, default: undefined },
});
```

- [ ] **Step 7: Update TextInput**

In `TextInput.vue`, add `invalid` and `describedBy` props and bind ARIA:

```vue
<input
    :id="id"
    ref="input"
    :value="modelValue"
    :aria-invalid="invalid ? 'true' : undefined"
    :aria-describedby="describedBy"
    class="rounded-lg border-slate-300 shadow-sm transition focus:border-brand-500 focus:ring-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500"
    @input="$emit('update:modelValue', $event.target.value)"
/>
```

- [ ] **Step 8: Verify shared primitives**

Run:

```bash
npm run lint
npm run build
```

Expected: Vue compiles and lint passes.

- [ ] **Step 9: Commit shared primitives**

```bash
git add resources/js/Components/UI resources/js/Components/InputError.vue resources/js/Components/TextInput.vue
git commit -m "feat: add shared responsive UI primitives"
```

---

### Task 4: Convert User-Facing Tables To Compact Mobile Cards

**Files:**
- Modify: `resources/js/Pages/Student/Requests/Index.vue`
- Modify: `resources/js/Pages/Admin/Requests/Index.vue`
- Modify: `resources/js/Pages/Admin/Clearances/Index.vue`
- Modify: `resources/js/Pages/Department/Clearances/Index.vue`
- Modify: `resources/js/Pages/SuperAdmin/Users/Index.vue`
- Modify: `resources/js/Pages/SuperAdmin/Users/Pending.vue`

- [ ] **Step 1: Use this compact card pattern for each index record**

Apply this structure with each page's actual fields:

```vue
<article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="truncate text-sm font-semibold text-slate-950">{{ primaryTitle }}</h3>
            <p class="mt-0.5 truncate text-xs text-slate-500">{{ secondaryLine }}</p>
        </div>
        <StatusBadge :status="status" />
    </div>

    <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
        <div>
            <dt class="font-medium text-slate-500">Submitted</dt>
            <dd class="mt-0.5 text-slate-800">{{ submittedAt }}</dd>
        </div>
        <div>
            <dt class="font-medium text-slate-500">Amount</dt>
            <dd class="mt-0.5 text-slate-800">{{ amount }}</dd>
        </div>
    </dl>

    <div class="mt-4 flex flex-wrap gap-2">
        <Link class="inline-flex min-h-10 flex-1 items-center justify-center rounded-lg bg-brand-600 px-3 text-sm font-semibold text-white hover:bg-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600" :href="detailsUrl">
            View details
        </Link>
    </div>
</article>
```

- [ ] **Step 2: Keep desktop tables wrapped in DataTableShell**

Use:

```vue
<ResponsiveRecordList :empty="records.length === 0">
    <template #empty>
        <EmptyState title="No records found" description="Adjust your filters or check again later." />
    </template>

    <template #cards>
        <!-- compact mobile cards -->
    </template>

    <template #table>
        <DataTableShell label="Requests table" min-width="min-w-[56rem]">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <!-- existing desktop table -->
            </table>
        </DataTableShell>
    </template>
</ResponsiveRecordList>
```

- [ ] **Step 3: Page-specific mobile card content**

Use these content priorities:

- Student requests: document type, request reference/status, submitted date, payment/release state, view action.
- Admin requests: student, document type, status/SLA, submitted date, payment verified state, review action.
- Admin clearances: student, document request, clearance stage/status, updated date, view action.
- Department clearances: student, course/year, request type, current department status, sign/review action.
- SuperAdmin users: full name/email, role/status, student ID or staff role, approve/edit action.
- Pending users: full name/email, course/year/student ID, registration date, approve/reject action.

- [ ] **Step 4: Verify mobile list behavior**

Run:

```bash
E2E_REFRESH_DB=1 npm run test:e2e -- ui-responsive.spec.ts
npm run lint
```

Expected: horizontal overflow failures on user-facing list pages should be resolved.

- [ ] **Step 5: Commit mobile card conversion**

```bash
git add resources/js/Pages/Student/Requests/Index.vue resources/js/Pages/Admin/Requests/Index.vue resources/js/Pages/Admin/Clearances/Index.vue resources/js/Pages/Department/Clearances/Index.vue resources/js/Pages/SuperAdmin/Users/Index.vue resources/js/Pages/SuperAdmin/Users/Pending.vue
git commit -m "fix: make data lists mobile responsive"
```

---

### Task 5: Make Logs And Report Tables Scroll-Safe

**Files:**
- Modify: `resources/js/Pages/SuperAdmin/Logs/Index.vue`
- Modify: `resources/js/Pages/SuperAdmin/Reports/Index.vue`
- Modify: `resources/js/Pages/Admin/Dashboard.vue`

- [ ] **Step 1: Wrap log/report tables with DataTableShell**

Use horizontal scroll instead of cards for logs/reports:

```vue
<DataTableShell label="Activity logs table" min-width="min-w-[64rem]">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <!-- existing table content -->
    </table>
</DataTableShell>
```

- [ ] **Step 2: Add compact visible scroll hint on mobile**

Place above the shell:

```vue
<p class="text-xs text-slate-500 md:hidden">Swipe horizontally to view all columns.</p>
```

- [ ] **Step 3: Verify log/report pages**

Run:

```bash
E2E_REFRESH_DB=1 npm run test:e2e -- ui-responsive.spec.ts
npm run lint
```

- [ ] **Step 4: Commit scroll-safe report/log tables**

```bash
git add resources/js/Pages/SuperAdmin/Logs/Index.vue resources/js/Pages/SuperAdmin/Reports/Index.vue resources/js/Pages/Admin/Dashboard.vue
git commit -m "fix: make report tables mobile safe"
```

---

### Task 6: Fix Forms, Errors, And Uploads

**Files:**
- Modify: all files listed under "Form/Upload Pages To Migrate"
- Modify: `resources/js/Components/FileUpload.vue` if consolidating upload behavior

- [ ] **Step 1: Migrate simple fields to FormField**

Replace raw label/input/error triples with:

```vue
<FormField id="email" label="Email" :error="form.errors.email" required>
    <template #default="{ id, describedBy, invalid }">
        <TextInput
            :id="id"
            v-model="form.email"
            type="email"
            autocomplete="email"
            :described-by="describedBy"
            :invalid="invalid"
            required
            class="block w-full"
        />
    </template>
</FormField>
```

- [ ] **Step 2: Use stable IDs in repeated rows**

For rows tied to a model, use the model ID:

```vue
<FormField
    :id="`copies-${documentType.id}`"
    :label="`Copies for ${documentType.name}`"
    :error="form.errors[`items.${documentType.id}.copies`]"
>
    <template #default="{ id, describedBy, invalid }">
        <input
            :id="id"
            v-model.number="form.items[documentType.id].copies"
            type="number"
            min="1"
            :aria-describedby="describedBy"
            :aria-invalid="invalid ? 'true' : undefined"
            class="block w-full rounded-lg border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500"
        />
    </template>
</FormField>
```

- [ ] **Step 3: Fix icon-only steppers and action buttons**

For copy steppers in `Student/Requests/Create.vue`, use labels that include the document name:

```vue
<IconButton :label="`Decrease copies for ${documentType.name}`" @click="decreaseCopies(documentType.id)">
    <MinusIcon class="h-4 w-4" aria-hidden="true" />
</IconButton>

<IconButton :label="`Increase copies for ${documentType.name}`" @click="increaseCopies(documentType.id)">
    <PlusIcon class="h-4 w-4" aria-hidden="true" />
</IconButton>
```

- [ ] **Step 4: Fix upload controls**

Use a real focusable file button pattern:

```vue
<FormField id="receipt" label="Payment receipt" :error="form.errors.receipt" help="Upload a JPG, PNG, or PDF receipt up to the server limit." required>
    <template #default="{ id, describedBy, invalid }">
        <input
            :id="id"
            type="file"
            accept="image/*,.pdf"
            :aria-describedby="describedBy"
            :aria-invalid="invalid ? 'true' : undefined"
            class="block w-full rounded-lg border border-slate-300 text-sm text-slate-700 file:mr-4 file:min-h-11 file:border-0 file:bg-brand-600 file:px-4 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
            @change="form.receipt = $event.target.files[0]"
        />
    </template>
</FormField>
```

Remove copy that says "drag and drop" unless actual `dragover`/`drop` handlers are implemented.

- [ ] **Step 5: Add processing labels and aria-busy**

For submit buttons:

```vue
<PrimaryButton :disabled="form.processing" :aria-busy="form.processing ? 'true' : undefined">
    {{ form.processing ? 'Submitting...' : 'Submit request' }}
</PrimaryButton>
```

- [ ] **Step 6: Verify form accessibility**

Run:

```bash
npm run lint
E2E_REFRESH_DB=1 npm run test:e2e -- ui-responsive.spec.ts
```

Manually keyboard-test:

- `/login`
- `/register`
- `/student/requests/create`
- `/student/payments`
- `/admin/settings/payment-profile`

- [ ] **Step 7: Commit forms/uploads**

```bash
git add resources/js/Pages/Auth resources/js/Pages/Student/Requests/Create.vue resources/js/Pages/Student/Requests/Show.vue resources/js/Pages/Student/Payments/Index.vue resources/js/Pages/Admin/Payments/Index.vue resources/js/Pages/Admin/Settings/PaymentProfile.vue resources/js/Pages/Admin/DocumentTypes/Index.vue resources/js/Pages/Admin/Releases/Index.vue resources/js/Pages/SuperAdmin/Users/Create.vue resources/js/Pages/SuperAdmin/Users/Edit.vue resources/js/Components/FileUpload.vue
git commit -m "fix: improve form accessibility and upload UX"
```

---

### Task 7: Standardize Page Headers, Containers, And Visual Primitives

**Files:**
- Modify priority pages under `resources/js/Pages/Admin`, `resources/js/Pages/Student`, `resources/js/Pages/SuperAdmin`, `resources/js/Pages/Department`, and `resources/js/Pages/Notifications/Index.vue`

- [ ] **Step 1: Replace page-local headers with PageHeader**

Use this structure:

```vue
<template #header>
    <PageHeader
        title="Requests"
        subtitle="Review document requests, payment readiness, and fulfillment progress."
    >
        <template #actions>
            <Link class="inline-flex min-h-11 items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600" :href="route('student.requests.create')">
                New request
            </Link>
        </template>
    </PageHeader>
</template>
```

- [ ] **Step 2: Remove duplicate outer containers**

When a page currently starts with:

```vue
<div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
```

replace it with:

```vue
<div class="space-y-6">
```

because `StudentLayout.vue` and `StaffLayout.vue` already provide the page container.

- [ ] **Step 3: Use existing Card/StatCard/StatusBadge consistently**

Replace ad-hoc status pills with:

```vue
<StatusBadge :status="request.status" />
```

Use `Card.vue` for ordinary white surfaces unless the page needs a distinct highlighted callout.

- [ ] **Step 4: Verify visual consistency**

Run:

```bash
npm run lint
npm run build
E2E_REFRESH_DB=1 npm run test:e2e -- ui-responsive.spec.ts
```

- [ ] **Step 5: Commit visual standardization**

```bash
git add resources/js/Pages resources/js/Components/UI/PageHeader.vue
git commit -m "refactor: standardize page headers and spacing"
```

---

### Task 8: Refresh Auth Pages With Simple Branded Card

**Files:**
- Modify: `resources/js/Layouts/GuestLayout.vue`
- Modify: `resources/js/Pages/Auth/Login.vue`
- Modify: `resources/js/Pages/Auth/Register.vue`
- Modify: `resources/js/Pages/Auth/ForgotPassword.vue`
- Modify: `resources/js/Pages/Auth/ResetPassword.vue`
- Modify: `resources/js/Pages/Auth/ConfirmPassword.vue`
- Modify: `resources/js/Pages/Auth/VerifyEmail.vue`
- Modify: `resources/js/Pages/Auth/RegistrationPending.vue`

- [ ] **Step 1: Update GuestLayout to match landing style**

Use a simple branded shell:

```vue
<template>
    <div class="flex min-h-dvh flex-col bg-gradient-to-br from-slate-50 via-white to-brand-50/60 px-4 py-8 text-slate-900 sm:px-6 lg:px-8">
        <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
            <div class="mb-8 text-center">
                <Link href="/" class="inline-flex items-center justify-center gap-3">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-sm">
                        <ApplicationLogo class="h-7 w-7" />
                    </span>
                    <span class="font-display text-xl font-bold tracking-tight text-slate-950">SVCI Docs</span>
                </Link>
                <p class="mt-3 text-sm text-slate-600">Official SVCI document request portal</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 2: Add clear auth page headings**

Each auth page should start with:

```vue
<div class="mb-6">
    <h1 class="font-display text-2xl font-bold text-slate-950">Log in</h1>
    <p class="mt-1 text-sm text-slate-600">Use your approved SVCI account to continue.</p>
</div>
```

Use page-specific title/subtitle text.

- [ ] **Step 3: Verify auth pages**

Run:

```bash
npm run lint
npm run build
E2E_REFRESH_DB=1 npm run test:e2e -- ui-responsive.spec.ts
```

- [ ] **Step 4: Commit auth refresh**

```bash
git add resources/js/Layouts/GuestLayout.vue resources/js/Pages/Auth
git commit -m "feat: align auth screens with brand design"
```

---

### Task 9: Standardize Notification And Utility Controls

**Files:**
- Modify: `resources/js/Components/NotificationBell.vue`
- Modify: `resources/js/Components/MessageBell.vue`
- Modify: `resources/js/Components/UserAvatar.vue`
- Modify: `resources/js/Layouts/StudentLayout.vue`
- Modify: `resources/js/Layouts/StaffLayout.vue`

- [ ] **Step 1: Give bells accessible labels and 44px targets**

Use this button class pattern:

```vue
<button
    type="button"
    class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
    aria-label="Open notifications"
>
    <BellIcon class="h-5 w-5" aria-hidden="true" />
</button>
```

- [ ] **Step 2: Keep staff messages scoped out unless Phase 08 requires them**

Do not add `MessageBell` to `StaffLayout.vue` in this task unless the messaging implementation confirms staff-to-student or staff-to-staff messages are live product scope.

- [ ] **Step 3: Verify utility controls**

Run:

```bash
npm run lint
E2E_REFRESH_DB=1 npm run test:e2e -- ui-responsive.spec.ts
```

- [ ] **Step 4: Commit utility controls**

```bash
git add resources/js/Components/NotificationBell.vue resources/js/Components/MessageBell.vue resources/js/Components/UserAvatar.vue resources/js/Layouts/StudentLayout.vue resources/js/Layouts/StaffLayout.vue
git commit -m "fix: standardize role navigation utilities"
```

---

### Task 10: Standardize Empty, Loading, And Feedback States

**Files:**
- Modify: `resources/js/Components/UI/EmptyState.vue`
- Modify: `resources/js/Components/UI/Skeleton.vue`
- Modify: `resources/js/Pages/Notifications/Index.vue`
- Modify: `resources/js/Pages/Student/Dashboard.vue`
- Modify: `resources/js/Pages/Admin/Requests/Index.vue`
- Modify: `resources/js/Pages/Student/Requests/Index.vue`
- Modify: `resources/js/Pages/SuperAdmin/Logs/Index.vue`

- [ ] **Step 1: Add EmptyState variants**

Support variants:

```js
const variantClasses = {
    panel: 'rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200',
    inline: 'rounded-lg bg-slate-50 p-4 text-center',
    table: 'p-8 text-center',
};
```

- [ ] **Step 2: Avoid card-inside-table empty states**

For table empty state:

```vue
<tr>
    <td colspan="7" class="p-8">
        <EmptyState variant="table" title="No records found" description="Try adjusting your filters." />
    </td>
</tr>
```

- [ ] **Step 3: Add action feedback**

For actions like "Mark all as read":

```vue
<button
    type="button"
    :disabled="form.processing"
    :aria-busy="form.processing ? 'true' : undefined"
    class="inline-flex min-h-11 items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-500 disabled:cursor-not-allowed disabled:opacity-60"
>
    {{ form.processing ? 'Marking...' : 'Mark all as read' }}
</button>
```

- [ ] **Step 4: Verify feedback states**

Run:

```bash
npm run lint
npm run build
E2E_REFRESH_DB=1 npm run test:e2e -- ui-responsive.spec.ts
```

- [ ] **Step 5: Commit feedback states**

```bash
git add resources/js/Components/UI/EmptyState.vue resources/js/Components/UI/Skeleton.vue resources/js/Pages/Notifications/Index.vue resources/js/Pages/Student/Dashboard.vue resources/js/Pages/Admin/Requests/Index.vue resources/js/Pages/Student/Requests/Index.vue resources/js/Pages/SuperAdmin/Logs/Index.vue
git commit -m "feat: standardize empty and loading states"
```

---

### Task 11: Final Verification And Documentation

**Files:**
- Modify: `docs/09-frontend-design.md`
- Optionally modify active plan file if this work maps to phase status: `docs/plan/phase-10-ui-polish.md`

- [ ] **Step 1: Update frontend design docs with implemented patterns**

Document:

- `PageHeader` usage.
- `ResponsiveRecordList` card/table split.
- `DataTableShell` scroll-safe log/report tables.
- `FormField` accessibility contract.
- `IconButton` accessible icon-only controls.
- Compact mobile card guidelines.

- [ ] **Step 2: Run full frontend verification**

Run:

```bash
npm run lint
npm run build
E2E_REFRESH_DB=1 npm run test:e2e
```

Expected: all commands exit 0.

- [ ] **Step 3: Run relevant Laravel verification**

Run focused tests first if UI changes touched request/payment flows:

```bash
php artisan test --filter=Request
php artisan test --filter=Payment
```

Then run:

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 4: Manual viewport checklist**

Verify these pages at `375x812`, `430x932`, `768x1024`, and desktop:

- `/`
- `/login`
- `/register`
- `/student/dashboard`
- `/student/requests`
- `/student/payments`
- `/student/clearance`
- `/admin/dashboard`
- `/admin/requests`
- `/admin/payments`
- `/admin/clearances`
- `/admin/settings/payment-profile`
- `/department/dashboard`
- `/department/clearances`
- `/superadmin/dashboard`
- `/superadmin/users`
- `/superadmin/logs`

For each page confirm:

- no horizontal overflow
- mobile nav opens/closes
- primary action is visible
- cards remain compact but understandable
- form labels and errors are readable
- touch targets are comfortable

- [ ] **Step 5: Commit docs and final verification updates**

```bash
git add docs/09-frontend-design.md docs/plan/phase-10-ui-polish.md
git commit -m "docs: document responsive UI patterns"
```

---

## Implementation Notes

- Keep diffs focused: one task, one commit.
- Do not restyle unrelated pages while fixing a specific responsive/accessibility issue.
- Prefer compact mobile cards over horizontal scroll for operational/user-facing lists.
- Keep mobile cards information-dense but readable: title, one secondary line, 2-4 metadata fields, status, action.
- Use horizontal scroll only for logs/reports where column scanability matters more than card readability.
- Do not add staff messaging UI unless messaging requirements confirm staff as participants.
- Tests are required for behavior or accessibility contract changes.
- Run security review if upload handling behavior changes, not just visual upload markup.

## Execution Handoff

Recommended execution: subagent-driven, one fresh subagent per task, with review between tasks.

Execution options:

1. **Subagent-Driven (recommended)** — dispatch a fresh subagent per task, review diffs between tasks, commit each task after verification.
2. **Inline Execution** — execute tasks in this session using checkpoints after each task.
