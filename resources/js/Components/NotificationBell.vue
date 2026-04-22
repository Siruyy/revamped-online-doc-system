<script setup>
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
        class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
        aria-label="Notifications"
    >
        <span class="text-lg">🔔</span>
        <span
            v-if="unreadCount > 0"
            class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-600 px-1 text-xs font-semibold text-white"
        >
            {{ unreadCount > 99 ? '99+' : unreadCount }}
        </span>
    </Link>
</template>
