<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    as: { type: String, default: 'button' },
    href: { type: String, default: null },
    label: { type: String, required: true },
    variant: { type: String, default: 'subtle' },
    type: { type: String, default: 'button' },
});

const variantClasses = {
    subtle: 'text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-brand-600',
    primary: 'bg-brand-600 text-white hover:bg-brand-500 focus-visible:outline-brand-600',
    danger: 'text-rose-700 hover:bg-rose-50 focus-visible:outline-rose-600',
};
</script>

<template>
    <Link
        v-if="href"
        :href="href"
        :aria-label="label"
        :class="[
            'inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
            variantClasses[variant] ?? variantClasses.subtle,
        ]"
    >
        <slot />
    </Link>

    <button
        v-else
        :type="type"
        :aria-label="label"
        :class="[
            'inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
            variantClasses[variant] ?? variantClasses.subtle,
        ]"
    >
        <slot />
    </button>
</template>
