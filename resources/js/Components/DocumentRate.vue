<script setup>
import { computed } from 'vue';

const props = defineProps({
    fee: { type: [Number, String], required: true },
    formula: { type: String, default: 'per_page' },
});

const amount = computed(() =>
    new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(props.fee)),
);
const unit = computed(
    () =>
        ({ flat: 'per copy', per_page: 'per page / copy', per_set: 'per set', per_5_copies: 'per 5 copies' })[
            props.formula
        ] || 'per page / copy',
);
</script>

<template>
    <span class="font-semibold tabular-nums">{{ amount }}</span>
    <span class="ml-1 text-sm text-slate-600">{{ unit }}</span>
</template>
