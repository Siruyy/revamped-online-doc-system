<script setup>
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    clearance: { type: Object, required: true },
    signatories: { type: Array, required: true },
});

const canDownloadPdf = computed(() => props.clearance.overall_status === 'completed' && props.clearance.pdf_path);
const isPublicClearance = computed(() => !props.clearance.user_id);
const requestorName = computed(
    () => props.clearance.user?.fullname || props.clearance.document_request?.requester_name || 'Public requestor',
);
const requestorEmail = computed(
    () => props.clearance.user?.email || props.clearance.document_request?.requester_email || 'No email provided',
);
const requestorCourse = computed(
    () => props.clearance.user?.course || props.clearance.document_request?.requester_course || 'N/A',
);
const requestorYear = computed(
    () => props.clearance.user?.year_level || props.clearance.document_request?.requester_year_level || 'N/A',
);
const signatoryCards = computed(() =>
    props.signatories.map((signatory) => ({
        ...signatory,
        statusValue: props.clearance[signatory.status],
        remarksValue: props.clearance[signatory.remarks],
        signedAtValue: props.clearance[signatory.signed_at],
        signerName: props.clearance[signatory.signer_payload]?.fullname,
    })),
);

const statusTone = (status) => {
    if (['cleared', 'completed', 'approved'].includes(status)) return 'success';
    if (['denied', 'rejected'].includes(status)) return 'danger';
    if (['pending', 'in_progress'].includes(status)) return 'warning';

    return 'neutral';
};

const statusLabel = (status) => status?.replaceAll('_', ' ') || 'N/A';
</script>

<template>
    <Head :title="`Clearance ${clearance.document_request?.reference_no}`" />

    <StaffLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-slate-900">
                    Clearance {{ clearance.document_request?.reference_no }}
                </h2>
                <a
                    v-if="canDownloadPdf"
                    :href="route('files.clearance-pdf', clearance.id)"
                    class="rounded-md border border-emerald-300 px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50"
                >
                    Download PDF
                </a>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Student</h3>
                    <span
                        v-if="isPublicClearance"
                        class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-800"
                    >
                        Public requestor
                    </span>
                </div>
                <p class="mt-2 text-sm text-slate-700">{{ requestorName }} ({{ requestorEmail }})</p>
                <p class="text-sm text-slate-700">Course: {{ requestorCourse }} | Year {{ requestorYear }}</p>
                <p class="text-sm text-slate-700">Request stage: {{ clearance.document_request?.processing_stage }}</p>
                <p class="text-sm text-slate-700">Purpose: {{ clearance.document_request?.purpose || 'N/A' }}</p>
                <div
                    v-if="isPublicClearance"
                    class="mt-4 rounded-lg bg-sky-50 px-3 py-2 text-xs leading-5 text-sky-900 ring-1 ring-sky-100"
                >
                    This clearance belongs to a no-login public request. Admin and SuperAdmin users may download the
                    private clearance PDF after completion; public tracking never exposes the PDF link.
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2">
                <article
                    v-for="signatory in signatoryCards"
                    :key="signatory.role"
                    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <p class="text-sm font-semibold text-slate-700">{{ signatory.label }}</p>
                    <StatusBadge
                        class="mt-2"
                        :tone="statusTone(signatory.statusValue)"
                        :label="statusLabel(signatory.statusValue)"
                    />
                    <p class="text-xs text-slate-500">Cleared by: {{ signatory.signerName || 'N/A' }}</p>
                    <p class="text-xs text-slate-500">Date signed: {{ signatory.signedAtValue || 'N/A' }}</p>
                    <p v-if="signatory.remarksValue" class="text-xs text-rose-600">
                        Remarks: {{ signatory.remarksValue }}
                    </p>
                </article>
            </section>
        </div>
    </StaffLayout>
</template>
