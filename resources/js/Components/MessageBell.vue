<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const unreadMessagesCount = computed(() => page.props.unreadMessagesCount ?? 0);
</script>

<template>
    <a
        href="#"
        class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
        aria-label="Messages"
    >
        <span class="text-lg">✉️</span>
        <span
            v-if="unreadMessagesCount > 0"
            class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1 text-xs font-semibold text-white"
        >
            {{ unreadMessagesCount > 99 ? '99+' : unreadMessagesCount }}
        </span>
    </a>
</template>
