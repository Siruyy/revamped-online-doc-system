<script setup>
import DocumentRate from '@/Components/DocumentRate.vue';
import FormField from '@/Components/UI/FormField.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ documentTypes: { type: Array, required: true } });
const editing = ref(null);
const form = useForm({ fee: '' });

function edit(type) {
    editing.value = type.id;
    form.fee = type.fee;
    form.clearErrors();
}
function save() {
    form.patch(route('department.prices.update', editing.value), {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = null;
        },
    });
}
</script>

<template>
    <Head title="Price List" />
    <StaffLayout>
        <template #header
            ><PageHeader
                title="Price List"
                subtitle="Update document rates shown on the public price list. Confirmed request quotes keep their existing amounts."
        /></template>
        <div class="space-y-5">
            <Link
                :href="route('public.pricelist')"
                class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-brand-700 underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600"
                >View public price list</Link
            >
            <p v-if="!documentTypes.length" class="rounded-xl border border-slate-200 bg-white p-5 text-slate-600">
                No document types are available.
            </p>
            <article
                v-for="type in documentTypes"
                :key="type.id"
                class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="font-semibold text-slate-900">{{ type.name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ type.category }} ·
                            {{ type.is_active ? 'Publicly listed' : 'Inactive — hidden from public list' }}
                        </p>
                        <p class="mt-2"><DocumentRate :fee="type.fee" :formula="type.fee_formula || 'per_page'" /></p>
                    </div>
                    <button
                        type="button"
                        :disabled="form.processing"
                        class="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600"
                        :aria-label="`Edit price for ${type.name}`"
                        @click="edit(type)"
                    >
                        Edit price
                    </button>
                </div>
                <form
                    v-if="editing === type.id"
                    class="mt-5 flex flex-wrap items-end gap-3 border-t border-slate-100 pt-5"
                    @submit.prevent="save"
                >
                    <FormField :id="`fee-${type.id}`" label="Fee (₱)" :error="form.errors.fee" required>
                        <template #default="{ id, describedBy, invalid }">
                            <input
                                :id="id"
                                v-model="form.fee"
                                type="number"
                                min="0"
                                max="999999.99"
                                step="0.01"
                                required
                                :aria-describedby="describedBy"
                                :aria-invalid="invalid ? 'true' : undefined"
                                class="min-h-11 w-full rounded-lg border-slate-300 sm:w-48"
                            />
                        </template>
                    </FormField>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="min-h-11 rounded-lg bg-brand-700 px-4 text-sm font-semibold text-white hover:bg-brand-600 disabled:opacity-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                    >
                        {{ form.processing ? 'Saving…' : 'Save price' }}
                    </button>
                    <button
                        type="button"
                        :disabled="form.processing"
                        class="min-h-11 rounded-lg px-4 text-sm font-semibold text-slate-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600"
                        @click="editing = null"
                    >
                        Cancel
                    </button>
                </form>
            </article>
        </div>
    </StaffLayout>
</template>
