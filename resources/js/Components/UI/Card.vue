<script setup>
defineProps({
    title: { type: String, default: null },
    eyebrow: { type: String, default: null },
});
</script>

<template>
    <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <header
            v-if="title || eyebrow || $slots.actions"
            class="flex items-center justify-between gap-3 border-b border-slate-100 px-6 py-4"
        >
            <div>
                <p v-if="eyebrow" class="text-xs font-semibold uppercase tracking-widest text-slate-500">
                    {{ eyebrow }}
                </p>
                <h3 v-if="title" class="font-display text-lg font-semibold text-slate-900">{{ title }}</h3>
            </div>
            <div v-if="$slots.actions" class="flex items-center gap-2"><slot name="actions" /></div>
        </header>
        <div class="p-6"><slot /></div>
    </section>
</template>
