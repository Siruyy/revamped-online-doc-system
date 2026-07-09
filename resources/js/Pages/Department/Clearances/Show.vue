<script setup>
import InputError from '@/Components/InputError.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    clearance: { type: Object, required: true },
    department: { type: String, required: true },
    currentSignatory: { type: Object, required: true },
    signatories: { type: Array, required: true },
});

const page = usePage();
const banner = computed(() => page.props.flash?.banner ?? null);
const deptStatusKey = computed(() => `${props.department}_status`);
const deptRemarksKey = computed(() => `${props.department}_remarks`);
const canAct = computed(() => props.clearance[deptStatusKey.value] === 'pending');
const signatoryCards = computed(() =>
    props.signatories.map((signatory) => ({
        ...signatory,
        statusValue: props.clearance[signatory.status],
        remarksValue: props.clearance[signatory.remarks],
        signedAtValue: props.clearance[signatory.signed_at],
        signerName: props.clearance[signatory.signer_payload]?.fullname,
    })),
);
const isPublicClearance = computed(() => !props.clearance.user_id);
const requestRequirements = computed(() => props.clearance.document_request?.requirements ?? []);

const signForm = useForm({ remarks: '' });
const denyForm = useForm({ remarks: '' });

const submitSign = () => signForm.post(route('department.clearances.sign', props.clearance.id));
const submitDeny = () => denyForm.post(route('department.clearances.deny', props.clearance.id));

const statusTone = (status) => {
    if (['cleared', 'completed', 'approved'].includes(status)) return 'success';
    if (['denied', 'rejected'].includes(status)) return 'danger';
    if (['pending', 'in_progress'].includes(status)) return 'warning';

    return 'neutral';
};

const statusLabel = (status) => status?.replaceAll('_', ' ') || 'N/A';
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
const requestorStudentId = computed(
    () => props.clearance.user?.student_id || props.clearance.document_request?.requester_student_id || 'N/A',
);
const signingSummary = computed(() => ({
    reference: props.clearance.document_request?.reference_no || 'N/A',
    purpose: props.clearance.document_request?.purpose || 'N/A',
    requestor: requestorName.value,
    intake: isPublicClearance.value ? 'Public no-login request' : 'Legacy student clearance',
}));
</script>

<template>
    <Head title="Clearance detail" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">
                Clearance {{ clearance.document_request?.reference_no }}
            </h2>
        </template>

        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div
                v-if="banner"
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
            >
                {{ banner }}
            </div>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Requestor</h3>
                    <span
                        class="rounded-full px-3 py-1 text-xs font-semibold"
                        :class="isPublicClearance ? 'bg-sky-100 text-sky-800' : 'bg-slate-100 text-slate-700'"
                    >
                        {{ isPublicClearance ? 'Public no-login request' : 'Legacy student clearance' }}
                    </span>
                </div>
                <p class="mt-2 text-sm text-slate-700">{{ requestorName }} ({{ requestorEmail }})</p>
                <p class="text-sm text-slate-700">Course: {{ requestorCourse }} · Year {{ requestorYear }}</p>
                <p class="text-sm text-slate-700">Student ID: {{ requestorStudentId }}</p>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Request</h3>
                <p class="mt-2 text-sm text-slate-700">Reference: {{ clearance.document_request?.reference_no }}</p>
                <p class="text-sm text-slate-700">Purpose: {{ clearance.document_request?.purpose || '—' }}</p>
                <div
                    v-if="isPublicClearance"
                    class="mt-4 rounded-lg bg-sky-50 px-3 py-2 text-xs leading-5 text-sky-900 ring-1 ring-sky-100"
                >
                    Public request clearances use the original request attachments below. The requestor does not need a
                    student account or a separate clearance upload.
                </div>
                <p v-if="clearance.uploaded_file_path" class="mt-2 text-sm">
                    <a
                        :href="route('files.clearance-supporting', clearance.id)"
                        class="font-semibold text-indigo-600 hover:text-indigo-500"
                    >
                        Download supporting file
                    </a>
                </p>
            </section>

            <section class="grid gap-4 md:grid-cols-2">
                <article
                    v-for="signatory in signatoryCards"
                    :key="signatory.role"
                    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ signatory.label }}</p>
                    <StatusBadge
                        class="mt-2"
                        :tone="statusTone(signatory.statusValue)"
                        :label="statusLabel(signatory.statusValue)"
                    />
                    <p class="mt-2 text-xs text-slate-500">Cleared by: {{ signatory.signerName || 'N/A' }}</p>
                    <p class="text-xs text-slate-500">Date signed: {{ signatory.signedAtValue || 'N/A' }}</p>
                    <p v-if="signatory.remarksValue" class="text-xs text-rose-600">
                        {{ signatory.remarksValue }}
                    </p>
                </article>
            </section>

            <section v-if="canAct" class="space-y-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">
                    Your department ({{ department }})
                    <span v-if="currentSignatory.label">- {{ currentSignatory.label }}</span>
                </h3>
                <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-700 ring-1 ring-slate-100">
                    <p class="font-semibold text-slate-950">What you are signing</p>
                    <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Reference</dt>
                            <dd class="font-mono text-slate-900">{{ signingSummary.reference }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Intake</dt>
                            <dd class="text-slate-900">{{ signingSummary.intake }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Requestor</dt>
                            <dd class="text-slate-900">{{ signingSummary.requestor }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Purpose</dt>
                            <dd class="text-slate-900">{{ signingSummary.purpose }}</dd>
                        </div>
                    </dl>
                </div>
                <div v-if="isPublicClearance" class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Request attachments</p>
                    <ul v-if="requestRequirements.length > 0" class="mt-2 divide-y divide-slate-100 rounded-lg border">
                        <li
                            v-for="requirement in requestRequirements"
                            :key="requirement.id"
                            class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-sm"
                        >
                            <div>
                                <p class="font-medium text-slate-800">{{ requirement.label }}</p>
                                <p class="text-xs capitalize text-slate-500">
                                    {{ requirement.status?.replaceAll('_', ' ') }}
                                </p>
                            </div>
                            <a
                                v-if="requirement.file_path"
                                :href="route('files.request-requirement', requirement.id)"
                                class="font-semibold text-indigo-600 hover:text-indigo-500"
                            >
                                Preview file
                            </a>
                        </li>
                    </ul>
                    <p v-else class="mt-2 rounded-lg bg-slate-50 p-3 text-sm text-slate-500">
                        No request attachments were submitted for this document type.
                    </p>
                </div>
                <p class="text-sm text-slate-600">
                    Current status:
                    <StatusBadge
                        :tone="statusTone(clearance[deptStatusKey])"
                        :label="statusLabel(clearance[deptStatusKey])"
                    />
                </p>
                <p v-if="clearance[deptRemarksKey]" class="text-xs text-slate-500">
                    Remarks: {{ clearance[deptRemarksKey] }}
                </p>

                <form class="space-y-2 border-t border-slate-100 pt-4" @submit.prevent="submitSign">
                    <label class="text-xs font-semibold uppercase text-slate-500"
                        >Mark cleared (optional remarks)</label
                    >
                    <textarea
                        v-model="signForm.remarks"
                        rows="2"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm"
                    />
                    <InputError :message="signForm.errors.sign" />
                    <InputError :message="signForm.errors.remarks" />
                    <button
                        type="submit"
                        class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50"
                        :disabled="signForm.processing"
                    >
                        Mark as cleared
                    </button>
                </form>

                <form class="space-y-2 border-t border-slate-100 pt-4" @submit.prevent="submitDeny">
                    <label class="text-xs font-semibold uppercase text-slate-500"
                        >Deny (remarks required, min 10 characters)</label
                    >
                    <p class="text-xs leading-5 text-slate-500">
                        Denial remarks are visible through public tracking and may stop the request until registrar
                        staff follow up.
                    </p>
                    <textarea
                        v-model="denyForm.remarks"
                        rows="2"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm"
                    />
                    <InputError :message="denyForm.errors.deny" />
                    <InputError :message="denyForm.errors.remarks" />
                    <button
                        type="submit"
                        class="rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500 disabled:opacity-50"
                        :disabled="denyForm.processing"
                    >
                        Deny with remarks
                    </button>
                </form>
            </section>

            <section v-else class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                You cannot act on this clearance for your department in its current state.
            </section>
        </div>
    </StaffLayout>
</template>
