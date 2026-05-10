<script setup>
import { computed } from 'vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    error: { type: String, default: '' },
    help: { type: String, default: '' },
    required: { type: Boolean, default: false },
});

const helpId = computed(() => (props.help ? `${props.id}-help` : undefined));
const errorId = computed(() => (props.error ? `${props.id}-error` : undefined));
const describedBy = computed(() => [helpId.value, errorId.value].filter(Boolean).join(' ') || undefined);
</script>

<template>
    <div>
        <label :for="id" class="block text-sm font-medium text-slate-700" :class="{ 'is-required': required }">
            {{ label }}
        </label>

        <div class="mt-1">
            <slot :id="id" :described-by="describedBy" :invalid="Boolean(error)" />
        </div>

        <p v-if="help" :id="helpId" class="mt-1 text-xs leading-5 text-slate-500">{{ help }}</p>
        <InputError :id="errorId" class="mt-1" :message="error" />
    </div>
</template>

<style scoped>
.is-required::after {
    margin-left: 0.25rem;
    color: #e11d48;
    content: '*';
}
</style>
