<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    documentTypes: { type: Array, required: true },
});

const createForm = useForm({
    name: '',
    description: '',
    category: '',
    fee: '',
    processing_days: 1,
    is_active: true,
});

const updateForms = reactive({});
props.documentTypes.forEach((type) => {
    updateForms[type.id] = {
        name: type.name,
        description: type.description,
        category: type.category,
        fee: type.fee,
        processing_days: type.processing_days,
        is_active: Boolean(type.is_active),
    };
});

const save = () => createForm.post(route('admin.document-types.store'));

const update = (typeId) => {
    router.patch(route('admin.document-types.update', typeId), updateForms[typeId]);
};

const destroyType = (typeId) => {
    router.delete(route('admin.document-types.destroy', typeId));
};
</script>

<template>
    <Head title="Document Types" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Document Types</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Create Document Type</h3>
                <form class="mt-3 grid gap-3 md:grid-cols-2" @submit.prevent="save">
                    <input v-model="createForm.name" type="text" placeholder="Name" class="rounded-md border-slate-300 text-sm shadow-sm" />
                    <input v-model="createForm.category" type="text" placeholder="Category" class="rounded-md border-slate-300 text-sm shadow-sm" />
                    <input v-model="createForm.fee" type="number" min="0" step="0.01" placeholder="Fee" class="rounded-md border-slate-300 text-sm shadow-sm" />
                    <input v-model="createForm.processing_days" type="number" min="1" max="365" class="rounded-md border-slate-300 text-sm shadow-sm" />
                    <textarea v-model="createForm.description" rows="2" placeholder="Description" class="rounded-md border-slate-300 text-sm shadow-sm md:col-span-2" />
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input v-model="createForm.is_active" type="checkbox" class="rounded border-slate-300 text-indigo-600 shadow-sm" />
                        Active
                    </label>
                    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 md:col-span-2">Create</button>
                </form>
            </section>

            <section class="space-y-3">
                <article v-for="type in documentTypes" :key="type.id" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <form
                        class="grid gap-3 md:grid-cols-2"
                        @submit.prevent="update(type.id)"
                    >
                        <input v-model="updateForms[type.id].name" type="text" class="rounded-md border-slate-300 text-sm shadow-sm" />
                        <input v-model="updateForms[type.id].category" type="text" class="rounded-md border-slate-300 text-sm shadow-sm" />
                        <input v-model="updateForms[type.id].fee" type="number" step="0.01" min="0" class="rounded-md border-slate-300 text-sm shadow-sm" />
                        <input v-model="updateForms[type.id].processing_days" type="number" min="1" max="365" class="rounded-md border-slate-300 text-sm shadow-sm" />
                        <textarea v-model="updateForms[type.id].description" rows="2" class="rounded-md border-slate-300 text-sm shadow-sm md:col-span-2" />
                        <div class="flex items-center justify-between md:col-span-2">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input v-model="updateForms[type.id].is_active" type="checkbox" class="rounded border-slate-300 text-indigo-600 shadow-sm" />
                                Active
                            </label>
                            <div class="flex items-center gap-2">
                                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500">Save</button>
                                <button type="button" class="rounded-md bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500" @click="destroyType(type.id)">
                                    Delete / Disable
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 md:col-span-2">Linked requests: {{ type.document_requests_count }}</p>
                    </form>
                </article>
            </section>
        </div>
    </StaffLayout>
</template>
