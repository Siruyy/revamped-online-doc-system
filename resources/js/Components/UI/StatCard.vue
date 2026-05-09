<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    icon: { type: [Function, Object], default: null },
    tone: { type: String, default: 'brand' },
    href: { type: String, default: null },
    cta: { type: String, default: null },
});

const tones = {
    brand: { bg: 'bg-brand-100', fg: 'text-brand-700' },
    success: { bg: 'bg-emerald-100', fg: 'text-emerald-700' },
    warning: { bg: 'bg-amber-100', fg: 'text-amber-700' },
    danger: { bg: 'bg-rose-100', fg: 'text-rose-700' },
    info: { bg: 'bg-sky-100', fg: 'text-sky-700' },
    neutral: { bg: 'bg-slate-100', fg: 'text-slate-700' },
};

const tone = computed(() => tones[props.tone] ?? tones.brand);
</script>

<template>
    <component
        :is="href ? 'a' : 'div'"
        :href="href || undefined"
        class="block rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition"
        :class="href ? 'hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-brand-500' : ''"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate text-xs uppercase tracking-wider text-slate-500">{{ label }}</p>
                <p class="mt-2 text-3xl font-display font-bold text-slate-900">{{ value }}</p>
            </div>
            <div v-if="icon" :class="['rounded-xl p-2.5', tone.bg, tone.fg]" aria-hidden="true">
                <component :is="icon" class="h-5 w-5" />
            </div>
        </div>
        <p v-if="cta" :class="['mt-3 text-xs font-semibold', tone.fg]">{{ cta }} →</p>
    </component>
</template>
