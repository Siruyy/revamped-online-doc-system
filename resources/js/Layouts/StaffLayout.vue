<script setup>
import NavLink from '@/Components/NavLink.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const showSidebar = ref(false);
const page = usePage();

const role = computed(() => page.props.auth?.user?.role ?? 'admin');

const departmentTitle = computed(() => {
    const labels = {
        teacher: 'Teacher',
        dean: 'Dean',
        accounting: 'Accounting',
        sao: 'SAO',
    };

    return labels[role.value] ?? '';
});

const links = computed(() => {
    if (role.value === 'superadmin') {
        return [
            { route: 'superadmin.dashboard', label: 'Dashboard' },
            { route: 'superadmin.users.index', label: 'Users' },
            { route: 'superadmin.users.pending', label: 'Pending registrations' },
            { route: 'superadmin.users.create', label: 'Create staff' },
            { route: 'superadmin.logs.index', label: 'Activity logs' },
            { route: 'superadmin.reports.index', label: 'Reports' },
            { route: 'superadmin.notifications.index', label: 'Notifications' },
            { route: 'superadmin.profile.edit', label: 'Profile' },
        ];
    }

    if (['teacher', 'dean', 'accounting', 'sao'].includes(role.value)) {
        return [
            { route: 'department.dashboard', label: 'Dashboard' },
            { route: 'department.clearances.index', label: 'Clearances' },
            { route: 'department.notifications.index', label: 'Notifications' },
            { route: 'department.faq.index', label: 'FAQ' },
            { route: 'department.profile.edit', label: 'Profile' },
        ];
    }

    return [
        { route: 'admin.dashboard', label: 'Dashboard' },
        { route: 'admin.requests.index', label: 'Requests' },
        { route: 'admin.payments.index', label: 'Payments' },
        { route: 'admin.clearances.index', label: 'Clearance Monitor' },
        { route: 'admin.document-types.index', label: 'Document Types' },
        { route: 'admin.announcements.index', label: 'Announcements' },
        { route: 'admin.faqs.index', label: 'FAQs' },
        { route: 'admin.reports.index', label: 'Reports' },
        { route: 'admin.notifications.index', label: 'Notifications' },
        { route: 'admin.profile.edit', label: 'Profile' },
    ];
});

const isActive = (routeName) => route().current(routeName) || route().current(routeName.replace('.index', '.*'));
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <div class="flex">
            <aside class="hidden w-72 border-r border-slate-200 bg-white lg:block">
                <div class="border-b border-slate-200 px-5 py-4">
                    <Link :href="route(links[0].route)" class="text-lg font-bold text-slate-900">SVCI Staff</Link>
                </div>
                <nav class="space-y-1 p-3">
                    <NavLink
                        v-for="item in links"
                        :key="item.route"
                        :href="route(item.route)"
                        :active="isActive(item.route)"
                    >
                        {{ item.label }}
                    </NavLink>
                </nav>
            </aside>

            <div class="flex-1">
                <header class="border-b border-slate-200 bg-white">
                    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                        <button
                            type="button"
                            class="rounded-md border border-slate-300 px-3 py-1 text-sm text-slate-600 lg:hidden"
                            @click="showSidebar = !showSidebar"
                        >
                            Menu
                        </button>
                        <div class="flex flex-wrap items-center gap-3 text-sm text-slate-600">
                            <span v-if="departmentTitle" class="font-semibold text-indigo-800">{{
                                departmentTitle
                            }}</span>
                            <span v-if="departmentTitle">Department</span>
                            <span v-else-if="role === 'admin'">Admin Console</span>
                            <span v-else-if="role === 'superadmin'" class="font-semibold text-violet-800"
                                >SuperAdmin Console</span
                            >
                            <span v-else>Staff Console</span>
                        </div>
                        <NotificationBell />
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="text-sm font-semibold text-slate-700"
                            >Log Out</Link
                        >
                    </div>
                </header>

                <div v-if="showSidebar" class="border-b border-slate-200 bg-white lg:hidden">
                    <nav class="space-y-1 p-3">
                        <ResponsiveNavLink
                            v-for="item in links"
                            :key="`mobile-${item.route}`"
                            :href="route(item.route)"
                            :active="isActive(item.route)"
                        >
                            {{ item.label }}
                        </ResponsiveNavLink>
                    </nav>
                </div>

                <header v-if="$slots.header" class="bg-white shadow-sm">
                    <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <main class="py-8">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
