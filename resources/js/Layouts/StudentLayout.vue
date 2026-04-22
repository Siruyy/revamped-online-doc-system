<script setup>
import MessageBell from '@/Components/MessageBell.vue';
import NavLink from '@/Components/NavLink.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import UserAvatar from '@/Components/UserAvatar.vue';
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

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
    <div class="min-h-screen bg-slate-100">
        <nav class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-8">
                        <Link :href="route('student.dashboard')" class="text-lg font-bold text-slate-900">SVCI</Link>

                        <div class="hidden items-center gap-2 md:flex">
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

                    <div class="hidden items-center gap-3 md:flex">
                        <MessageBell />
                        <NotificationBell />
                        <UserAvatar />
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center rounded-md p-2 text-slate-500 hover:bg-slate-100 md:hidden"
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                    >
                        ☰
                    </button>
                </div>
            </div>

            <div v-if="showingNavigationDropdown" class="border-t border-slate-200 bg-white md:hidden">
                <div class="space-y-1 p-3">
                    <ResponsiveNavLink
                        v-for="link in studentLinks"
                        :key="`mobile-${link.route}`"
                        :href="route(link.route)"
                        :active="route().current(`${link.route}*`)"
                    >
                        {{ link.label }}
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('logout')" method="post" as="button">Log Out</ResponsiveNavLink>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main class="py-8">
            <slot />
        </main>
    </div>
</template>
