<script setup>
import FileUpload from '@/Components/FileUpload.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { CheckBadgeIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    clearance: {
        type: Object,
        default: null,
    },
    signatories: { type: Array, required: true },
});

const form = useForm({
    clearance_file: null,
});

const statuses = computed(() => {
    if (!props.clearance) return [];

    return props.signatories.map((signatory) => ({
        key: signatory.role,
        label: signatory.label,
        status: props.clearance[signatory.status],
        signer: props.clearance[signatory.signer_payload]?.fullname,
        signedAt: props.clearance[signatory.signed_at],
        remarks: props.clearance[signatory.remarks],
    }));
});

const submit = () => {
    form.post(route('student.clearance.submit'), { forceFormData: true });
};

const statusTone = (status) => {
    if (['cleared', 'completed', 'approved'].includes(status)) return 'success';
    if (['denied', 'rejected'].includes(status)) return 'danger';
    if (['pending', 'in_progress'].includes(status)) return 'warning';

    return 'neutral';
};

const statusLabel = (status) => status?.replaceAll('_', ' ') || 'N/A';
</script>

<template>
    <Head title="Clearance Status" />

    <StudentLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Clearance Status</h2>
        </template>

        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <EmptyState
                v-if="!clearance"
                title="No clearance record yet"
                description="Your clearance status will appear after an approved request requires department signing."
                :icon="CheckBadgeIcon"
            />

            <template v-else>
                <section class="grid gap-4 md:grid-cols-2">
                    <article
                        v-for="item in statuses"
                        :key="item.key"
                        class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <p class="text-sm font-semibold text-slate-700">{{ item.label }}</p>
                        <p class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                            Status:
                            <StatusBadge :tone="statusTone(item.status)" :label="statusLabel(item.status)" />
                        </p>
                        <p class="text-xs text-slate-500">Cleared by: {{ item.signer || 'N/A' }}</p>
                        <p class="text-xs text-slate-500">Date signed: {{ item.signedAt || 'N/A' }}</p>
                        <p v-if="item.remarks" class="mt-1 text-xs text-rose-600">Remarks: {{ item.remarks }}</p>
                    </article>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-600">
                        Overall Status:
                        <StatusBadge
                            :tone="statusTone(clearance.overall_status)"
                            :label="statusLabel(clearance.overall_status)"
                        />
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
