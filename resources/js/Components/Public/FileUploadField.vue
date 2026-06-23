<script setup>
defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    hint: { type: String, default: 'JPG, PNG, or PDF up to 5 MB.' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
});

const emit = defineEmits(['change']);

function onChange(event) {
    emit('change', event.target.files?.[0] ?? null);
}
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <label :for="id" class="block text-sm font-semibold text-slate-900">
            {{ label }}
            <span v-if="required" class="text-rose-600">*</span>
        </label>
        <p class="mt-1 text-xs leading-5 text-slate-500">{{ hint }}</p>
        <input
            :id="id"
            type="file"
            accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
            class="mt-3 block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100"
            :required="required"
            @change="onChange"
        />
        <p v-if="error" class="mt-2 text-sm text-rose-600">{{ error }}</p>
    </div>
</template>
