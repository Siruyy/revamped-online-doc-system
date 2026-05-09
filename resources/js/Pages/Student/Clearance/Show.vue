<script setup>
import FileUpload from '@/Components/FileUpload.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    clearance: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    clearance_file: null,
});

const statuses = computed(() => {
    if (!props.clearance) return [];

    return [
        {
            key: 'teacher',
            label: 'Teacher',
            status: props.clearance.teacher_status,
            signer: props.clearance.teacher_signer?.fullname,
            signedAt: props.clearance.teacher_signed_at,
            remarks: props.clearance.teacher_remarks,
        },
        {
            key: 'dean',
            label: 'Dean',
            status: props.clearance.dean_status,
            signer: props.clearance.dean_signer?.fullname,
            signedAt: props.clearance.dean_signed_at,
            remarks: props.clearance.dean_remarks,
        },
        {
            key: 'accounting',
            label: 'Accounting',
            status: props.clearance.accounting_status,
            signer: props.clearance.accounting_signer?.fullname,
            signedAt: props.clearance.accounting_signed_at,
            remarks: props.clearance.accounting_remarks,
        },
        {
            key: 'sao',
            label: 'SAO',
            status: props.clearance.sao_status,
            signer: props.clearance.sao_signer?.fullname,
            signedAt: props.clearance.sao_signed_at,
            remarks: props.clearance.sao_remarks,
        },
    ];
});

const submit = () => {
    form.post(route('student.clearance.submit'), { forceFormData: true });
};
</script>

<template>
    <Head title="Clearance Status" />

    <StudentLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Clearance Status</h2>
        </template>

        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div
                v-if="!clearance"
                class="rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm"
            >
                No clearance record is available yet.
            </div>

            <template v-else>
                <section class="grid gap-4 md:grid-cols-2">
                    <article
                        v-for="item in statuses"
                        :key="item.key"
                        class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <p class="text-sm font-semibold text-slate-700">{{ item.label }}</p>
                        <p class="mt-2 text-sm">
                            Status:
                            <span class="font-semibold capitalize">{{ item.status }}</span>
                        </p>
                        <p class="text-xs text-slate-500">Signer: {{ item.signer || 'N/A' }}</p>
                        <p class="text-xs text-slate-500">Signed at: {{ item.signedAt || 'N/A' }}</p>
                        <p v-if="item.remarks" class="mt-1 text-xs text-rose-600">Remarks: {{ item.remarks }}</p>
                    </article>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-600">
                        Overall Status: <span class="capitalize text-slate-900">{{ clearance.overall_status }}</span>
                    </p>

                    <form class="mt-4 space-y-4" @submit.prevent="submit">
                        <FileUpload
                            v-model="form.clearance_file"
                            label="Submit clearance supporting file (optional)"
                            :error="form.errors.clearance_file"
                            accept="image/jpeg,image/png,application/pdf"
                        />
                        <PrimaryButton :disabled="form.processing">Upload File</PrimaryButton>
                    </form>

                    <div v-if="clearance.overall_status === 'completed'" class="mt-4">
                        <a
                            :href="route('student.clearance.download-pdf')"
                            class="inline-flex rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                        >
                            Download Clearance PDF
                        </a>
                    </div>
                </section>
            </template>
        </div>
    </StudentLayout>
</template>
