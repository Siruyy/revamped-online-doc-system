<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
});
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <p v-if="eyebrow" class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">{{ eyebrow }}</p>
            <h1 class="text-2xl font-display font-bold tracking-tight text-slate-950 sm:text-3xl">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">{{ subtitle }}</p>
        </div>

        <div v-if="$slots.actions" class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <slot name="actions" />
        </div>
    </div>
</template>
