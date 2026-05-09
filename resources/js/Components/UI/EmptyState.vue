<script setup>
import { InboxIcon } from '@heroicons/vue/24/outline';

defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    icon: { type: [Function, Object], default: () => InboxIcon },
});
</script>

<template>
    <div role="status" class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200">
        <component :is="icon" class="mx-auto h-12 w-12 text-slate-300" aria-hidden="true" />
        <p class="mt-3 font-display text-base font-semibold text-slate-700">{{ title }}</p>
        <p v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</p>
        <div v-if="$slots.actions" class="mt-4 flex justify-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
