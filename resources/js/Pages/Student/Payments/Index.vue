<script setup>
import FileUpload from '@/Components/FileUpload.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const { payments } = defineProps({
    payments: {
        type: Object,
        required: true,
    },
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
</script>

<template>
    <Head title="Payments" />

    <StudentLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Payments</h2>
        </template>

        <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
            <div v-if="payments.data.length === 0" class="rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">
                You have no pending payments.
            </div>

            <div v-for="payment in payments.data" :key="payment.id" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="font-semibold text-slate-900">{{ payment.document_request?.reference_no || 'Request payment' }}</p>
                    <p class="text-sm text-slate-600">Amount: PHP {{ Number(payment.total_amount).toFixed(2) }}</p>
                </div>

                <form class="mt-4 space-y-4" @submit.prevent="submit(payment.id)">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Payment Method</label>
                            <select v-model="getForm(payment.id).payment_method" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                                <option value="">Select payment method</option>
                                <option value="Cash">Cash</option>
                                <option value="GCash">GCash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                            <InputError class="mt-2" :message="getForm(payment.id).errors.payment_method" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Reference Number (Optional)</label>
                            <input
                                v-model="getForm(payment.id).reference_number"
                                type="text"
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm"
                            />
                            <InputError class="mt-2" :message="getForm(payment.id).errors.reference_number" />
                        </div>
                    </div>

                    <FileUpload
                        v-model="getForm(payment.id).receipt"
                        label="Payment Receipt"
                        :error="getForm(payment.id).errors.receipt"
                        accept="image/jpeg,image/png,application/pdf"
                    />

                    <PrimaryButton :disabled="getForm(payment.id).processing">Upload Receipt</PrimaryButton>
                </form>
            </div>
        </div>
    </StudentLayout>
</template>
