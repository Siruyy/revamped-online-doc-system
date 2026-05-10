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
        class="relative inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
        aria-label="Open messages"
    >
        <EnvelopeIcon class="h-5 w-5" aria-hidden="true" />
        <span
            v-if="unreadMessagesCount > 0"
            class="absolute -right-0.5 -top-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-indigo-600 px-1 text-xs font-bold text-white leading-none"
        >
            {{ unreadMessagesCount > 99 ? '99+' : unreadMessagesCount }}
        </span>
    </a>
</template>
