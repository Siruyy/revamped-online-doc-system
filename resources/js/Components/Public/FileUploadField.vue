<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    hint: { type: String, default: 'JPG, PNG, or PDF up to 5 MB.' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    missing: { type: Boolean, default: false },
});

const emit = defineEmits(['change']);
const selectedFileName = ref('');
const stateLabel = computed(() => {
    if (props.error) return 'Needs attention';
    if (selectedFileName.value) return 'Selected';
    if (props.missing) return 'Missing';
    if (props.required) return 'Required';

    return 'Optional';
});

function onChange(event) {
    const file = event.target.files?.[0] ?? null;
    selectedFileName.value = file?.name ?? '';
    emit('change', file);
}
</script>

<template>
    <div
        class="rounded-2xl border bg-white p-4 shadow-sm"
        :class="
            error
                ? 'border-rose-300 ring-1 ring-rose-100'
                : selectedFileName
                  ? 'border-emerald-300 ring-1 ring-emerald-100'
                  : missing
                    ? 'border-amber-300 ring-1 ring-amber-100'
                    : 'border-slate-200'
        "
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <label :for="id" class="block text-sm font-semibold text-slate-900">
                    {{ label }}
                    <span v-if="required" class="text-rose-600">*</span>
                </label>
                <p class="mt-1 text-xs leading-5 text-slate-500">{{ hint }}</p>
            </div>
            <span
                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                :class="
                    error
                        ? 'bg-rose-100 text-rose-700'
                        : selectedFileName
                          ? 'bg-emerald-100 text-emerald-700'
                          : missing
                            ? 'bg-amber-100 text-amber-800'
                            : 'bg-slate-100 text-slate-600'
                "
            >
                {{ stateLabel }}
            </span>
        </div>
        <input
            :id="id"
            type="file"
            accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
            class="mt-3 block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100"
            @change="onChange"
        />
        <p v-if="selectedFileName" class="mt-2 text-sm font-medium text-slate-800">
            {{ selectedFileName }}
            <span class="font-normal text-slate-500">selected. Choose another file to replace it.</span>
        </p>
        <p v-else-if="missing" class="mt-2 text-sm text-amber-800">This file is still needed before submission.</p>
        <p v-if="error" class="mt-2 text-sm text-rose-600">{{ error }}</p>
    </div>
</template>
