<script setup>
import MessageBell from '@/Components/MessageBell.vue';
import NavLink from '@/Components/NavLink.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import UserAvatar from '@/Components/UserAvatar.vue';
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { DocumentTextIcon, Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline';

const showingNavigationDropdown = ref(false);

const studentLinks = [
    { route: 'student.dashboard', label: 'Dashboard' },
    { route: 'student.requests.index', label: 'My Requests' },
    { route: 'student.payments.index', label: 'Payments' },
    { route: 'student.clearance.show', label: 'Clearance' },
    { route: 'student.notifications.index', label: 'Notifications' },
    { route: 'student.faq.index', label: 'FAQ' },
];
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-brand-600 selection:text-white">
        <nav class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('student.dashboard')" class="flex items-center gap-2 group">
                                <div class="bg-brand-600 p-1.5 rounded-md shadow-sm transition-transform group-hover:scale-105">
                                    <DocumentTextIcon class="w-6 h-6 text-white" />
                                </div>
                                <span class="font-display font-bold text-xl text-slate-900 tracking-tight hidden sm:block">SVCI Docs</span>
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 md:flex">
                            <NavLink
                                v-for="link in studentLinks"
                                :key="link.route"
                                :href="route(link.route)"
                                :active="route().current(`${link.route}*`)"
                            >
                                {{ link.label }}
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden md:ms-6 md:flex md:items-center md:gap-4">
                        <div class="flex items-center gap-2">
                            <MessageBell />
                            <NotificationBell />
                        </div>
                        <div class="h-6 w-px bg-slate-200 mx-2"></div>
                        <UserAvatar />
                    </div>

                    <!-- Hamburger -->
                    <div class="-me-2 flex items-center md:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:bg-slate-100 focus:text-slate-700 transition duration-150 ease-in-out"
                        >
                            <Bars3Icon v-if="!showingNavigationDropdown" class="w-6 h-6" />
                            <XMarkIcon v-else class="w-6 h-6" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div
                :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                class="md:hidden bg-white border-t border-slate-200 shadow-lg absolute w-full"
            >
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink
                        v-for="link in studentLinks"
                        :key="`mobile-${link.route}`"
                        :href="route(link.route)"
                        :active="route().current(`${link.route}*`)"
                    >
                        {{ link.label }}
                    </ResponsiveNavLink>
                </div>

                <!-- Responsive Settings Options -->
                <div class="border-t border-slate-200 pb-1 pt-4 bg-slate-50">
                    <div class="px-4 flex items-center justify-between mb-3">
                        <div class="font-medium text-base text-slate-800">
                            {{ $page.props.auth.user.first_name }} {{ $page.props.auth.user.last_name }}
                        </div>
                        <div class="flex gap-2">
                            <MessageBell />
                            <NotificationBell />
                        </div>
                    </div>

                    <div class="space-y-1 mt-3">
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                            Log Out
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        <header class="bg-white shadow-sm ring-1 ring-slate-900/5" v-if="$slots.header">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page Content -->
        <main class="py-8 sm:py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>