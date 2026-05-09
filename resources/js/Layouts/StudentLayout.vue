<script setup>
import MessageBell from '@/Components/MessageBell.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import UserAvatar from '@/Components/UserAvatar.vue';
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeftOnRectangleIcon,
    Bars3Icon,
    ClipboardDocumentListIcon,
    CreditCardIcon,
    DocumentTextIcon,
    HomeIcon,
    QuestionMarkCircleIcon,
    ShieldCheckIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const showMobileMenu = ref(false);
const page = usePage();

const authUser = computed(() => page.props.auth?.user ?? {});
const userDisplayName = computed(() => {
    const first = authUser.value?.first_name;
    const last = authUser.value?.last_name;
    return [first, last].filter(Boolean).join(' ') || 'Student';
});

const studentLinks = [
    { route: 'student.dashboard', label: 'Dashboard', icon: HomeIcon },
    { route: 'student.requests.index', label: 'My Requests', icon: ClipboardDocumentListIcon },
    { route: 'student.payments.index', label: 'Payments', icon: CreditCardIcon },
    { route: 'student.clearance.show', label: 'Clearance', icon: ShieldCheckIcon },
    { route: 'student.faq.index', label: 'FAQ', icon: QuestionMarkCircleIcon },
];

const isActive = (routeName) => route().current(routeName) || route().current(routeName + '*');
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-brand-600 selection:text-white">

        <!-- ── Top Navigation ───────────────────────────────────────────── -->
        <nav class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-14 items-center justify-between gap-4">

                    <!-- Logo + nav links -->
                    <div class="flex items-center gap-6">
                        <Link :href="route('student.dashboard')" class="flex items-center gap-2.5 group shrink-0">
                            <div class="bg-brand-600 p-1.5 rounded-lg shadow-sm group-hover:bg-brand-500 transition-colors">
                                <DocumentTextIcon class="w-5 h-5 text-white" />
                            </div>
                            <span class="font-display font-bold text-base text-slate-900 hidden sm:block tracking-tight">SVCI Docs</span>
                        </Link>

                        <!-- Desktop nav links -->
                        <div class="hidden md:flex items-center gap-1">
                            <Link
                                v-for="link in studentLinks"
                                :key="link.route"
                                :href="route(link.route)"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                                :class="isActive(link.route)
                                    ? 'bg-brand-50 text-brand-700'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                            >
                                <component :is="link.icon" class="w-4 h-4 shrink-0"
                                    :class="isActive(link.route) ? 'text-brand-600' : 'text-slate-400'" />
                                {{ link.label }}
                            </Link>
                        </div>
                    </div>

                    <!-- Right side: icons + avatar -->
                    <div class="flex items-center gap-1">
                        <MessageBell class="hidden md:inline-flex" />
                        <NotificationBell />
                        <div class="h-5 w-px bg-slate-200 mx-1 hidden md:block"></div>
                        <div class="hidden md:block">
                            <UserAvatar />
                        </div>
                        <!-- Logout (desktop) -->
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="hidden md:inline-flex items-center gap-1.5 ml-1 rounded-lg px-2.5 py-1.5 text-sm font-medium text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                        >
                            <ArrowLeftOnRectangleIcon class="w-4 h-4" />
                            <span class="hidden lg:inline">Log Out</span>
                        </Link>
                        <!-- Mobile hamburger -->
                        <button
                            type="button"
                            class="md:hidden p-1.5 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                            @click="showMobileMenu = !showMobileMenu"
                        >
                            <Bars3Icon v-if="!showMobileMenu" class="w-5 h-5" />
                            <XMarkIcon v-else class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu dropdown -->
            <Transition name="slide-down">
                <div v-if="showMobileMenu" class="md:hidden bg-white border-t border-slate-200 shadow-lg absolute w-full">
                    <div class="px-4 py-3 space-y-1">
                        <Link
                            v-for="link in studentLinks"
                            :key="`m-${link.route}`"
                            :href="route(link.route)"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                            :class="isActive(link.route)
                                ? 'bg-brand-50 text-brand-700'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                            @click="showMobileMenu = false"
                        >
                            <component :is="link.icon" class="w-5 h-5 shrink-0"
                                :class="isActive(link.route) ? 'text-brand-600' : 'text-slate-400'" />
                            {{ link.label }}
                        </Link>
                    </div>
                    <div class="border-t border-slate-200 px-4 py-3">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-slate-800">{{ userDisplayName }}</span>
                            <div class="flex items-center gap-2">
                                <MessageBell />
                                <NotificationBell />
                            </div>
                        </div>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="flex w-full items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                        >
                            <ArrowLeftOnRectangleIcon class="w-4 h-4" />
                            Log Out
                        </Link>
                    </div>
                </div>
            </Transition>
        </nav>

        <!-- Page Heading -->
        <header v-if="$slots.header" class="bg-white border-b border-slate-200 shadow-sm">
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Main Content -->
        <main class="py-8 sm:py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>

<style>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.18s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
