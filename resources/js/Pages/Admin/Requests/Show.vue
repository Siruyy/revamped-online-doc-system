<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ArrowTopRightOnSquareIcon,
    ArrowUturnLeftIcon,
    BoltIcon,
    CheckCircleIcon,
    ClipboardDocumentCheckIcon,
    ClockIcon,
    CreditCardIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    EyeIcon,
    PauseCircleIcon,
    PlayCircleIcon,
    TicketIcon,
    UserCircleIcon,
    XCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    request: { type: Object, required: true },
    batchRequests: { type: Array, required: true },
    policy: { type: Object, required: true },
});

const denyForm = useForm({ denial_reason: '' });
const approvePackageForm = useForm({});
const stageForm = useForm({ processing_stage: props.request.processing_stage || 'processing' });
const pauseForm = useForm({ reason: '' });
const rejectForm = useForm({ notes: '' });
const page = usePage();
const routeBase = computed(() => (page.props.auth?.user?.role === 'superadmin' ? 'superadmin' : 'admin'));

const docType = computed(() => props.request.document_type);
const payment = computed(() => props.request.payments?.[0]);
const clearance = computed(() => props.request.clearances?.[0]);
const claimSlip = computed(() => props.request.claim_slip);
const requirements = computed(() => props.request.requirements ?? []);
const requestItems = computed(() => {
    const items = props.request.items ?? [];

    if (items.length > 0) return items;

    return [
        {
            id: `legacy-${props.request.id}`,
            document_type: docType.value,
            copies: props.request.quantity || 1,
            page_count_snapshot: props.request.page_count || docType.value?.default_page_count || 1,
            fee_per_page_snapshot: props.request.fee_snapshot || 0,
            line_total: props.request.fee_snapshot || 0,
        },
    ];
});
const requestItemsTotal = computed(() =>
    requestItems.value.reduce((sum, item) => sum + Number(item.line_total || 0), 0),
);
const isPublicRequest = computed(() => props.request.intake_mode === 'public');
const allReqsValidated = computed(
    () => requirements.value.length === 0 || requirements.value.every((r) => r.status === 'validated'),
);
const paymentApproved = computed(() => payment.value?.status === 'approved');
const paymentPendingApproval = computed(() => payment.value?.status === 'pending_approval');

const canApprove = computed(() => !isPublicRequest.value && props.request.status === 'pending');
const canApprovePackage = computed(
    () =>
        isPublicRequest.value &&
        props.request.status === 'pending' &&
        allReqsValidated.value &&
        paymentPendingApproval.value,
);
const canDeny = computed(() => ['pending', 'approved'].includes(props.request.status));
const canUpdateStage = computed(() => props.request.status === 'approved');
const canRelease = computed(
    () => props.request.status === 'approved' && props.request.processing_stage === 'ready_for_pickup',
);
const clearanceReady = computed(() => !clearance.value || clearance.value.overall_status === 'completed');
const readinessItems = computed(() => [
    {
        label: 'Requirements',
        status: allReqsValidated.value ? 'Ready' : 'Needs validation',
        ready: allReqsValidated.value,
        detail:
            requirements.value.length === 0
                ? 'No attachments required.'
                : `${requirements.value.filter((r) => r.status === 'validated').length} of ${requirements.value.length} validated.`,
        blocker: 'Validate or reject every submitted requirement.',
    },
    {
        label: 'Payment',
        status: paymentApproved.value
            ? 'Approved'
            : paymentPendingApproval.value
              ? 'Receipt submitted'
              : 'Needs receipt',
        ready: Boolean(paymentApproved.value || paymentPendingApproval.value),
        detail: payment.value
            ? `${payment.value.status?.replaceAll('_', ' ')} · ${fmtPeso(payment.value.total_amount)}`
            : 'No payment record.',
        blocker: 'A submitted payment receipt is required before approval.',
    },
    {
        label: 'Clearance',
        status: !clearance.value ? 'Not required' : clearance.value.overall_status?.replaceAll('_', ' '),
        ready: clearanceReady.value,
        detail: !clearance.value
            ? 'This request does not have a clearance record.'
            : 'Department signatures are tracked internally.',
        blocker: 'Clearance is still in progress.',
    },
    {
        label: 'Stage',
        status: props.request.processing_stage?.replaceAll('_', ' ') || 'not started',
        ready: props.request.status !== 'denied',
        detail: `Request status is ${props.request.status?.replaceAll('_', ' ')}.`,
        blocker: 'Denied requests cannot continue without a new submission.',
    },
]);
const readinessBlockers = computed(() =>
    readinessItems.value.filter((item) => !item.ready).map((item) => item.blocker),
);

const showDeny = ref(false);
const showPause = ref(false);
const rejectReqId = ref(null);
const previewFile = ref(null);

const previewIsImage = computed(() => {
    if (!previewFile.value?.path) return false;

    return /\.(jpe?g|png|gif|webp)$/i.test(previewFile.value.path);
});

function openFilePreview(file) {
    previewFile.value = file;
}

function closeFilePreview() {
    previewFile.value = null;
}

function approve() {
    router.post(route(`${routeBase.value}.requests.approve`, props.request.id));
}

function approvePackage() {
    approvePackageForm.post(route(`${routeBase.value}.requests.approve-with-payment`, props.request.id));
}

function deny() {
    const routeName = isPublicRequest.value
        ? `${routeBase.value}.requests.deny-with-payment`
        : `${routeBase.value}.requests.deny`;

    denyForm.post(route(routeName, props.request.id), {
        onSuccess: () => {
            showDeny.value = false;
            denyForm.reset();
        },
    });
}

function updateStage() {
    stageForm.post(route(`${routeBase.value}.requests.stage`, props.request.id));
}

function release() {
    if (!window.confirm('Mark this request as released? This will close out the request.')) return;
    router.post(route(`${routeBase.value}.requests.release`, props.request.id));
}

function validateReq(req) {
    router.post(
        route(`${routeBase.value}.requests.requirements.validate`, [props.request.id, req.id]),
        {},
        { preserveScroll: true },
    );
}

function startReject(reqId) {
    rejectReqId.value = reqId;
    rejectForm.reset();
}

function rejectReq(req) {
    if (!rejectForm.notes.trim()) return;
    rejectForm.post(route(`${routeBase.value}.requests.requirements.reject`, [props.request.id, req.id]), {
        preserveScroll: true,
        onSuccess: () => {
            rejectReqId.value = null;
            rejectForm.reset();
        },
    });
}

function pauseSla() {
    pauseForm.post(route(`${routeBase.value}.requests.sla.pause`, props.request.id), {
        onSuccess: () => {
            showPause.value = false;
            pauseForm.reset();
        },
    });
}

function resumeSla() {
    router.post(route(`${routeBase.value}.requests.sla.resume`, props.request.id), {}, { preserveScroll: true });
}

function markHd() {
    if (!window.confirm('Confirm receipt of Honorable Dismissal? This starts the 14-day SLA.')) return;
    router.post(route(`${routeBase.value}.requests.hd`, props.request.id), {}, { preserveScroll: true });
}

function statusTone(status) {
    return (
        {
            pending: 'bg-amber-100 text-amber-800',
            approved: 'bg-sky-100 text-sky-800',
            completed: 'bg-emerald-100 text-emerald-800',
            denied: 'bg-rose-100 text-rose-800',
            cancelled: 'bg-slate-100 text-slate-600',
        }[status] ?? 'bg-slate-100 text-slate-600'
    );
}

function reqTone(status) {
    return (
        {
            missing: 'bg-slate-100 text-slate-700',
            submitted: 'bg-amber-100 text-amber-800',
            validated: 'bg-emerald-100 text-emerald-800',
            rejected: 'bg-rose-100 text-rose-800',
        }[status] ?? 'bg-slate-100 text-slate-600'
    );
}

function fmtDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
}

function fmtDateOnly(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function fmtPeso(value) {
    return `₱${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
}
</script>

<template>
    <Head :title="`Request ${request.reference_no}`" />

    <StaffLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <Link
                        :href="route(`${routeBase}.requests.index`)"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline"
                    >
                        <ArrowUturnLeftIcon class="h-3 w-3" /> Back to queue
                    </Link>
                    <h2 class="mt-1 text-2xl font-display font-bold text-slate-900">{{ docType?.name }}</h2>
                    <p class="text-xs text-slate-500">
                        Ref. <span class="font-mono">{{ request.reference_no }}</span>
                    </p>
                </div>
                <span
                    :class="[
                        'inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold capitalize',
                        statusTone(request.status),
                    ]"
                >
                    {{ request.status }} · {{ request.processing_stage?.replaceAll('_', ' ') }}
                </span>
            </div>
        </template>

        <div class="mx-auto grid max-w-7xl gap-6 px-4 pb-12 sm:px-6 lg:grid-cols-3 lg:px-8">
            <!-- Main column -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Requestor card -->
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start gap-4">
                        <div class="rounded-xl bg-brand-100 p-3 text-brand-700">
                            <UserCircleIcon class="h-7 w-7" />
                        </div>
                        <div class="flex-1">
                            <h3 v-if="!isPublicRequest" class="font-display text-lg font-semibold text-slate-900">
                                {{ request.user?.fullname }}
                            </h3>
                            <h3 v-else class="font-display text-lg font-semibold text-slate-900">
                                {{ request.requester_name }}
                            </h3>
                            <p v-if="!isPublicRequest" class="text-xs text-slate-500">
                                {{ request.user?.email }} · {{ request.user?.contact_number || '—' }}
                            </p>
                            <p v-else class="text-xs text-slate-500">
                                {{ request.requester_email || 'No email provided' }} ·
                                {{ request.requester_contact_number }}
                            </p>
                            <dl class="mt-3 grid grid-cols-1 gap-x-6 gap-y-1 text-xs sm:grid-cols-3">
                                <template v-if="!isPublicRequest">
                                    <div>
                                        <dt class="text-slate-500">Student ID</dt>
                                        <dd class="font-mono text-slate-900">{{ request.user?.student_id }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-500">Course / Year</dt>
                                        <dd class="text-slate-900">
                                            {{ request.user?.course }} · Y{{ request.user?.year_level }}
                                        </dd>
                                    </div>
                                </template>
                                <div v-if="!isPublicRequest">
                                    <dt class="text-slate-500">Academic status</dt>
                                    <dd class="capitalize text-slate-900">
                                        {{ (request.user?.academic_status || 'enrolled').replaceAll('_', ' ') }}
                                    </dd>
                                </div>
                                <div v-else>
                                    <dt class="text-slate-500">Intake</dt>
                                    <dd class="capitalize text-slate-900">Public request</dd>
                                </div>
                                <template v-if="isPublicRequest">
                                    <div>
                                        <dt class="text-slate-500">Student ID</dt>
                                        <dd class="font-mono text-slate-900">
                                            {{ request.requester_student_id || 'Optional - not provided' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-500">Course / Year</dt>
                                        <dd class="text-slate-900">
                                            {{ request.requester_course || '—' }} · Y{{
                                                request.requester_year_level || '?'
                                            }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-500">Graduation / last semester</dt>
                                        <dd class="text-slate-900">
                                            {{ request.requester_graduation_or_last_sem || '—' }}
                                        </dd>
                                    </div>
                                </template>
                            </dl>
                            <div
                                v-if="isPublicRequest"
                                class="mt-4 rounded-lg bg-sky-50 px-3 py-2 text-xs leading-5 text-sky-900 ring-1 ring-sky-100"
                            >
                                Public requestors do not need student accounts. Review the submitted request details,
                                attachments, and payment receipt here; they will track updates with the reference
                                number.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Readiness -->
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="font-display text-lg font-semibold text-slate-900">Readiness</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                Check the request package before approval, clearance, pickup, or release actions.
                            </p>
                        </div>
                        <span
                            class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold"
                            :class="
                                readinessBlockers.length
                                    ? 'bg-amber-100 text-amber-800'
                                    : 'bg-emerald-100 text-emerald-800'
                            "
                        >
                            {{ readinessBlockers.length ? `${readinessBlockers.length} blocker(s)` : 'Ready' }}
                        </span>
                    </div>
                    <div class="mt-5 grid gap-3 md:grid-cols-4">
                        <article
                            v-for="item in readinessItems"
                            :key="item.label"
                            class="rounded-xl border p-4 text-sm"
                            :class="
                                item.ready
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
                                    : 'border-amber-200 bg-amber-50 text-amber-900'
                            "
                        >
                            <p class="text-xs font-semibold uppercase tracking-wide opacity-75">{{ item.label }}</p>
                            <p class="mt-2 font-semibold capitalize">{{ item.status }}</p>
                            <p class="mt-1 text-xs leading-5">{{ item.detail }}</p>
                        </article>
                    </div>
                    <div
                        v-if="readinessBlockers.length"
                        class="mt-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-100"
                    >
                        <p class="font-semibold">Approval blockers</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            <li v-for="blocker in readinessBlockers" :key="blocker">{{ blocker }}</li>
                        </ul>
                    </div>
                </section>

                <!-- Request details -->
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="mb-4 flex items-center gap-2">
                        <DocumentTextIcon class="h-5 w-5 text-brand-600" />
                        <h3 class="font-display text-lg font-semibold text-slate-900">Request Details</h3>
                    </div>
                    <div class="mb-5 overflow-hidden rounded-xl border border-slate-200">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-600">
                                Requested Documents
                            </h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-white text-left text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Document</th>
                                        <th class="px-4 py-3 font-semibold">Qty</th>
                                        <th class="px-4 py-3 font-semibold">Pages</th>
                                        <th class="px-4 py-3 text-right font-semibold">Unit fee</th>
                                        <th class="px-4 py-3 text-right font-semibold">Line total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr v-for="item in requestItems" :key="item.id">
                                        <td class="px-4 py-3 font-medium text-slate-900">
                                            {{ item.document_type?.name || docType?.name || 'Document request' }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ item.copies || 1 }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ item.page_count_snapshot || '—' }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-slate-700">
                                            {{ fmtPeso(item.fee_per_page_snapshot) }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ fmtPeso(item.line_total) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="border-t border-slate-200 bg-slate-50">
                                    <tr>
                                        <td
                                            colspan="4"
                                            class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500"
                                        >
                                            Total
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right font-display text-base font-bold text-slate-950"
                                        >
                                            {{ fmtPeso(requestItemsTotal || request.fee_snapshot) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <dl class="grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase text-slate-500">Category</dt>
                            <dd class="font-medium text-slate-900">{{ docType?.category }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase text-slate-500">Quantity</dt>
                            <dd class="font-medium text-slate-900">
                                {{ request.quantity
                                }}<span v-if="request.page_count"> · {{ request.page_count }} pages</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase text-slate-500">Fee</dt>
                            <dd class="font-semibold text-slate-900">{{ fmtPeso(request.fee_snapshot) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase text-slate-500">Submitted</dt>
                            <dd class="text-slate-900">{{ fmtDate(request.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase text-slate-500">Approved</dt>
                            <dd class="text-slate-900">{{ fmtDate(request.approved_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase text-slate-500">Expected release</dt>
                            <dd class="font-semibold text-slate-900">{{ fmtDateOnly(request.expected_release_on) }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase text-slate-500">Purpose</dt>
                            <dd class="italic text-slate-700">{{ request.purpose || '—' }}</dd>
                        </div>
                    </dl>

                    <div
                        v-if="request.status === 'denied' && request.denial_reason"
                        class="mt-4 flex items-start gap-2 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-800 ring-1 ring-rose-200"
                    >
                        <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 flex-none" />
                        <p><strong>Denied:</strong> {{ request.denial_reason }}</p>
                    </div>
                </section>

                <!-- Requirements -->
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <ClipboardDocumentCheckIcon class="h-5 w-5 text-brand-600" />
                            <h3 class="font-display text-lg font-semibold text-slate-900">Attachments</h3>
                        </div>
                        <span class="text-xs text-slate-500">
                            {{ requirements.filter((r) => r.status === 'validated').length }} /
                            {{ requirements.length }} validated
                        </span>
                    </div>
                    <div
                        v-if="requirements.length === 0"
                        class="mt-4 rounded-lg bg-slate-50 p-4 text-sm text-slate-500"
                    >
                        This document type does not require attachments.
                    </div>
                    <div
                        v-if="requirements.length > 0 && isPublicRequest"
                        class="mt-4 rounded-lg bg-sky-50 px-3 py-2 text-xs leading-5 text-sky-900 ring-1 ring-sky-100"
                    >
                        For public requests, these uploaded requirement files are the clearance review materials.
                        Department staff should not ask the requestor to create an account or upload a separate
                        clearance file.
                    </div>
                    <ul v-if="requirements.length > 0" class="mt-4 divide-y divide-slate-100">
                        <li v-for="req in requirements" :key="req.id" class="py-3">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-slate-900">{{ req.label }}</p>
                                    <p v-if="req.notes" class="mt-1 text-xs italic text-slate-500">
                                        Notes: {{ req.notes }}
                                    </p>
                                    <button
                                        v-if="req.file_path"
                                        type="button"
                                        class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline"
                                        @click="
                                            openFilePreview({
                                                title: req.label,
                                                url: route('files.request-requirement', req.id),
                                                path: req.file_path,
                                            })
                                        "
                                    >
                                        <EyeIcon class="h-3.5 w-3.5" /> Preview file
                                    </button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                            reqTone(req.status),
                                        ]"
                                    >
                                        {{ req.status }}
                                    </span>
                                    <template v-if="['submitted', 'rejected'].includes(req.status)">
                                        <button
                                            type="button"
                                            class="rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-500"
                                            @click="validateReq(req)"
                                        >
                                            Validate
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md border border-rose-200 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                                            @click="startReject(req.id)"
                                        >
                                            Reject
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div
                                v-if="rejectReqId === req.id"
                                class="mt-3 rounded-xl border border-rose-200 bg-rose-50/50 p-3"
                            >
                                <label class="block text-xs font-medium text-slate-700"
                                    >Reason for rejection (visible to student)</label
                                >
                                <textarea
                                    v-model="rejectForm.notes"
                                    rows="2"
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm"
                                />
                                <div class="mt-2 flex gap-2">
                                    <button
                                        type="button"
                                        class="rounded-md bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500"
                                        @click="rejectReq(req)"
                                    >
                                        Send back
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-md border border-slate-200 px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-50"
                                        @click="rejectReqId = null"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>

                <!-- Batch -->
                <section v-if="batchRequests.length" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">
                        Other requests in same batch
                    </h3>
                    <ul class="mt-3 divide-y divide-slate-100">
                        <li
                            v-for="item in batchRequests"
                            :key="item.id"
                            class="flex items-center justify-between py-2 text-sm"
                        >
                            <div>
                                <p class="font-mono text-xs text-slate-500">{{ item.reference_no }}</p>
                                <p class="font-medium text-slate-800">{{ item.document_type?.name }}</p>
                            </div>
                            <Link
                                :href="route(`${routeBase}.requests.show`, item.id)"
                                class="text-xs font-semibold text-brand-700 hover:underline"
                                >Open →</Link
                            >
                        </li>
                    </ul>
                </section>
            </div>

            <!-- Side panel -->
            <aside class="space-y-6">
                <!-- Quick actions -->
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:sticky lg:top-24">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">Decision</h3>
                    <div
                        v-if="isPublicRequest"
                        class="mt-3 rounded-lg bg-sky-50 px-3 py-2 text-xs leading-5 text-sky-900 ring-1 ring-sky-100"
                    >
                        Approve request + payment only after validating attachments and receipt. If any requested
                        document requires clearance, approval automatically starts the internal department clearance
                        workflow.
                    </div>
                    <div class="mt-3 flex flex-col gap-2">
                        <div
                            v-if="approvePackageForm.hasErrors"
                            class="rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700 ring-1 ring-rose-200"
                        >
                            {{
                                approvePackageForm.errors.request ||
                                approvePackageForm.errors.payment ||
                                approvePackageForm.errors.requirement
                            }}
                        </div>
                        <button
                            v-if="canApprove"
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
                            @click="approve"
                        >
                            <CheckCircleIcon class="h-5 w-5" /> Approve request
                        </button>
                        <button
                            v-if="canApprovePackage"
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
                            :disabled="approvePackageForm.processing"
                            @click="approvePackage"
                        >
                            <CheckCircleIcon class="h-5 w-5" /> Approve request + payment
                        </button>
                        <button
                            v-if="canDeny"
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50"
                            @click="showDeny = !showDeny"
                        >
                            <XCircleIcon class="h-5 w-5" /> Deny request
                        </button>
                    </div>
                    <form
                        v-if="showDeny && canDeny"
                        class="mt-3 space-y-2 rounded-lg border border-slate-200 p-3"
                        @submit.prevent="deny"
                    >
                        <label class="text-xs font-medium text-slate-700">Reason (visible in public tracking)</label>
                        <textarea
                            v-model="denyForm.denial_reason"
                            rows="3"
                            maxlength="500"
                            required
                            class="block w-full rounded-md border-slate-300 text-sm"
                        />
                        <p v-if="denyForm.errors.denial_reason" class="text-xs text-rose-600">
                            {{ denyForm.errors.denial_reason }}
                        </p>
                        <p v-if="denyForm.errors.request" class="text-xs text-rose-600">
                            {{ denyForm.errors.request }}
                        </p>
                        <button
                            type="submit"
                            class="w-full rounded-md bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500"
                            :disabled="denyForm.processing"
                        >
                            Submit denial
                        </button>
                    </form>
                </section>

                <!-- Stage / SLA -->
                <section v-if="canUpdateStage" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-2">
                        <BoltIcon class="h-5 w-5 text-brand-600" />
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">Workflow</h3>
                    </div>
                    <form class="mt-3 space-y-2" @submit.prevent="updateStage">
                        <label class="text-xs font-medium text-slate-700">Processing stage</label>
                        <select
                            v-model="stageForm.processing_stage"
                            class="block w-full rounded-md border-slate-300 text-sm"
                        >
                            <option value="processing">Processing</option>
                            <option value="ready_for_pickup">Ready for pickup</option>
                            <option value="released">Released</option>
                        </select>
                        <button
                            type="submit"
                            class="w-full rounded-md bg-slate-900 px-3 py-1.5 text-sm font-semibold text-white hover:bg-slate-700"
                            :disabled="stageForm.processing"
                        >
                            Update stage
                        </button>
                    </form>

                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">SLA control</p>
                        <p v-if="request.sla_paused_at" class="mt-1 text-xs text-slate-500">
                            Paused at {{ fmtDate(request.sla_paused_at) }} ({{ request.sla_pause_reason }}).
                        </p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-if="!request.sla_paused_at"
                                type="button"
                                class="inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100"
                                @click="showPause = !showPause"
                            >
                                <PauseCircleIcon class="h-4 w-4" /> Pause SLA
                            </button>
                            <button
                                v-else
                                type="button"
                                class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"
                                @click="resumeSla"
                            >
                                <PlayCircleIcon class="h-4 w-4" /> Resume SLA
                            </button>
                            <button
                                v-if="canRelease"
                                type="button"
                                class="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500"
                                @click="release"
                            >
                                <CheckCircleIcon class="h-4 w-4" /> Mark released
                            </button>
                        </div>
                        <form
                            v-if="showPause"
                            class="mt-3 space-y-2 rounded-lg border border-amber-200 bg-amber-50/40 p-3"
                            @submit.prevent="pauseSla"
                        >
                            <label class="text-xs font-medium text-slate-700">Pause reason</label>
                            <select
                                v-model="pauseForm.reason"
                                required
                                class="block w-full rounded-md border-slate-300 text-sm"
                            >
                                <option value="">Select reason…</option>
                                <option v-for="(label, key) in policy.sla_pause_reasons" :key="key" :value="key">
                                    {{ label }}
                                </option>
                            </select>
                            <button
                                type="submit"
                                class="w-full rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-500"
                            >
                                Apply pause
                            </button>
                        </form>
                    </div>
                </section>

                <!-- HD return for transfer credentials -->
                <section
                    v-if="request.requires_hd_return"
                    class="rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 p-6 text-white shadow"
                >
                    <div class="flex items-start gap-3">
                        <BoltIcon class="h-6 w-6 opacity-90" />
                        <div class="flex-1">
                            <p class="text-xs uppercase tracking-widest opacity-80">Special Compliance</p>
                            <h4 class="font-display text-base font-semibold">Honorable Dismissal Return</h4>
                            <p v-if="!request.hd_received_at" class="mt-1 text-xs opacity-90">
                                The 14-day TOR-for-transfer SLA only starts once the receiving school returns the
                                Honorable Dismissal.
                            </p>
                            <p v-else class="mt-1 text-xs opacity-90">
                                Honorable Dismissal received on {{ fmtDate(request.hd_received_at) }}.
                            </p>
                            <button
                                v-if="!request.hd_received_at"
                                type="button"
                                class="mt-3 inline-flex items-center gap-1 rounded-lg bg-white/20 px-3 py-1.5 text-xs font-semibold backdrop-blur hover:bg-white/30 ring-1 ring-white/30"
                                @click="markHd"
                            >
                                Mark HD Returned
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Eligibility checklist -->
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">Eligibility checklist</h3>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li class="flex items-center gap-2">
                            <CheckCircleIcon v-if="allReqsValidated" class="h-4 w-4 text-emerald-600" />
                            <ClockIcon v-else class="h-4 w-4 text-amber-600" />
                            <span>{{
                                allReqsValidated ? 'All attachments validated' : 'Attachments pending validation'
                            }}</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <CheckCircleIcon v-if="paymentApproved" class="h-4 w-4 text-emerald-600" />
                            <ClockIcon v-else class="h-4 w-4 text-amber-600" />
                            <span>{{ paymentApproved ? 'Payment approved' : 'Payment not yet verified' }}</span>
                        </li>
                        <li v-if="clearance" class="flex items-center gap-2">
                            <CheckCircleIcon
                                v-if="clearance.overall_status === 'completed'"
                                class="h-4 w-4 text-emerald-600"
                            />
                            <ClockIcon v-else class="h-4 w-4 text-amber-600" />
                            <span class="capitalize"
                                >Clearance: {{ clearance.overall_status?.replaceAll('_', ' ') }}</span
                            >
                        </li>
                    </ul>
                </section>

                <!-- Payment -->
                <section v-if="payment" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-2">
                        <CreditCardIcon class="h-5 w-5 text-brand-600" />
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">Payment</h3>
                    </div>
                    <dl class="mt-3 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Status</dt>
                            <dd class="font-semibold capitalize">{{ payment.status?.replaceAll('_', ' ') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Amount</dt>
                            <dd>
                                ₱{{
                                    Number(payment.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })
                                }}
                            </dd>
                        </div>
                        <div v-if="payment.reference_number" class="flex justify-between">
                            <dt class="text-slate-500">Reference</dt>
                            <dd class="font-mono">{{ payment.reference_number }}</dd>
                        </div>
                    </dl>
                    <button
                        v-if="payment.receipt_path"
                        type="button"
                        class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline"
                        @click="
                            openFilePreview({
                                title: 'Payment receipt',
                                url: route('files.payment-receipt', payment.id),
                                path: payment.receipt_path,
                            })
                        "
                    >
                        <EyeIcon class="h-3.5 w-3.5" /> Preview receipt
                    </button>
                </section>

                <!-- Claim slip -->
                <section
                    v-if="claimSlip"
                    class="rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 p-6 text-white shadow"
                >
                    <div class="flex items-start gap-3">
                        <TicketIcon class="h-6 w-6 opacity-90" />
                        <div>
                            <p class="text-xs uppercase tracking-widest opacity-80">Claim slip</p>
                            <p class="font-mono text-lg font-bold">{{ claimSlip.claim_number }}</p>
                            <p class="text-xs opacity-90">Claim on {{ fmtDateOnly(claimSlip.claim_date) }}</p>
                            <p class="text-xs opacity-80">
                                Channel:
                                {{ policy.release_channels?.[claimSlip.release_channel] ?? claimSlip.release_channel }}
                            </p>
                        </div>
                    </div>
                </section>
            </aside>
        </div>

        <Modal :show="!!previewFile" max-width="2xl" @close="closeFilePreview">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div class="min-w-0">
                    <h3 class="truncate font-display text-lg font-semibold text-slate-900">
                        {{ previewFile?.title }}
                    </h3>
                    <p class="text-xs text-slate-500">Private file preview</p>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        v-if="previewFile"
                        :href="previewFile.url"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                        aria-label="Open preview in a new tab"
                    >
                        <ArrowTopRightOnSquareIcon class="h-4 w-4" />
                    </a>
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                        aria-label="Close preview"
                        @click="closeFilePreview"
                    >
                        <XMarkIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>
            <div class="bg-slate-100 p-4">
                <div
                    class="flex min-h-[70vh] items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200"
                >
                    <img
                        v-if="previewFile && previewIsImage"
                        :src="previewFile.url"
                        :alt="previewFile.title"
                        class="max-h-[70vh] w-auto max-w-full object-contain"
                    />
                    <iframe
                        v-else-if="previewFile"
                        :src="previewFile.url"
                        :title="previewFile.title"
                        class="h-[70vh] w-full border-0"
                    />
                </div>
            </div>
        </Modal>
    </StaffLayout>
</template>
