<script setup>
import { InboxIcon } from '@heroicons/vue/24/outline';

defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    icon: { type: [Function, Object], default: () => InboxIcon },
    compact: { type: Boolean, default: false },
});
</script>

<template>
    <div
        role="status"
        class="rounded-2xl bg-white text-center shadow-sm ring-1 ring-slate-200"
        :class="compact ? 'p-6' : 'p-10'"
    >
        <component
            :is="icon"
            class="mx-auto text-slate-300"
            :class="compact ? 'h-9 w-9' : 'h-12 w-12'"
            aria-hidden="true"
        />
        <p class="mt-3 font-display font-semibold text-slate-700" :class="compact ? 'text-sm' : 'text-base'">
            {{ title }}
        </p>
        <p v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</p>
        <div v-if="$slots.actions" class="mt-4 flex justify-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
