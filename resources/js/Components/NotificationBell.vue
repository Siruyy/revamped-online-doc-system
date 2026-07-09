<script setup>
import { useRealtimeOrPoll } from '@/Composables/useRealtimeOrPoll';
import { BellIcon } from '@heroicons/vue/24/outline';
import { computed, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

/** Avoid duplicate .notification() handlers when the bell remounts across navigations. */
const notificationEchoBound = new Map();

const page = usePage();

const unreadCount = computed(() => page.props.unreadNotificationsCount ?? 0);

const notificationsUrl = computed(() => {
    const role = page.props.auth?.user?.role;
    const routesByRole = {
        student: 'student.notifications.index',
        admin: 'admin.notifications.index',
        dean: 'department.notifications.index',
        president: 'department.notifications.index',
        librarian: 'department.notifications.index',
        student_affairs: 'department.notifications.index',
        alumni: 'department.notifications.index',
        guidance: 'department.notifications.index',
        superadmin: 'superadmin.notifications.index',
    };
    const name = routesByRole[role];

    return name ? route(name) : '#';
});

const userId = computed(() => page.props.auth?.user?.id ?? null);

const reloadNotifications = () => {
    router.reload({ only: ['unreadNotificationsCount'], preserveScroll: true });
};

const echoConnection = () =>
    window.Echo?.connector?.pusher?.connection ?? window.Echo?.connector?.connection ?? window.Echo;

const hasEchoChannel = (name) => Boolean(window.Echo?.connector?.channels?.[`private-${name}`]);

onMounted(() => {
    if (!userId.value || typeof window === 'undefined' || !window.Echo) {
        return;
    }
    const key = `notifications:${userId.value}`;
    const channelName = `user.${userId.value}`;
    const connection = echoConnection();
    if (notificationEchoBound.get(key) === connection && hasEchoChannel(channelName)) {
        return;
    }
    notificationEchoBound.set(key, connection);
    window.Echo.private(channelName).notification(() => {
        reloadNotifications();
    });
});

useRealtimeOrPoll(reloadNotifications, { intervalMs: 90000 });

onUnmounted(() => {
    // Intentionally do not Echo.leave(user.*): other views may still be listening.
});
</script>

<template>
    <Link
        :href="notificationsUrl"
        class="relative inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
        aria-label="Open notifications"
    >
        <BellIcon class="h-5 w-5" aria-hidden="true" />
        <span
            v-if="unreadCount > 0"
            class="absolute -right-0.5 -top-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-xs font-bold text-white leading-none"
        >
            {{ unreadCount > 99 ? '99+' : unreadCount }}
        </span>
    </Link>
</template>
