<script setup>
import InputError from '@/Components/InputError.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';
import {
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
    BanknotesIcon,
    CheckCircleIcon,
    ClockIcon,
    InformationCircleIcon,
    QrCodeIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    payments: { type: Object, required: true },
    paymentProfiles: { type: Array, default: () => [] },
    paymentProfile: { type: Object, default: null }, // legacy compat
});

const forms = reactive({});

const getForm = (paymentId) => {
    if (!forms[paymentId]) {
        forms[paymentId] = useForm({
            receipt: null,
            payment_method: '',
            reference_number: '',
        });
    }
    return forms[paymentId];
};

const submit = (paymentId) => {
    const form = getForm(paymentId);
    form.post(route('student.payments.upload', paymentId), { forceFormData: true });
};

function statusBadge(status) {
    return (
        {
            pending: 'bg-slate-100 text-slate-600',
            pending_approval: 'bg-amber-100 text-amber-800',
            approved: 'bg-emerald-100 text-emerald-800',
            denied: 'bg-rose-100 text-rose-800',
        }[status] ?? 'bg-slate-100 text-slate-600'
    );
}

function statusLabel(status) {
    return (
        {
            pending: 'Awaiting Receipt',
            pending_approval: 'Receipt Under Review',
            approved: 'Payment Approved',
            denied: 'Receipt Rejected',
        }[status] ?? status
    );
}

// Policy-initial gate: upload only allowed after request is approved by admin
const canUpload = (payment) =>
    !['approved', 'pending_approval'].includes(payment.status) &&
    ['approved', 'completed'].includes(payment.document_request?.status);

function formatPeso(n) {
    return `₱${Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function requestItems(payment) {
    return payment.document_request?.items ?? [];
}
</script>

<template>
    <Head title="Payments" />

    <StudentLayout>
        <template #header>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">My Account</p>
                <h2 class="mt-1 text-2xl font-display font-bold text-slate-900">Payments</h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    Upload your payment receipts to proceed with document processing.
                </p>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-8 px-4 pb-12 sm:px-6 lg:px-8">
            <!-- ════════════════════════
                 SCHOOL PAYMENT DETAILS (all active profiles)
                 ════════════════════════ -->
            <section v-if="paymentProfiles.length" class="space-y-4">
                <div class="flex items-start gap-3 mb-1">
                    <div
                        class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-brand-600 text-white shadow"
                    >
                        <BanknotesIcon class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-display font-bold text-brand-900">School Payment Details</h3>
                        <p class="text-sm text-brand-700 mt-0.5">
                            Choose any of the payment channels below. Transfer your payment then upload your receipt.
                        </p>
                    </div>
                </div>

                <div
                    v-for="profile in paymentProfiles"
                    :key="profile.id"
                    class="rounded-2xl border-2 border-brand-200 bg-gradient-to-br from-brand-50 to-blue-50 p-5 shadow-sm"
                >
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Bank info -->
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-brand-200">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">
                                {{ profile.bank_name }}
                            </h4>
                            <dl class="space-y-3 text-sm">
                                <div>
                                    <dt class="text-xs text-slate-400">Account Name</dt>
                                    <dd class="font-semibold text-slate-900 mt-0.5">
                                        {{ profile.account_name || '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-400">Account Number</dt>
                                    <dd
                                        class="font-mono text-xl font-bold tracking-widest text-brand-700 mt-0.5 select-all"
                                    >
                                        {{ profile.account_number || '—' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- QR code -->
                        <div
                            class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-brand-200 flex flex-col items-center justify-center gap-3"
                        >
                            <div
                                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500"
                            >
                                <QrCodeIcon class="h-4 w-4 text-brand-600" />
                                Scan to Pay
                            </div>
                            <img
                                v-if="profile.qr_url"
                                :src="profile.qr_url"
                                alt="Payment QR Code"
                                class="h-36 w-36 object-contain rounded-lg"
                            />
                            <div
                                v-else
                                class="flex h-36 w-36 flex-col items-center justify-center rounded-lg bg-slate-100 text-slate-400 gap-2"
                            >
                                <QrCodeIcon class="h-10 w-10" />
                                <span class="text-xs text-center">QR not configured</span>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div v-if="profile.instructions" class="rounded-xl bg-amber-50 p-5 ring-1 ring-amber-200">
                            <h4
                                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-amber-700 mb-3"
                            >
                                <InformationCircleIcon class="h-4 w-4" />
                                How to Pay
                            </h4>
                            <p class="text-sm text-amber-900 whitespace-pre-line leading-relaxed">
                                {{ profile.instructions }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ════════════════════════
                 PAYMENTS LIST
                 ════════════════════════ -->
            <div
                v-if="payments.data.length === 0"
                class="flex flex-col items-center gap-3 rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm"
            >
                <BanknotesIcon class="h-12 w-12 text-slate-300" />
                <p class="font-semibold text-slate-600">No pending payments</p>
                <p class="text-sm text-slate-500">Once you submit a document request, your payment will appear here.</p>
            </div>

            <div
                v-for="payment in payments.data"
                :key="payment.id"
                class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200"
            >
                <!-- Payment header -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
                    <div>
                        <p class="font-display font-semibold text-slate-900">
                            {{ payment.document_request?.reference_no || 'Document Request' }}
                        </p>
                        <p v-if="payment.document_request?.document_type?.name" class="text-xs text-slate-500 mt-0.5">
                            {{ payment.document_request.document_type.name }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-lg font-display font-bold text-slate-900">
                            ₱{{ Number(payment.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                        </span>
                        <span
                            :class="[
                                'inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold',
                                statusBadge(payment.status),
                            ]"
                        >
                            <CheckCircleIcon v-if="payment.status === 'approved'" class="h-3.5 w-3.5" />
                            <XCircleIcon v-else-if="payment.status === 'denied'" class="h-3.5 w-3.5" />
                            <ClockIcon v-else class="h-3.5 w-3.5" />
                            {{ statusLabel(payment.status) }}
                        </span>
                    </div>
                </div>

                <!-- Rejection message -->
                <div
                    v-if="payment.status === 'denied' && payment.denial_reason"
                    class="mx-6 mt-4 flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"
                >
                    <XCircleIcon class="h-4 w-4 mt-0.5 flex-none" />
                    <div>
                        <p class="font-semibold">Receipt rejected</p>
                        <p>{{ payment.denial_reason }}</p>
                    </div>
                </div>

                <!-- Approved message -->
                <div
                    v-else-if="payment.status === 'approved'"
                    class="mx-6 mt-4 flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                >
                    <CheckCircleIcon class="h-4 w-4 mt-0.5 flex-none" />
                    <p>Payment approved. Department clearance and document processing have started.</p>
                </div>

                <!-- Under review message -->
                <div
                    v-else-if="payment.status === 'pending_approval'"
                    class="mx-6 mt-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                >
                    <ClockIcon class="h-4 w-4 mt-0.5 flex-none animate-pulse" />
                    <p>
                        Your receipt has been submitted and is under admin review. We'll notify you once it's verified.
                    </p>
                </div>

                <!-- Itemized breakdown -->
                <div v-if="requestItems(payment).length" class="px-6 py-4 border-t border-slate-100">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Breakdown</p>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="item in requestItems(payment)" :key="item.id" class="text-slate-700">
                                <td class="py-2">{{ item.document_type?.name }}</td>
                                <td class="py-2 text-center text-slate-500">
                                    {{ item.page_count_snapshot }}p × {{ item.copies }} copy
                                </td>
                                <td class="py-2 text-right font-semibold">{{ formatPeso(item.line_total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Locked notice when request not yet approved -->
                <div
                    v-if="
                        payment.document_request?.status === 'pending' &&
                        !['approved', 'pending_approval'].includes(payment.status)
                    "
                    class="mx-6 mt-4 mb-4 flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
                >
                    <ClockIcon class="h-4 w-4 mt-0.5 flex-none" />
                    <p>Receipt upload will be unlocked once admin approves your request.</p>
                </div>

                <!-- Upload form -->
                <div v-if="canUpload(payment)" class="px-6 py-5">
                    <h4 class="text-sm font-semibold text-slate-700 mb-4">
                        {{ payment.status === 'denied' ? 'Upload New Receipt' : 'Upload Payment Receipt' }}
                    </h4>
                    <form class="space-y-4" @submit.prevent="submit(payment.id)">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">
                                    Payment Method <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="getForm(payment.id).payment_method"
                                    required
                                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                                >
                                    <option value="">Select method…</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="gcash">GCash</option>
                                    <option value="maya">Maya</option>
                                    <option value="cash">Cash (Cashier)</option>
                                    <option value="other">Other</option>
                                </select>
                                <InputError class="mt-1" :message="getForm(payment.id).errors.payment_method" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700"
                                    >Reference / Transaction No.</label
                                >
                                <input
                                    v-model="getForm(payment.id).reference_number"
                                    type="text"
                                    placeholder="e.g. TXN-2026-XXXXXX"
                                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                                />
                                <InputError class="mt-1" :message="getForm(payment.id).errors.reference_number" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Receipt Screenshot / File <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex items-center justify-center w-full">
                                <label
                                    class="flex flex-col items-center justify-center w-full h-36 border-2 border-brand-300 border-dashed rounded-xl cursor-pointer bg-brand-50 hover:bg-brand-100 transition-colors"
                                >
                                    <div class="flex flex-col items-center justify-center py-4 text-brand-700">
                                        <ArrowUpTrayIcon class="w-8 h-8 mb-2 text-brand-500" />
                                        <p class="text-sm font-medium">
                                            <span v-if="getForm(payment.id).receipt">
                                                {{ getForm(payment.id).receipt.name }}
                                            </span>
                                            <span v-else>Click to upload or drag and drop</span>
                                        </p>
                                        <p class="text-xs text-brand-500 mt-0.5">JPG, PNG, PDF – max 5 MB</p>
                                    </div>
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,application/pdf"
                                        class="hidden"
                                        @change="getForm(payment.id).receipt = $event.target.files[0]"
                                    />
                                </label>
                            </div>
                            <InputError class="mt-1" :message="getForm(payment.id).errors.receipt" />
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                type="submit"
                                :disabled="getForm(payment.id).processing || !getForm(payment.id).receipt"
                                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-brand-500 disabled:opacity-60 transition-colors"
                            >
                                <ArrowUpTrayIcon class="h-4 w-4" />
                                {{ getForm(payment.id).processing ? 'Uploading…' : 'Submit Receipt' }}
                            </button>
                            <p v-if="payment.receipt_path" class="text-xs text-slate-500">
                                This will replace your current receipt.
                            </p>
                        </div>
                    </form>
                </div>

                <!-- Already has approved receipt – show download link -->
                <div v-if="payment.receipt_path" class="border-t border-slate-100 px-6 py-4">
                    <a
                        :href="route('files.payment-receipt', payment.id)"
                        class="inline-flex items-center gap-2 text-sm font-medium text-brand-700 hover:text-brand-600"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        View submitted receipt
                    </a>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
