<script setup>
import Timeline from '@/Components/Timeline.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    request: {
        type: Object,
        required: true,
    },
});

const payment = computed(() => props.request.payments?.[0] ?? null);
const clearance = computed(() => props.request.clearances?.[0] ?? null);

const timeline = computed(() => [
    { key: 'submitted', label: 'Request submitted', completed: true, active: false, timestamp: props.request.created_at },
    {
        key: 'payment',
        label: 'Payment uploaded',
        completed: !!payment.value?.receipt_path,
        active: payment.value?.status === 'pending_approval',
        timestamp: payment.value?.submitted_at,
    },
    { key: 'approved', label: 'Request approved', completed: props.request.status === 'approved', active: false, timestamp: props.request.approved_at },
    {
        key: 'released',
        label: 'Request released',
        completed: props.request.processing_stage === 'released',
        active: props.request.processing_stage === 'ready_for_pickup',
        timestamp: props.request.released_at,
    },
]);

const canCancel = computed(() => props.request.status === 'pending' && !payment.value?.receipt_path);

const cancelRequest = () => {
    if (!window.confirm('Are you sure you want to cancel this request?')) return;
    router.post(route('student.requests.cancel', props.request.id));
};
</script>

<template>
    <Head :title="`Request ${request.reference_no}`" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-slate-900">Request {{ request.reference_no }}</h2>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-700">
                    {{ request.status }}
                </span>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Timeline</h3>
                <div class="mt-4">
                    <Timeline :items="timeline" />
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Payment</h3>
                    <p class="mt-3 text-sm text-slate-700">Status: <span class="font-semibold capitalize">{{ payment?.status || 'n/a' }}</span></p>
                    <p class="text-sm text-slate-700">Amount: PHP {{ Number(payment?.total_amount || 0).toFixed(2) }}</p>
                    <p v-if="payment?.receipt_path" class="mt-2 text-sm">
                        <a :href="route('files.payment-receipt', payment.id)" class="text-indigo-600 hover:text-indigo-500">Preview receipt</a>
                    </p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Clearance</h3>
                    <p v-if="!clearance" class="mt-3 text-sm text-slate-500">No clearance record yet.</p>
                    <div v-else class="mt-3 space-y-2 text-sm">
                        <p>Teacher: <span class="font-semibold capitalize">{{ clearance.teacher_status }}</span></p>
                        <p>Dean: <span class="font-semibold capitalize">{{ clearance.dean_status }}</span></p>
                        <p>Accounting: <span class="font-semibold capitalize">{{ clearance.accounting_status }}</span></p>
                        <p>SAO: <span class="font-semibold capitalize">{{ clearance.sao_status }}</span></p>
                        <p>Overall: <span class="font-semibold capitalize">{{ clearance.overall_status }}</span></p>
                    </div>
                </div>
            </section>

            <div v-if="canCancel" class="flex justify-end">
                <button
                    type="button"
                    class="rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500"
                    @click="cancelRequest"
                >
                    Cancel Request
                </button>
            </div>
        </div>
    </StudentLayout>
</template>
