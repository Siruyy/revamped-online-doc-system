<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const unreadCount = computed(() => page.props.unreadNotificationsCount ?? 0);
</script>

<template>
    <Link
        :href="route('student.notifications.index')"
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
