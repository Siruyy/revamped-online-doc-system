<script setup>
import { useEchoPrivateChannel } from '@/Composables/useEchoPrivateChannel';
import { useRealtimeOrPoll } from '@/Composables/useRealtimeOrPoll';
import FormField from '@/Components/UI/FormField.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
    BanknotesIcon,
    BuildingOffice2Icon,
    CalendarDaysIcon,
    CheckCircleIcon,
    ClipboardDocumentCheckIcon,
    ClockIcon,
    CreditCardIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    PaperAirplaneIcon,
    QrCodeIcon,
    TicketIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    request: { type: Object, required: true },
    policy: { type: Object, required: true },
    paymentProfile: { type: Object, default: null },
});

const page = usePage();
const studentId = computed(() => page.props.auth?.user?.id ?? null);

const reloadRequest = () => {
    router.reload({ only: ['request'], preserveScroll: true });
};

useEchoPrivateChannel(() => (studentId.value ? `user.${studentId.value}` : null), {
    RequestApproved: reloadRequest,
    RequestDenied: reloadRequest,
    RequestStageUpdated: reloadRequest,
    PaymentApproved: reloadRequest,
    PaymentDenied: reloadRequest,
    ClearanceUpdated: reloadRequest,
    ClearanceCompleted: reloadRequest,
    RegistrationApproved: reloadRequest,
});

useRealtimeOrPoll(reloadRequest, { intervalMs: 90000 });

const payment = computed(() => props.request.payments?.[0] ?? null);
const clearance = computed(() => props.request.clearances?.[0] ?? null);
const canDownloadClearancePdf = computed(
    () => clearance.value?.overall_status === 'completed' && clearance.value?.pdf_path,
);
const claimSlip = computed(() => props.request.claim_slip ?? null);
const requirements = computed(() => props.request.requirements ?? []);
const docType = computed(() => props.request.document_type);
const requestItems = computed(() => props.request.items ?? []);

// Policy-initial: student can cancel while request is still pending (before admin approves)
const canCancel = computed(() => props.request.status === 'pending');

// Show payment instructions only after admin approves — policy-initial gate.
const showPaymentInstructions = computed(() => {
    const paymentStatus = payment.value?.status;
    return (
        props.paymentProfile &&
        props.request.status === 'approved' &&
        !['pending_approval', 'approved'].includes(paymentStatus)
    );
});

// Show upload reminder when request is approved but no receipt yet submitted
const showReceiptUpload = computed(() => {
    return props.request.status === 'approved' && payment.value && !payment.value.receipt_path;
});

function cancelRequest() {
    if (!window.confirm('Are you sure you want to cancel this request?')) return;
    router.post(route('student.requests.cancel', props.request.id));
}

function formatDate(value) {
    if (!value) return '—';
    try {
        return new Date(value).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
    } catch {
        return value;
    }
}

function formatPeso(n) {
    return `₱${Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatDateOnly(value) {
    if (!value) return '—';
    try {
        return new Date(value).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } catch {
        return value;
    }
}

// Policy-initial timeline: submitted → admin review → approved → pay → payment verified → clearance → released
const timeline = computed(() => {
    const paymentStatus = payment.value?.status;
    const receiptUploaded = !!payment.value?.receipt_path;
    const paymentApproved = paymentStatus === 'approved';
    const requestApproved = ['approved', 'completed'].includes(props.request.status);

    return [
        {
            key: 'submitted',
            label: 'Request submitted',
            done: true,
            timestamp: props.request.created_at,
        },
        {
            key: 'admin_review',
            label:
                props.request.status === 'approved' || props.request.status === 'completed'
                    ? 'Request approved by admin'
                    : props.request.status === 'denied'
                      ? 'Request denied'
                      : 'Awaiting admin review',
            done: requestApproved,
            active: props.request.status === 'pending',
            failed: props.request.status === 'denied',
            timestamp: props.request.approved_at,
        },
        {
            key: 'payment_upload',
            label: receiptUploaded
                ? paymentStatus === 'denied'
                    ? 'Receipt rejected – re-upload required'
                    : 'Receipt uploaded'
                : 'Upload payment receipt',
            done: receiptUploaded && paymentStatus !== 'denied',
            active: requestApproved && (!receiptUploaded || paymentStatus === 'denied'),
            failed: paymentStatus === 'denied',
            timestamp: payment.value?.submitted_at,
        },
        {
            key: 'payment_verified',
            label: paymentApproved ? 'Payment verified by admin' : 'Awaiting payment verification',
            done: paymentApproved,
            active: receiptUploaded && !paymentApproved && paymentStatus !== 'denied',
            timestamp: payment.value?.approved_at,
        },
        {
            key: 'processing',
            label: 'Processing',
            done: ['ready_for_pickup', 'released'].includes(props.request.processing_stage),
            active: props.request.processing_stage === 'processing',
            timestamp: props.request.sla_start_at,
        },
        {
            key: 'ready',
            label: 'Ready for pickup',
            done: props.request.processing_stage === 'released',
            active: props.request.processing_stage === 'ready_for_pickup',
            timestamp: claimSlip.value?.claim_date,
        },
        {
            key: 'released',
            label: 'Released',
            done: props.request.processing_stage === 'released',
            timestamp: props.request.released_at,
        },
    ];
});

// Requirement upload state
const activeRequirementId = ref(null);
const requirementForm = useForm({
    file: null,
    notes: '',
});

function openRequirement(id) {
    activeRequirementId.value = id;
    requirementForm.reset();
}

function submitRequirement(requirement) {
    if (!requirementForm.file) return;
    requirementForm.post(route('student.requests.requirements.upload', [props.request.id, requirement.id]), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            activeRequirementId.value = null;
            requirementForm.reset();
        },
    });
}

function statusBadge(status) {
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

function requirementBadge(status) {
    return (
        {
            missing: 'bg-slate-100 text-slate-700',
            submitted: 'bg-amber-100 text-amber-800',
            validated: 'bg-emerald-100 text-emerald-800',
            rejected: 'bg-rose-100 text-rose-800',
        }[status] ?? 'bg-slate-100 text-slate-600'
    );
}

function paymentStatusBadge(status) {
    return (
        {
            pending: 'bg-slate-100 text-slate-600',
            pending_approval: 'bg-amber-100 text-amber-800',
            approved: 'bg-emerald-100 text-emerald-800',
            denied: 'bg-rose-100 text-rose-800',
        }[status] ?? 'bg-slate-100 text-slate-600'
    );
}
</script>

<template>
    <Head :title="`Request ${request.reference_no}`" />

    <StudentLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Document Request</p>
                    <h2 class="mt-1 text-2xl font-display font-bold text-slate-900">
                        {{ docType?.name || 'Request' }}
                    </h2>
                    <p class="text-xs text-slate-500">Ref. {{ request.reference_no }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        :class="[
                            'inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold capitalize',
                            statusBadge(request.status),
                        ]"
                    >
                        <CheckCircleIcon v-if="request.status === 'completed'" class="h-4 w-4" />
                        <ExclamationTriangleIcon v-else-if="request.status === 'denied'" class="h-4 w-4" />
                        <ClockIcon v-else class="h-4 w-4" />
                        {{ request.status }}
                    </span>
                    <button
                        v-if="canCancel"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                        @click="cancelRequest"
                    >
                        <XCircleIcon class="h-4 w-4" />
                        Cancel
                    </button>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6 px-4 pb-12 sm:px-6 lg:px-8">
            <!-- Denial alert -->
            <div
                v-if="request.status === 'denied' && request.denial_reason"
                class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800"
            >
                <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 flex-none" />
                <div>
                    <p class="font-semibold">This request was denied</p>
                    <p class="mt-1">{{ request.denial_reason }}</p>
                </div>
            </div>

            <!-- Payment receipt denied alert -->
            <div
                v-if="payment?.status === 'denied'"
                class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800"
            >
                <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 flex-none" />
                <div>
                    <p class="font-semibold">Your payment receipt was rejected</p>
                    <p v-if="payment.denial_reason" class="mt-1">Reason: {{ payment.denial_reason }}</p>
                    <p class="mt-1">Please upload a new, clear receipt to continue.</p>
                    <Link
                        :href="route('student.payments.index')"
                        class="mt-2 inline-flex items-center gap-1 rounded-lg bg-rose-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-600"
                    >
                        <ArrowUpTrayIcon class="h-4 w-4" /> Re-upload Receipt
                    </Link>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════
                 PAYMENT INSTRUCTIONS (shown until receipt approved)
                 ═══════════════════════════════════════════════════ -->
            <section
                v-if="showPaymentInstructions"
                class="rounded-2xl border-2 border-brand-300 bg-gradient-to-br from-brand-50 to-blue-50 p-6 shadow-sm"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-brand-600 text-white shadow"
                    >
                        <BanknotesIcon class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-display font-bold text-brand-900">
                            Your Request is Approved — Now Pay
                        </h3>
                        <p class="mt-0.5 text-sm text-brand-700">
                            Transfer the amount below to the school's account, then upload your receipt. Admin will
                            verify your payment before processing begins.
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <!-- Bank details -->
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-brand-200">
                        <h4
                            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-600"
                        >
                            <BanknotesIcon class="h-4 w-4 text-brand-600" />
                            Bank Account Details
                        </h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500">Bank</dt>
                                <dd class="mt-0.5 font-semibold text-slate-900">
                                    {{ paymentProfile?.bank_name || '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Account Name</dt>
                                <dd class="mt-0.5 font-semibold text-slate-900">
                                    {{ paymentProfile?.account_name || '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Account Number</dt>
                                <dd class="mt-0.5 font-mono text-lg font-bold tracking-wider text-brand-700 select-all">
                                    {{ paymentProfile?.account_number || '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Amount to Pay</dt>
                                <dd class="mt-0.5 text-xl font-display font-bold text-emerald-700">
                                    ₱{{
                                        Number(payment?.total_amount || 0).toLocaleString('en-PH', {
                                            minimumFractionDigits: 2,
                                        })
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- QR code + instructions -->
                    <div class="flex flex-col gap-4">
                        <div
                            v-if="paymentProfile?.qr_url"
                            class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-brand-200 flex flex-col items-center"
                        >
                            <div
                                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-600 mb-3"
                            >
                                <QrCodeIcon class="h-4 w-4 text-brand-600" />
                                Scan to Pay
                            </div>
                            <img
                                :src="paymentProfile.qr_url"
                                alt="Payment QR Code"
                                class="h-40 w-40 object-contain rounded-lg"
                            />
                        </div>
                        <div
                            v-else
                            class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-brand-100 flex items-center justify-center gap-3 text-slate-400"
                        >
                            <QrCodeIcon class="h-8 w-8" />
                            <span class="text-sm">QR code not yet configured</span>
                        </div>

                        <div
                            v-if="paymentProfile?.instructions"
                            class="rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200 text-sm text-amber-900"
                        >
                            <div
                                class="flex items-center gap-2 mb-2 text-xs font-semibold uppercase tracking-wider text-amber-700"
                            >
                                <InformationCircleIcon class="h-4 w-4" />
                                Instructions
                            </div>
                            <p class="whitespace-pre-line leading-relaxed">{{ paymentProfile.instructions }}</p>
                        </div>
                    </div>
                </div>

                <!-- Upload CTA -->
                <div class="mt-5 flex items-center gap-3">
                    <Link
                        :href="route('student.payments.index')"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-brand-500 transition-colors"
                    >
                        <ArrowUpTrayIcon class="h-5 w-5" />
                        Upload Payment Receipt
                    </Link>
                    <p class="text-xs text-slate-500">Accepted: JPG, PNG, PDF — max 5 MB</p>
                </div>
            </section>

            <!-- Upload reminder (receipt uploaded but pending admin check) -->
            <div
                v-else-if="showReceiptUpload"
                class="flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800"
            >
                <ClockIcon class="h-5 w-5 flex-none animate-pulse" />
                <div>
                    <p class="font-semibold">Waiting for payment receipt</p>
                    <p>
                        You haven't uploaded your payment receipt yet.
                        <Link :href="route('student.payments.index')" class="font-semibold underline"
                            >Upload now →</Link
                        >
                    </p>
                </div>
            </div>

            <!-- Summary grid -->
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-slate-500">
                        <DocumentTextIcon class="h-4 w-4" /> Category
                    </div>
                    <p class="mt-2 font-display font-semibold text-slate-900">{{ docType?.category }}</p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-slate-500">
                        <ClockIcon class="h-4 w-4" /> Processing SLA
                    </div>
                    <p class="mt-2 font-display font-semibold text-slate-900">{{ docType?.processing_days }} days</p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-slate-500">
                        <CalendarDaysIcon class="h-4 w-4" /> Expected release
                    </div>
                    <p class="mt-2 font-display font-semibold text-slate-900">
                        {{ formatDateOnly(request.expected_release_on) }}
                    </p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-slate-500">
                        <BuildingOffice2Icon class="h-4 w-4" /> Release at
                    </div>
                    <p class="mt-2 text-sm font-medium text-slate-900">
                        {{ policy.release_channels?.[docType?.release_channel] ?? '—' }}
                    </p>
                </div>
            </section>

            <!-- Items breakdown (multi-doc) -->
            <section v-if="requestItems.length" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-600">Document Items</h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                            <th class="pb-2 text-left font-semibold">Document</th>
                            <th class="pb-2 text-center font-semibold">Pages</th>
                            <th class="pb-2 text-center font-semibold">Copies</th>
                            <th class="pb-2 text-right font-semibold">Line Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="item in requestItems" :key="item.id" class="text-slate-700">
                            <td class="py-2.5">
                                <p class="font-medium text-slate-900">{{ item.document_type?.name }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ formatPeso(item.fee_per_page_snapshot) }}/{{
                                        item.document_type?.fee_formula?.replace('_', ' ') || 'flat'
                                    }}
                                </p>
                            </td>
                            <td class="py-2.5 text-center text-slate-600">{{ item.page_count_snapshot }}</td>
                            <td class="py-2.5 text-center text-slate-600">{{ item.copies }}</td>
                            <td class="py-2.5 text-right font-semibold text-slate-900">
                                {{ formatPeso(item.line_total) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-slate-200">
                            <td colspan="3" class="pt-3 font-semibold text-slate-700">Total</td>
                            <td class="pt-3 text-right text-lg font-bold text-brand-700">
                                {{ formatPeso(payment?.total_amount || request.fee_snapshot) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </section>

            <!-- Timeline -->
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">Progress</h3>
                <ol class="relative mt-5 space-y-6 border-l-2 border-slate-100 pl-6">
                    <li v-for="step in timeline" :key="step.key" class="relative">
                        <span
                            class="absolute -left-[33px] mt-1 flex h-6 w-6 items-center justify-center rounded-full"
                            :class="
                                step.failed
                                    ? 'bg-rose-500 text-white'
                                    : step.done
                                      ? 'bg-brand-600 text-white'
                                      : step.active
                                        ? 'bg-amber-500 text-white ring-4 ring-amber-100'
                                        : 'bg-slate-200 text-slate-500'
                            "
                        >
                            <XCircleIcon v-if="step.failed" class="h-4 w-4" />
                            <CheckCircleIcon v-else-if="step.done" class="h-4 w-4" />
                            <ClockIcon v-else-if="step.active" class="h-4 w-4 animate-pulse" />
                            <span v-else class="h-2 w-2 rounded-full bg-white"></span>
                        </span>
                        <p
                            :class="[
                                'font-medium',
                                step.failed
                                    ? 'text-rose-700'
                                    : step.active
                                      ? 'text-amber-700'
                                      : step.done
                                        ? 'text-slate-900'
                                        : 'text-slate-500',
                            ]"
                        >
                            {{ step.label }}
                        </p>
                        <p class="text-xs text-slate-400">{{ formatDate(step.timestamp) }}</p>
                    </li>
                </ol>
            </section>

            <!-- Requirements -->
            <section v-if="requirements.length" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">Attachments</h3>
                    <span class="text-xs text-slate-500"
                        >{{ requirements.filter((r) => r.status === 'validated').length }} /
                        {{ requirements.length }} validated</span
                    >
                </div>
                <ul class="mt-4 divide-y divide-slate-100">
                    <li v-for="req in requirements" :key="req.id" class="py-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex items-start gap-3">
                                <div class="rounded-xl bg-brand-50 p-2 text-brand-700">
                                    <ClipboardDocumentCheckIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900">{{ req.label }}</p>
                                    <p
                                        v-if="policy.requirements_catalog?.[req.requirement_key]?.hint"
                                        class="text-xs text-slate-500"
                                    >
                                        {{ policy.requirements_catalog[req.requirement_key].hint }}
                                    </p>
                                    <p v-if="req.notes" class="mt-1 text-xs italic text-slate-500">
                                        Notes: {{ req.notes }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-3 py-0.5 text-xs font-semibold capitalize',
                                        requirementBadge(req.status),
                                    ]"
                                >
                                    {{ req.status }}
                                </span>
                                <button
                                    v-if="['missing', 'rejected'].includes(req.status)"
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-500"
                                    @click="openRequirement(req.id)"
                                >
                                    <ArrowUpTrayIcon class="h-4 w-4" />
                                    {{ req.status === 'rejected' ? 'Re-upload' : 'Upload' }}
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="activeRequirementId === req.id"
                            class="mt-3 rounded-xl border border-dashed border-brand-300 bg-brand-50/40 p-4"
                        >
                            <FormField
                                :id="`requirement-file-${req.id}`"
                                label="File"
                                :error="requirementForm.errors.file"
                                help="PDF, JPG, PNG; max 5MB."
                                required
                            >
                                <template #default="{ id, describedBy, invalid }">
                                    <input
                                        :id="id"
                                        type="file"
                                        accept=".pdf,image/png,image/jpeg"
                                        class="mt-1 block w-full rounded-lg border border-slate-300 text-sm text-slate-700 file:mr-4 file:min-h-11 file:border-0 file:bg-brand-600 file:px-4 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                                        :aria-describedby="describedBy"
                                        :aria-invalid="invalid ? 'true' : undefined"
                                        @change="requirementForm.file = $event.target.files[0]"
                                    />
                                    <p v-if="requirementForm.file" class="mt-2 text-xs text-brand-700">
                                        Selected: {{ requirementForm.file.name }}
                                    </p>
                                </template>
                            </FormField>
                            <FormField
                                :id="`requirement-notes-${req.id}`"
                                class="mt-3"
                                label="Notes (optional)"
                                :error="requirementForm.errors.notes"
                            >
                                <template #default="{ id, describedBy, invalid }">
                                    <textarea
                                        :id="id"
                                        v-model="requirementForm.notes"
                                        rows="2"
                                        maxlength="500"
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                        :aria-describedby="describedBy"
                                        :aria-invalid="invalid ? 'true' : undefined"
                                    />
                                </template>
                            </FormField>
                            <div class="mt-3 flex gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-500 disabled:opacity-60"
                                    :disabled="!requirementForm.file || requirementForm.processing"
                                    :aria-busy="requirementForm.processing ? 'true' : undefined"
                                    @click="submitRequirement(req)"
                                >
                                    <PaperAirplaneIcon class="h-4 w-4" />
                                    {{ requirementForm.processing ? 'Submitting...' : 'Submit' }}
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50"
                                    @click="activeRequirementId = null"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>
            </section>

            <!-- Payment / Clearance -->
            <section class="grid gap-6 lg:grid-cols-2">
                <!-- Payment card -->
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-2">
                        <CreditCardIcon class="h-5 w-5 text-brand-600" />
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">Payment</h3>
                    </div>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Status</dt>
                            <dd>
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                        paymentStatusBadge(payment?.status),
                                    ]"
                                >
                                    {{ (payment?.status || 'n/a').replaceAll('_', ' ') }}
                                </span>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Amount</dt>
                            <dd class="font-semibold text-slate-900">
                                ₱{{
                                    Number(payment?.total_amount || 0).toLocaleString('en-PH', {
                                        minimumFractionDigits: 2,
                                    })
                                }}
                            </dd>
                        </div>
                        <div v-if="payment?.reference_number" class="flex justify-between">
                            <dt class="text-slate-500">Reference #</dt>
                            <dd class="font-mono text-slate-900">{{ payment.reference_number }}</dd>
                        </div>
                        <div v-if="payment?.payment_method" class="flex justify-between">
                            <dt class="text-slate-500">Method</dt>
                            <dd class="capitalize text-slate-900">
                                {{ payment.payment_method?.replaceAll('_', ' ') }}
                            </dd>
                        </div>
                    </dl>
                    <div class="mt-4 flex items-center gap-2">
                        <Link
                            v-if="payment"
                            :href="route('student.payments.index')"
                            class="inline-flex items-center gap-1 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-500"
                        >
                            <ArrowUpTrayIcon class="h-4 w-4" />
                            {{ payment?.receipt_path ? 'View / Replace Receipt' : 'Upload Receipt' }}
                        </Link>
                        <a
                            v-if="payment?.receipt_path"
                            :href="route('files.payment-receipt', payment.id)"
                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                        >
                            <ArrowDownTrayIcon class="h-4 w-4" /> View Receipt
                        </a>
                    </div>
                </div>

                <!-- Clearance card -->
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-2">
                        <BuildingOffice2Icon class="h-5 w-5 text-brand-600" />
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">Clearance</h3>
                    </div>
                    <div v-if="!clearance" class="mt-4 rounded-lg bg-slate-50 p-4 text-sm text-slate-500">
                        <p v-if="!request.payment_verified_at">
                            Clearance routing starts automatically once your payment is verified and the request is
                            approved.
                        </p>
                        <p v-else>No clearance required for this document type.</p>
                    </div>
                    <dl v-else class="mt-4 space-y-2 text-sm">
                        <div
                            v-for="signatory in policy.clearance_signatories"
                            :key="signatory.role"
                            class="flex justify-between"
                        >
                            <dt class="text-slate-500">{{ signatory.label }}</dt>
                            <dd>
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                        requirementBadge(
                                            clearance[signatory.status] === 'cleared'
                                                ? 'validated'
                                                : clearance[signatory.status] === 'denied'
                                                  ? 'rejected'
                                                  : 'submitted',
                                        ),
                                    ]"
                                >
                                    {{ clearance[signatory.status] }}
                                </span>
                            </dd>
                        </div>
                        <div class="mt-3 flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                            <dt class="font-medium text-slate-600">Overall</dt>
                            <dd>
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                        statusBadge(
                                            clearance.overall_status === 'completed'
                                                ? 'completed'
                                                : clearance.overall_status === 'denied'
                                                  ? 'denied'
                                                  : 'pending',
                                        ),
                                    ]"
                                >
                                    {{ clearance.overall_status?.replaceAll('_', ' ') }}
                                </span>
                            </dd>
                        </div>
                        <a
                            v-if="canDownloadClearancePdf"
                            :href="route('files.clearance-pdf', clearance.id)"
                            class="inline-flex min-h-11 items-center justify-center rounded-lg border border-emerald-300 px-4 text-sm font-semibold text-emerald-800 hover:bg-emerald-50"
                        >
                            Download clearance PDF
                        </a>
                    </dl>
                </div>
            </section>

            <!-- Claim slip -->
            <section
                v-if="claimSlip"
                class="rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 p-6 text-white shadow-lg"
            >
                <div class="flex items-start gap-3">
                    <TicketIcon class="h-8 w-8 flex-none opacity-90" />
                    <div class="flex-1">
                        <p class="text-xs font-semibold uppercase tracking-widest opacity-80">Claim Slip</p>
                        <h3 class="mt-1 text-2xl font-display font-bold">{{ claimSlip.claim_number }}</h3>
                        <p class="mt-1 text-sm opacity-90">
                            Please present this slip and a valid ID at
                            <strong>{{
                                policy.release_channels?.[claimSlip.release_channel] ?? claimSlip.release_channel
                            }}</strong>
                            on <strong>{{ formatDateOnly(claimSlip.claim_date) }}</strong
                            >.
                        </p>
                        <p v-if="claimSlip.state === 'released'" class="mt-2 text-sm">
                            Released {{ formatDate(claimSlip.released_at) }} to
                            <strong>{{ claimSlip.claimant_name }}</strong
                            >.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </StudentLayout>
</template>
