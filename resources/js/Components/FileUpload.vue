<script setup>
import InputError from '@/Components/InputError.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: [File, null],
        default: null,
    },
    error: {
        type: String,
        default: '',
    },
    accept: {
        type: String,
        default: 'image/jpeg,image/png,application/pdf',
    },
    label: {
        type: String,
        default: 'Upload file',
    },
});

const emit = defineEmits(['update:modelValue']);
const isDragging = ref(false);

const previewUrl = computed(() => {
    if (!props.modelValue) return null;

    if (!props.modelValue.type.startsWith('image/')) return null;

    return URL.createObjectURL(props.modelValue);
});

const onFileSelect = (event) => {
    const file = event.target.files?.[0] ?? null;
    emit('update:modelValue', file);
};

const onDrop = (event) => {
    isDragging.value = false;
    const file = event.dataTransfer?.files?.[0] ?? null;
    emit('update:modelValue', file);
};
</script>

<template>
    <div class="space-y-2">
        <label class="block text-sm font-medium text-slate-700">{{ label }}</label>

        <div
            class="rounded-lg border-2 border-dashed p-4 transition"
            :class="isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-slate-300 bg-white'"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
        >
            <input class="block w-full text-sm text-slate-600" type="file" :accept="accept" @change="onFileSelect" />
            <p v-if="modelValue" class="mt-2 text-xs text-slate-600">
                Selected: {{ modelValue.name }} ({{ Math.ceil(modelValue.size / 1024) }} KB)
            </p>
            <img v-if="previewUrl" :src="previewUrl" alt="Preview" class="mt-2 h-24 w-24 rounded-md object-cover" />
        </div>

        <InputError :message="error" />
    </div>
</template>
