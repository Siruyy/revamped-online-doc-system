<script setup>
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
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

watch(
    () => props.payments.data,
    (rows) => {
        rows.forEach((payment) => {
            if (typeof denyReasons[payment.id] === 'undefined') {
                denyReasons[payment.id] = '';
            }
        });
    },
    { immediate: true },
);

const applyFilters = () => {
    router.get(route('admin.payments.index'), filterForm, { preserveState: true, replace: true });
};

const approve = (id) => {
    router.post(route('admin.payments.approve', id));
};
const deny = (id) => {
    router.post(route('admin.payments.deny', id), {
        denial_reason: denyReasons[id] ?? '',
    });
};

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
                                class="min-h-11 rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                                @click="approve(item.id)"
                            >
                                Approve
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
                                />
                                <button
                                    type="submit"
                                    class="min-h-11 rounded-md bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-500"
                                >
                                    Deny
                                </button>
                            </form>
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
