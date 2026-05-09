<script setup>
import { computed } from 'vue';

const props = defineProps({
    tone: { type: String, default: 'neutral' },
    label: { type: String, required: true },
    size: { type: String, default: 'sm' },
});

const palette = {
    success: 'bg-emerald-100 text-emerald-800',
    warning: 'bg-amber-100 text-amber-800',
    danger: 'bg-rose-100 text-rose-800',
    info: 'bg-sky-100 text-sky-800',
    neutral: 'bg-slate-100 text-slate-700',
    brand: 'bg-brand-100 text-brand-800',
};

const sizes = {
    xs: 'px-2 py-0.5 text-[10px]',
    sm: 'px-2.5 py-0.5 text-xs',
    md: 'px-3 py-1 text-sm',
};

const classes = computed(() => [
    'inline-flex items-center rounded-full font-semibold capitalize',
    palette[props.tone] ?? palette.neutral,
    sizes[props.size] ?? sizes.sm,
]);
</script>

<template>
    <span :class="classes">
        <slot>{{ label }}</slot>
    </span>
</template>
