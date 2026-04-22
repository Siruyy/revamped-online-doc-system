<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    clearance: { type: Object, required: true },
});
</script>

<template>
    <Head :title="`Clearance ${clearance.document_request?.reference_no}`" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">
                Clearance {{ clearance.document_request?.reference_no }}
            </h2>
        </template>

        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Student</h3>
                <p class="mt-2 text-sm text-slate-700">{{ clearance.user?.fullname }} ({{ clearance.user?.email }})</p>
                <p class="text-sm text-slate-700">Course: {{ clearance.user?.course }} | Year {{ clearance.user?.year_level }}</p>
                <p class="text-sm text-slate-700">Request stage: {{ clearance.document_request?.processing_stage }}</p>
                <p class="text-sm text-slate-700">Purpose: {{ clearance.document_request?.purpose || 'N/A' }}</p>
            </section>

            <section class="grid gap-4 md:grid-cols-2">
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm font-semibold text-slate-700">Teacher</p>
                    <p class="mt-1 text-sm capitalize">{{ clearance.teacher_status }}</p>
                    <p class="text-xs text-slate-500">Signer: {{ clearance.teacher_signer?.fullname || 'N/A' }}</p>
                    <p class="text-xs text-slate-500">Signed at: {{ clearance.teacher_signed_at || 'N/A' }}</p>
                    <p v-if="clearance.teacher_remarks" class="text-xs text-rose-600">Remarks: {{ clearance.teacher_remarks }}</p>
                </article>
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm font-semibold text-slate-700">Dean</p>
                    <p class="mt-1 text-sm capitalize">{{ clearance.dean_status }}</p>
                    <p class="text-xs text-slate-500">Signer: {{ clearance.dean_signer?.fullname || 'N/A' }}</p>
                    <p class="text-xs text-slate-500">Signed at: {{ clearance.dean_signed_at || 'N/A' }}</p>
                    <p v-if="clearance.dean_remarks" class="text-xs text-rose-600">Remarks: {{ clearance.dean_remarks }}</p>
                </article>
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm font-semibold text-slate-700">Accounting</p>
                    <p class="mt-1 text-sm capitalize">{{ clearance.accounting_status }}</p>
                    <p class="text-xs text-slate-500">Signer: {{ clearance.accounting_signer?.fullname || 'N/A' }}</p>
                    <p class="text-xs text-slate-500">Signed at: {{ clearance.accounting_signed_at || 'N/A' }}</p>
                    <p v-if="clearance.accounting_remarks" class="text-xs text-rose-600">Remarks: {{ clearance.accounting_remarks }}</p>
                </article>
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm font-semibold text-slate-700">SAO</p>
                    <p class="mt-1 text-sm capitalize">{{ clearance.sao_status }}</p>
                    <p class="text-xs text-slate-500">Signer: {{ clearance.sao_signer?.fullname || 'N/A' }}</p>
                    <p class="text-xs text-slate-500">Signed at: {{ clearance.sao_signed_at || 'N/A' }}</p>
                    <p v-if="clearance.sao_remarks" class="text-xs text-rose-600">Remarks: {{ clearance.sao_remarks }}</p>
                </article>
            </section>
        </div>
    </StaffLayout>
</template>
