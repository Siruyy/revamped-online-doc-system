<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    documentTypeGroups: {
        type: Object,
        required: true,
    },
    pendingRequestExists: {
        type: Boolean,
        required: true,
    },
});

const form = useForm({
    document_ids: [],
    purpose: '',
});

const allDocumentTypes = computed(() => Object.values(props.documentTypeGroups).flat());
const totalFee = computed(() =>
    allDocumentTypes.value
        .filter((item) => form.document_ids.includes(item.id))
        .reduce((sum, item) => sum + Number(item.fee), 0)
        .toFixed(2),
);

const submit = () => {
    form.post(route('student.requests.store'));
};
</script>

<template>
    <Head title="New Request" />

    <StudentLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Submit Document Request</h2>
        </template>

        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div
                v-if="pendingRequestExists"
                class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700"
            >
                You already have an active request. Submit is disabled until it is resolved.
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Select Document Types</h3>

                    <div
                        v-for="(documents, category) in documentTypeGroups"
                        :key="category"
                        class="mt-4 rounded-md border border-slate-200 p-4"
                    >
                        <p class="text-sm font-semibold text-slate-800">{{ category }}</p>
                        <label
                            v-for="documentType in documents"
                            :key="documentType.id"
                            class="mt-3 flex cursor-pointer items-start gap-3 rounded-md border border-slate-200 p-3 hover:bg-slate-50"
                        >
                            <input
                                v-model="form.document_ids"
                                :value="documentType.id"
                                type="checkbox"
                                class="mt-1 rounded border-slate-300 text-indigo-600 shadow-sm"
                            />
                            <div>
                                <p class="font-medium text-slate-900">{{ documentType.name }}</p>
                                <p class="text-xs text-slate-500">{{ documentType.description }}</p>
                                <p class="mt-1 text-xs text-slate-600">
                                    Fee: PHP {{ Number(documentType.fee).toFixed(2) }} | Processing: {{ documentType.processing_days }}
                                    day(s)
                                </p>
                            </div>
                        </label>
                    </div>

                    <InputError class="mt-3" :message="form.errors.document_ids" />
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <label for="purpose" class="text-sm font-semibold uppercase tracking-wide text-slate-600">Purpose (Optional)</label>
                    <textarea
                        id="purpose"
                        v-model="form.purpose"
                        rows="4"
                        class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        maxlength="500"
                    />
                    <InputError class="mt-2" :message="form.errors.purpose" />
                </section>

                <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm text-slate-600">
                        Selected: <span class="font-semibold text-slate-900">{{ form.document_ids.length }}</span> |
                        Total: <span class="font-semibold text-slate-900">PHP {{ totalFee }}</span>
                    </p>
                    <PrimaryButton :disabled="form.document_ids.length === 0 || pendingRequestExists || form.processing">
                        Submit Request
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </StudentLayout>
</template>
