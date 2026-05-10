<script setup>
import NotificationBell from '@/Components/NotificationBell.vue';
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    HomeIcon,
    UsersIcon,
    UserPlusIcon,
    ClipboardDocumentListIcon,
    ChartBarIcon,
    BellIcon,
    UserCircleIcon,
    CheckBadgeIcon,
    DocumentTextIcon,
    BanknotesIcon,
    TicketIcon,
    MegaphoneIcon,
    QuestionMarkCircleIcon,
    Bars3Icon,
    XMarkIcon,
    ArrowLeftOnRectangleIcon,
    CogIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline';

// ─── Sidebar collapse state ─────────────────────────────────────────────────
// Persist preference in localStorage so it survives page navigations
const storedCollapsed =
    typeof localStorage !== 'undefined' ? localStorage.getItem('sidebar_collapsed') === 'true' : false;
const sidebarCollapsed = ref(storedCollapsed);

watch(sidebarCollapsed, (val) => {
    if (typeof localStorage !== 'undefined') {
        localStorage.setItem('sidebar_collapsed', String(val));
    }
});

// Mobile drawer
const showSidebar = ref(false);

const page = usePage();
const authUser = computed(() => page.props.auth?.user ?? {});

const role = computed(() => authUser.value?.role ?? 'admin');
const userDisplayName = computed(() => {
    const firstName = authUser.value?.first_name;
    const lastName = authUser.value?.last_name;
    const fullName = [firstName, lastName].filter(Boolean).join(' ').trim();
    return fullName || authUser.value?.name || 'Staff User';
});
const userInitials = computed(() => {
    const first = (authUser.value?.first_name || '')[0] ?? '';
    const last = (authUser.value?.last_name || '')[0] ?? '';
    return (first + last).toUpperCase() || 'S';
});

const departmentTitle = computed(
    () =>
        ({
            teacher: 'Teacher',
            dean: 'Dean',
            accounting: 'Accounting',
            sao: 'SAO',
        })[role.value] ?? '',
);

const roleLabel = computed(() => {
    if (departmentTitle.value) return `${departmentTitle.value} Dept`;
    if (role.value === 'superadmin') return 'SuperAdmin';
    return 'Admin';
});

const roleBadgeClass = computed(
    () =>
        ({
            superadmin: 'bg-violet-100 text-violet-700',
            admin: 'bg-emerald-100 text-emerald-700',
            teacher: 'bg-sky-100 text-sky-700',
            dean: 'bg-indigo-100 text-indigo-700',
            accounting: 'bg-amber-100 text-amber-700',
            sao: 'bg-rose-100 text-rose-700',
        })[role.value] ?? 'bg-slate-100 text-slate-600',
);

const getIconForRoute = (routeName) => {
    if (routeName.includes('dashboard')) return HomeIcon;
    if (routeName.includes('users.pending') || routeName.includes('users.create')) return UserPlusIcon;
    if (routeName.includes('users')) return UsersIcon;
    if (routeName.includes('logs')) return ClipboardDocumentListIcon;
    if (routeName.includes('reports')) return ChartBarIcon;
    if (routeName.includes('notifications')) return BellIcon;
    if (routeName.includes('profile')) return UserCircleIcon;
    if (routeName.includes('clearance')) return CheckBadgeIcon;
    if (routeName.includes('requests')) return DocumentTextIcon;
    if (routeName.includes('document-types')) return ClipboardDocumentListIcon;
    if (routeName.includes('payments')) return BanknotesIcon;
    if (routeName.includes('releases')) return TicketIcon;
    if (routeName.includes('announcements')) return MegaphoneIcon;
    if (routeName.includes('faq')) return QuestionMarkCircleIcon;
    if (routeName.includes('settings')) return CogIcon;
    return DocumentTextIcon;
};

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
        ].map((link) => ({ ...link, icon: getIconForRoute(link.route) }));
    }

    if (['teacher', 'dean', 'accounting', 'sao'].includes(role.value)) {
        return [
            { route: 'department.dashboard', label: 'Dashboard' },
            { route: 'department.clearances.index', label: 'Clearances' },
            { route: 'department.notifications.index', label: 'Notifications' },
            { route: 'department.faq.index', label: 'FAQ' },
            { route: 'department.profile.edit', label: 'Profile' },
        ].map((link) => ({ ...link, icon: getIconForRoute(link.route) }));
    }

    return [
        { route: 'admin.dashboard', label: 'Dashboard' },
        { route: 'admin.requests.index', label: 'Requests' },
        { route: 'admin.payments.index', label: 'Payments' },
        { route: 'admin.releases.index', label: 'Releases' },
        { route: 'admin.clearances.index', label: 'Clearance Monitor' },
        { route: 'admin.document-types.index', label: 'Document Types' },
        { route: 'admin.announcements.index', label: 'Announcements' },
        { route: 'admin.faqs.index', label: 'FAQs' },
        { route: 'admin.settings.payment-profile.index', label: 'Payment Settings' },
        { route: 'admin.reports.index', label: 'Reports' },
        { route: 'admin.notifications.index', label: 'Notifications' },
        { route: 'admin.profile.edit', label: 'Profile' },
    ].map((link) => ({ ...link, icon: getIconForRoute(link.route) }));
});

const isActive = (routeName) => route().current(routeName) || route().current(routeName.replace('.index', '.*'));
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-brand-600 selection:text-white flex">
        <!-- ── Desktop Sidebar ──────────────────────────────────────────── -->
        <aside
            class="hidden lg:flex lg:flex-col bg-slate-900 text-slate-300 shrink-0 shadow-xl fixed h-dvh z-50 transition-all duration-300"
            :class="sidebarCollapsed ? 'w-16' : 'w-64'"
        >
            <!-- Logo -->
            <div
                class="h-14 flex items-center border-b border-slate-800 bg-slate-950"
                :class="sidebarCollapsed ? 'justify-center px-0' : 'px-5 gap-3'"
            >
                <Link :href="route(links[0]?.route ?? 'admin.dashboard')" class="flex items-center gap-3 group">
                    <div
                        class="bg-brand-500 p-1.5 rounded-lg shadow-sm group-hover:bg-brand-400 transition-colors shrink-0"
                    >
                        <DocumentTextIcon class="w-5 h-5 text-white" />
                    </div>
                    <span
                        v-if="!sidebarCollapsed"
                        class="font-display font-bold text-base text-white tracking-tight whitespace-nowrap overflow-hidden"
                    >
                        SVCI Staff
                    </span>
                </Link>
            </div>

            <!-- User info (expanded only) -->
            <div v-if="!sidebarCollapsed" class="px-4 py-4 border-b border-slate-800/60">
                <div class="flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-full bg-brand-700 flex items-center justify-center text-white text-sm font-bold shadow-inner shrink-0"
                    >
                        {{ userInitials }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="truncate text-sm font-medium text-white leading-tight">{{ userDisplayName }}</p>
                        <span
                            :class="roleBadgeClass"
                            class="inline-block mt-0.5 rounded-full px-1.5 py-0.5 text-xs font-semibold"
                        >
                            {{ roleLabel }}
                        </span>
                    </div>
                </div>
            </div>
            <!-- Collapsed avatar -->
            <div v-else class="flex justify-center py-3 border-b border-slate-800/60">
                <div
                    class="w-8 h-8 rounded-full bg-brand-700 flex items-center justify-center text-white text-xs font-bold"
                >
                    {{ userInitials }}
                </div>
            </div>

            <!-- Nav Links -->
            <nav
                class="flex-1 overflow-y-auto py-3 space-y-0.5 custom-scrollbar"
                :class="sidebarCollapsed ? 'px-2' : 'px-3'"
            >
                <Link
                    v-for="item in links"
                    :key="item.route"
                    :href="route(item.route)"
                    :title="sidebarCollapsed ? item.label : undefined"
                    class="group flex items-center rounded-lg text-sm font-medium transition-all duration-150"
                    :class="[
                        sidebarCollapsed ? 'justify-center px-0 py-2.5 h-10' : 'gap-3 px-3 py-2.5',
                        isActive(item.route)
                            ? 'bg-brand-600 text-white shadow-sm'
                            : 'text-slate-400 hover:text-white hover:bg-slate-800',
                    ]"
                >
                    <component
                        :is="item.icon"
                        class="w-5 h-5 shrink-0 transition-colors"
                        :class="isActive(item.route) ? 'text-white' : 'text-slate-500 group-hover:text-slate-300'"
                    />
                    <span v-if="!sidebarCollapsed" class="truncate">{{ item.label }}</span>
                </Link>
            </nav>

            <!-- Bottom: collapse toggle + logout -->
            <div
                class="border-t border-slate-800 bg-slate-950 py-2"
                :class="sidebarCollapsed ? 'px-2 space-y-1' : 'px-3 space-y-0.5'"
            >
                <!-- Collapse toggle -->
                <button
                    type="button"
                    class="flex w-full items-center rounded-lg text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                    :class="sidebarCollapsed ? 'justify-center py-2.5 h-10' : 'gap-3 px-3 py-2.5'"
                    :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    @click="sidebarCollapsed = !sidebarCollapsed"
                >
                    <ChevronRightIcon v-if="sidebarCollapsed" class="w-5 h-5 shrink-0 text-slate-500" />
                    <ChevronLeftIcon v-else class="w-5 h-5 shrink-0 text-slate-500" />
                    <span v-if="!sidebarCollapsed" class="text-xs">Collapse</span>
                </button>
                <!-- Log out -->
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex w-full items-center rounded-lg text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                    :class="sidebarCollapsed ? 'justify-center py-2.5 h-10' : 'gap-3 px-3 py-2.5'"
                    :title="sidebarCollapsed ? 'Log Out' : undefined"
                >
                    <ArrowLeftOnRectangleIcon class="w-5 h-5 shrink-0 text-slate-500" />
                    <span v-if="!sidebarCollapsed">Log Out</span>
                </Link>
            </div>
        </aside>

        <!-- ── Main Content ─────────────────────────────────────────────── -->
        <div
            class="flex-1 flex flex-col min-w-0 transition-all duration-300"
            :class="sidebarCollapsed ? 'lg:ml-16' : 'lg:ml-64'"
        >
            <!-- Top Header -->
            <header
                class="h-14 bg-white border-b border-slate-200 shadow-sm sticky top-0 z-40 flex items-center justify-between gap-4 px-4 sm:px-6"
            >
                <!-- Mobile hamburger + brand -->
                <div class="flex items-center gap-3 lg:hidden">
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
                    <span class="font-display font-bold text-base text-slate-900">SVCI</span>
                </div>

                <!-- Role chip (desktop) — subtle, no "Admin Console" text -->
                <div class="hidden lg:block">
                    <span :class="roleBadgeClass" class="rounded-full px-3 py-1 text-xs font-semibold">
                        {{ roleLabel }}
                    </span>
                </div>

                <!-- Right header actions -->
                <div class="flex items-center gap-2 ml-auto">
                    <NotificationBell />
                    <div class="h-5 w-px bg-slate-200 mx-1"></div>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="hidden sm:inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                    >
                        <ArrowLeftOnRectangleIcon class="w-4 h-4" />
                        Log Out
                    </Link>
                </div>
            </header>

            <!-- Mobile Sidebar Backdrop + Drawer -->
            <Transition name="fade">
                <div v-if="showSidebar" class="fixed inset-0 z-50 lg:hidden">
                    <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm" @click="showSidebar = false" />
                    <aside
                        id="staff-mobile-navigation"
                        class="absolute inset-y-0 left-0 flex max-h-dvh min-h-dvh w-64 flex-col bg-slate-900 text-slate-300 shadow-2xl"
                    >
                        <div class="h-14 flex items-center justify-between px-5 bg-slate-950 border-b border-slate-800">
                            <span class="font-display font-bold text-base text-white">SVCI Staff</span>
                            <button
                                class="p-1.5 text-slate-400 hover:text-white rounded-lg"
                                @click="showSidebar = false"
                            >
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                        <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-0.5">
                            <Link
                                v-for="item in links"
                                :key="`m-${item.route}`"
                                :href="route(item.route)"
                                class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium"
                                :class="
                                    isActive(item.route)
                                        ? 'bg-brand-600 text-white'
                                        : 'text-slate-400 hover:text-white hover:bg-slate-800'
                                "
                                @click="showSidebar = false"
                            >
                                <component
                                    :is="item.icon"
                                    class="w-5 h-5 shrink-0"
                                    :class="isActive(item.route) ? 'text-white' : 'text-slate-500'"
                                />
                                {{ item.label }}
                            </Link>
                        </nav>
                        <div class="px-3 pb-3 border-t border-slate-800 pt-3">
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="flex w-full items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                            >
                                <ArrowLeftOnRectangleIcon class="w-5 h-5 text-slate-500" />
                                Log Out
                            </Link>
                        </div>
                    </aside>
                </div>
            </Transition>

            <!-- Page sub-header slot -->
            <div v-if="$slots.header" class="bg-white border-b border-slate-200">
                <div class="px-4 py-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
                    <slot name="header" />
                </div>
            </div>

            <!-- Main content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <div class="max-w-7xl mx-auto">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 3px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #475569;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
