<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
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
        ref="input"
        v-model="model"
        class="rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors duration-200"
    />
</template>
