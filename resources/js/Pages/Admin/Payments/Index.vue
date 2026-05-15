<script setup>
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { useEchoPrivateChannel } from '@/Composables/useEchoPrivateChannel';
import { useRealtimeOrPoll } from '@/Composables/useRealtimeOrPoll';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { BanknotesIcon } from '@heroicons/vue/24/outline';
import { reactive, watch } from 'vue';

const props = defineProps({
    payments: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const filterForm = reactive({ status: props.filters.status || '' });
const denyReasons = reactive({});
const processingByPayment = reactive({});
const rowErrors = reactive({});

watch(
    () => props.payments.data,
    (rows) => {
        const visibleIds = new Set(rows.map((payment) => String(payment.id)));

        for (const key of Object.keys(denyReasons)) {
            if (!visibleIds.has(key)) {
                delete denyReasons[key];
            }
        }
        for (const key of Object.keys(processingByPayment)) {
            if (!visibleIds.has(key)) {
                delete processingByPayment[key];
            }
        }
        for (const key of Object.keys(rowErrors)) {
            if (!visibleIds.has(key)) {
                delete rowErrors[key];
            }
        }

        rows.forEach((payment) => {
            if (typeof denyReasons[payment.id] === 'undefined') {
                denyReasons[payment.id] = '';
            }
            if (typeof processingByPayment[payment.id] === 'undefined') {
                processingByPayment[payment.id] = { approve: false, deny: false };
            }
            if (typeof rowErrors[payment.id] === 'undefined') {
                rowErrors[payment.id] = { payment: null, denial_reason: null };
            }
        });
    },
    { immediate: true },
);

const applyFilters = () => {
    router.get(route('admin.payments.index'), filterForm, { preserveState: true, replace: true });
};

const clearRowErrors = (id) => {
    rowErrors[id] = { payment: null, denial_reason: null };
};

const captureRowErrors = (id, errors) => {
    rowErrors[id] = {
        payment: errors.payment ?? null,
        denial_reason: errors.denial_reason ?? null,
    };
};

const approve = (id) => {
    clearRowErrors(id);
    processingByPayment[id].approve = true;
    router.post(
        route('admin.payments.approve', id),
        {},
        {
            preserveScroll: true,
            onError: (errors) => captureRowErrors(id, errors),
            onFinish: () => {
                processingByPayment[id].approve = false;
            },
        },
    );
};
const deny = (id) => {
    clearRowErrors(id);
    processingByPayment[id].deny = true;
    router.post(
        route('admin.payments.deny', id),
        {
            denial_reason: denyReasons[id] ?? '',
        },
        {
            preserveScroll: true,
            onError: (errors) => captureRowErrors(id, errors),
            onSuccess: () => {
                denyReasons[id] = '';
            },
            onFinish: () => {
                processingByPayment[id].deny = false;
            },
        },
    );
};

const reloadPayments = () => {
    router.reload({ only: ['payments', 'filters'], preserveScroll: true });
};

useEchoPrivateChannel(() => 'role.admin', {
    PaymentSubmitted: reloadPayments,
});

useRealtimeOrPoll(reloadPayments, { intervalMs: 90000 });

function paymentStatusTone(status) {
    return (
        {
            pending: 'neutral',
            pending_approval: 'warning',
            approved: 'success',
            denied: 'danger',
        }[status] ?? 'neutral'
    );
}
</script>

<template>
    <Head title="Manage Payments" />

    <StaffLayout>
        <template #header>
            <PageHeader
                title="Payments Management"
                subtitle="Review receipt submissions, approve valid payments, and record denial reasons."
            />
        </template>

        <div class="space-y-6">
            <div
                class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center"
            >
                <label for="payment-status-filter" class="sr-only">Payment status filter</label>
                <select
                    id="payment-status-filter"
                    v-model="filterForm.status"
                    class="min-h-11 rounded-md border-slate-300 text-sm shadow-sm"
                >
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="pending_approval">Pending Approval</option>
                    <option value="approved">Approved</option>
                    <option value="denied">Denied</option>
                </select>
                <button
                    type="button"
                    class="min-h-11 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                    @click="applyFilters"
                >
                    Apply
                </button>
            </div>

            <div class="space-y-4">
                <article
                    v-for="item in payments.data"
                    :key="item.id"
                    class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900">
                                Payment #{{ item.id }} — {{ item.document_request?.reference_no }}
                            </p>
                            <p class="text-sm text-slate-600">
                                {{ item.user?.fullname }} | {{ item.document_request?.document_type?.name }} | PHP
                                {{ Number(item.total_amount).toFixed(2) }}
                            </p>
                            <div class="mt-2 flex items-center gap-2 text-sm text-slate-600">
                                <span>Status:</span>
                                <StatusBadge
                                    :label="item.status.replaceAll('_', ' ')"
                                    :tone="paymentStatusTone(item.status)"
                                />
                            </div>
                            <p v-if="item.receipt_path" class="mt-1 text-sm">
                                <a
                                    :href="route('files.payment-receipt', item.id)"
                                    class="text-indigo-600 hover:text-indigo-500"
                                    >Preview Receipt</a
                                >
                            </p>
                        </div>

                        <div
                            v-if="item.status === 'pending_approval'"
                            class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center"
                        >
                            <button
                                type="button"
                                class="min-h-11 rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50"
                                :disabled="processingByPayment[item.id]?.approve"
                                @click="approve(item.id)"
                            >
                                {{ processingByPayment[item.id]?.approve ? 'Approving...' : 'Approve' }}
                            </button>
                            <form
                                class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center"
                                @submit.prevent="deny(item.id)"
                            >
                                <label :for="`denial-reason-${item.id}`" class="sr-only"
                                    >Denial reason for payment #{{ item.id }}</label
                                >
                                <input
                                    :id="`denial-reason-${item.id}`"
                                    v-model="denyReasons[item.id]"
                                    type="text"
                                    placeholder="Denial reason"
                                    class="min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm sm:w-56"
                                    :disabled="processingByPayment[item.id]?.deny"
                                />
                                <button
                                    type="submit"
                                    class="min-h-11 rounded-md bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-500 disabled:opacity-50"
                                    :disabled="processingByPayment[item.id]?.deny"
                                >
                                    {{ processingByPayment[item.id]?.deny ? 'Denying...' : 'Deny' }}
                                </button>
                            </form>
                            <div
                                v-if="rowErrors[item.id]?.payment || rowErrors[item.id]?.denial_reason"
                                class="w-full text-sm text-rose-600"
                            >
                                <p v-if="rowErrors[item.id]?.payment">{{ rowErrors[item.id].payment }}</p>
                                <p v-if="rowErrors[item.id]?.denial_reason">
                                    {{ rowErrors[item.id].denial_reason }}
                                </p>
                            </div>
                        </div>
                    </div>
                </article>

                <div v-if="payments.data.length === 0">
                    <EmptyState
                        title="No payments found"
                        description="Receipt submissions and payment reviews will appear here once students upload proof of payment."
                        :icon="BanknotesIcon"
                    />
                </div>
            </div>
        </div>
    </StaffLayout>
</template>
