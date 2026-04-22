<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, router } from '@inertiajs/vue3';
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
</script>

<template>
    <Head title="Manage Payments" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Payments Management</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <select v-model="filterForm.status" class="rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="pending_approval">Pending Approval</option>
                    <option value="approved">Approved</option>
                    <option value="denied">Denied</option>
                </select>
                <button type="button" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700" @click="applyFilters">
                    Apply
                </button>
            </div>

            <div class="space-y-4">
                <article v-for="item in payments.data" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900">Payment #{{ item.id }} — {{ item.document_request?.reference_no }}</p>
                            <p class="text-sm text-slate-600">
                                {{ item.user?.fullname }} | {{ item.document_request?.document_type?.name }} | PHP {{ Number(item.total_amount).toFixed(2) }}
                            </p>
                            <p class="text-sm text-slate-600">Status: <span class="font-semibold capitalize">{{ item.status }}</span></p>
                            <p v-if="item.receipt_path" class="mt-1 text-sm">
                                <a :href="route('files.payment-receipt', item.id)" class="text-indigo-600 hover:text-indigo-500">Preview Receipt</a>
                            </p>
                        </div>

                        <div v-if="item.status === 'pending_approval'" class="flex flex-wrap items-center gap-2">
                            <button type="button" class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500" @click="approve(item.id)">
                                Approve
                            </button>
                            <form class="flex items-center gap-2" @submit.prevent="deny(item.id)">
                                <input
                                    v-model="denyReasons[item.id]"
                                    type="text"
                                    placeholder="Denial reason"
                                    class="rounded-md border-slate-300 text-sm shadow-sm"
                                />
                                <button type="submit" class="rounded-md bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-500">Deny</button>
                            </form>
                        </div>
                    </div>
                </article>

                <div v-if="payments.data.length === 0" class="rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">
                    No payments found.
                </div>
            </div>
        </div>
    </StaffLayout>
</template>
