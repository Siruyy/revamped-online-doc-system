<script setup>
import { EnvelopeIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const unreadMessagesCount = computed(() => page.props.unreadMessagesCount ?? 0);
</script>

<template>
    <a
        href="#"
        class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors"
        aria-label="Messages"
    >
        <EnvelopeIcon class="h-5 w-5" />
        <span
            v-if="unreadMessagesCount > 0"
            class="absolute -right-0.5 -top-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-indigo-600 px-1 text-xs font-bold text-white leading-none"
        >
            {{ unreadMessagesCount > 99 ? '99+' : unreadMessagesCount }}
        </span>
    </a>
</template>
