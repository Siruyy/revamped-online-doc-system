<script setup>
import { BellIcon } from '@heroicons/vue/24/outline';
import { computed, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

/** Avoid duplicate .notification() handlers when the bell remounts across navigations. */
const notificationEchoBound = new Set();

const page = usePage();

const unreadCount = computed(() => page.props.unreadNotificationsCount ?? 0);

const notificationsUrl = computed(() => {
    const role = page.props.auth?.user?.role;
    const routesByRole = {
        student: 'student.notifications.index',
        admin: 'admin.notifications.index',
        teacher: 'department.notifications.index',
        dean: 'department.notifications.index',
        accounting: 'department.notifications.index',
        sao: 'department.notifications.index',
        superadmin: 'superadmin.notifications.index',
    };
    const name = routesByRole[role];

    return name ? route(name) : '#';
});

const userId = computed(() => page.props.auth?.user?.id ?? null);

onMounted(() => {
    if (!userId.value || typeof window === 'undefined' || !window.Echo) {
        return;
    }
    const key = `notifications:${userId.value}`;
    if (notificationEchoBound.has(key)) {
        return;
    }
    notificationEchoBound.add(key);
    window.Echo.private(`user.${userId.value}`).notification(() => {
        router.reload({ preserveScroll: true });
    });
});

onUnmounted(() => {
    // Intentionally do not Echo.leave(user.*): other views may still be listening.
});
</script>

<template>
    <Link
        :href="notificationsUrl"
        class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors"
        aria-label="Notifications"
    >
        <BellIcon class="h-5 w-5" />
        <span
            v-if="unreadCount > 0"
            class="absolute -right-0.5 -top-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-xs font-bold text-white leading-none"
        >
            {{ unreadCount > 99 ? '99+' : unreadCount }}
        </span>
    </Link>
</template>
