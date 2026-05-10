<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

defineProps({
    id: { type: String, default: undefined },
    invalid: { type: Boolean, default: false },
    describedBy: { type: String, default: undefined },
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        :id="id"
        ref="input"
        v-model="model"
        :aria-invalid="invalid ? 'true' : undefined"
        :aria-describedby="describedBy"
        class="rounded-lg border-slate-300 shadow-sm transition focus:border-brand-500 focus:ring-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500"
    />
</template>
