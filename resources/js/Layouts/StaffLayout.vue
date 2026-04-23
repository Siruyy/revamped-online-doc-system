<script setup>
import NotificationBell from '@/Components/NotificationBell.vue';
import { computed, ref } from 'vue';
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
    MegaphoneIcon,
    QuestionMarkCircleIcon,
    Bars3Icon,
    XMarkIcon,
    ArrowLeftOnRectangleIcon
} from '@heroicons/vue/24/outline';

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
const userInitial = computed(() => {
    const firstName = authUser.value?.first_name;
    const name = firstName || authUser.value?.name || 'S';

    return String(name).charAt(0).toUpperCase();
});

const departmentTitle = computed(() => {
    const labels = {
        teacher: 'Teacher',
        dean: 'Dean',
        accounting: 'Accounting',
        sao: 'SAO',
    };

    return labels[role.value] ?? '';
});

// Map routes to appropriate icons
const getIconForRoute = (routeName) => {
    if (routeName.includes('dashboard')) return HomeIcon;
    if (routeName.includes('users.pending')) return UserPlusIcon;
    if (routeName.includes('users.create')) return UserPlusIcon;
    if (routeName.includes('users')) return UsersIcon;
    if (routeName.includes('logs')) return ClipboardDocumentListIcon;
    if (routeName.includes('reports')) return ChartBarIcon;
    if (routeName.includes('notifications')) return BellIcon;
    if (routeName.includes('profile')) return UserCircleIcon;
    if (routeName.includes('clearance')) return CheckBadgeIcon;
    if (routeName.includes('requests')) return DocumentTextIcon;
    if (routeName.includes('document-types')) return ClipboardDocumentListIcon;
    if (routeName.includes('payments')) return BanknotesIcon;
    if (routeName.includes('announcements')) return MegaphoneIcon;
    if (routeName.includes('faq')) return QuestionMarkCircleIcon;
    
    return DocumentTextIcon; // fallback
};

const links = computed(() => {
    let rawLinks = [];
    if (role.value === 'superadmin') {
        rawLinks = [
            { route: 'superadmin.dashboard', label: 'Dashboard' },
            { route: 'superadmin.users.index', label: 'Users' },
            { route: 'superadmin.users.pending', label: 'Pending registrations' },
            { route: 'superadmin.users.create', label: 'Create staff' },
            { route: 'superadmin.logs.index', label: 'Activity logs' },
            { route: 'superadmin.reports.index', label: 'Reports' },
            { route: 'superadmin.notifications.index', label: 'Notifications' },
            { route: 'superadmin.profile.edit', label: 'Profile' },
        ];
    } else if (['teacher', 'dean', 'accounting', 'sao'].includes(role.value)) {
        rawLinks = [
            { route: 'department.dashboard', label: 'Dashboard' },
            { route: 'department.clearances.index', label: 'Clearances' },
            { route: 'department.notifications.index', label: 'Notifications' },
            { route: 'department.faq.index', label: 'FAQ' },
            { route: 'department.profile.edit', label: 'Profile' },
        ];
    } else {
        rawLinks = [
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
    }

    return rawLinks.map(link => ({
        ...link,
        icon: getIconForRoute(link.route)
    }));
});

const isActive = (routeName) => route().current(routeName) || route().current(routeName.replace('.index', '.*'));
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-brand-600 selection:text-white flex">
        
        <!-- Desktop Sidebar -->
        <aside class="hidden lg:flex lg:flex-col w-72 bg-slate-900 text-slate-300 border-r border-slate-800 shrink-0 shadow-xl fixed h-screen z-50">
            <!-- Logo area -->
            <div class="h-16 flex items-center px-6 bg-slate-950 border-b border-slate-800">
                <Link :href="route(links[0]?.route ?? 'admin.dashboard')" class="flex items-center gap-3 group">
                    <div class="bg-brand-500 p-1.5 rounded shadow-sm group-hover:bg-brand-400 transition-colors">
                        <DocumentTextIcon class="w-5 h-5 text-white" />
                    </div>
                    <span class="font-display font-bold text-lg text-white tracking-tight">SVCI Staff</span>
                </Link>
            </div>
            
            <!-- User info summary -->
            <div class="px-6 py-5 border-b border-slate-800/60 bg-slate-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-white font-bold shadow-inner">
                        {{ userInitial }}
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <div class="truncate text-sm font-medium text-white">{{ userDisplayName }}</div>
                        <div class="text-xs text-brand-400 font-semibold mt-0.5 uppercase tracking-wider">
                            <span v-if="departmentTitle">{{ departmentTitle }} Dept</span>
                            <span v-else-if="role === 'admin'">Admin</span>
                            <span v-else-if="role === 'superadmin'">SuperAdmin</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 custom-scrollbar">
                <Link
                    v-for="item in links"
                    :key="item.route"
                    :href="route(item.route)"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200"
                    :class="isActive(item.route) 
                        ? 'bg-brand-600 text-white shadow-md shadow-brand-900/20' 
                        : 'text-slate-400 hover:text-white hover:bg-slate-800'"
                >
                    <component 
                        :is="item.icon" 
                        class="w-5 h-5 shrink-0 transition-colors duration-200" 
                        :class="isActive(item.route) ? 'text-white' : 'text-slate-500 group-hover:text-slate-300'"
                    />
                    {{ item.label }}
                </Link>
            </nav>

            <!-- Bottom action -->
            <div class="p-4 border-t border-slate-800 bg-slate-950">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex w-full items-center gap-3 px-3 py-2.5 text-sm font-medium text-slate-400 rounded-lg hover:text-white hover:bg-slate-800 transition-colors"
                >
                    <ArrowLeftOnRectangleIcon class="w-5 h-5 text-slate-500" />
                    Log Out
                </Link>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 lg:ml-72 transition-all duration-300">
            
            <!-- Top Header -->
            <header class="h-16 bg-white border-b border-slate-200 shadow-sm sticky top-0 z-40 flex items-center justify-between px-4 sm:px-6 lg:px-8">
                
                <!-- Mobile Menu Button & Brand -->
                <div class="flex items-center gap-4 lg:hidden">
                    <button
                        type="button"
                        class="p-2 -ml-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500"
                        @click="showSidebar = !showSidebar"
                    >
                        <span class="sr-only">Open sidebar</span>
                        <Bars3Icon class="w-6 h-6" />
                    </button>
                    <span class="font-display font-bold text-lg text-slate-900">SVCI</span>
                </div>

                <!-- Desktop Context Info -->
                <div class="hidden lg:flex items-center text-sm font-medium text-slate-500">
                    <span v-if="departmentTitle" class="text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-md">{{ departmentTitle }} Department</span>
                    <span v-else-if="role === 'admin'" class="text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md">Admin Console</span>
                    <span v-else-if="role === 'superadmin'" class="text-violet-600 bg-violet-50 px-2.5 py-1 rounded-md shadow-sm border border-violet-100">SuperAdmin Console</span>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center gap-4">
                    <div class="hidden sm:block">
                        <NotificationBell />
                    </div>
                    <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="hidden sm:flex items-center text-sm font-semibold text-slate-600 hover:text-slate-900"
                    >
                        Log Out
                    </Link>
                </div>
            </header>

            <!-- Mobile Sidebar Backdrop & Menu -->
            <div v-if="showSidebar" class="relative z-50 lg:hidden">
                <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="showSidebar = false"></div>
                <div class="fixed inset-y-0 left-0 w-72 bg-slate-900 text-slate-300 shadow-2xl flex flex-col">
                    <div class="h-16 flex items-center justify-between px-6 bg-slate-950 border-b border-slate-800">
                        <span class="font-display font-bold text-lg text-white">SVCI Staff</span>
                        <button @click="showSidebar = false" class="p-2 -mr-2 text-slate-400 hover:text-white">
                            <XMarkIcon class="w-6 h-6" />
                        </button>
                    </div>
                    
                    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                        <Link
                            v-for="item in links"
                            :key="`mobile-${item.route}`"
                            :href="route(item.route)"
                            class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-base font-medium"
                            :class="isActive(item.route) 
                                ? 'bg-brand-600 text-white' 
                                : 'text-slate-400 hover:text-white hover:bg-slate-800'"
                            @click="showSidebar = false"
                        >
                            <component 
                                :is="item.icon" 
                                class="w-6 h-6 shrink-0" 
                                :class="isActive(item.route) ? 'text-white' : 'text-slate-500'"
                            />
                            {{ item.label }}
                        </Link>
                    </nav>
                </div>
            </div>

            <!-- Page Specific Header (Slot) -->
            <div v-if="$slots.header" class="bg-white border-b border-slate-200 shadow-sm">
                <div class="px-4 py-5 sm:px-6 lg:px-8 max-w-7xl mx-auto">
                    <slot name="header" />
                </div>
            </div>

            <!-- Main Content Area -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <div class="max-w-7xl mx-auto">
                    <slot />
                </div>
            </main>
            
        </div>
    </div>
</template>

<style>
/* Custom Scrollbar for the dark sidebar */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
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
</style>
