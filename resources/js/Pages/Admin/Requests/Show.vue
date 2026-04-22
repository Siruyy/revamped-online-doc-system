<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    request: { type: Object, required: true },
    batchRequests: { type: Array, required: true },
});

const denyForm = useForm({ denial_reason: '' });
const stageForm = useForm({ processing_stage: props.request.processing_stage || 'processing' });

const canApprove = computed(() => props.request.status === 'pending');
const canDeny = computed(() => ['pending', 'approved'].includes(props.request.status));
const canUpdateStage = computed(() => props.request.status === 'approved');

const approve = () => router.post(route('admin.requests.approve', props.request.id));
const deny = () => denyForm.post(route('admin.requests.deny', props.request.id));
const updateStage = () => stageForm.post(route('admin.requests.stage', props.request.id));
</script>

<template>
    <Head :title="`Request ${request.reference_no}`" />

    <StaffLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-slate-900">Request {{ request.reference_no }}</h2>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-700">{{ request.status }}</span>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Student Information</h3>
                <p class="mt-2 text-sm text-slate-700">{{ request.user?.fullname }} ({{ request.user?.email }})</p>
                <p class="text-sm text-slate-700">Course: {{ request.user?.course }} | Year {{ request.user?.year_level }}</p>
                <p class="text-sm text-slate-700">Student ID: {{ request.user?.student_id }}</p>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Linked Batch Requests</h3>
                <ul class="mt-3 divide-y divide-slate-200">
                    <li v-for="item in batchRequests" :key="item.id" class="py-3 text-sm">
                        <p class="font-semibold text-slate-800">{{ item.reference_no }} — {{ item.document_type?.name }}</p>
                        <p class="text-slate-500">Status: {{ item.status }} | Stage: {{ item.processing_stage }}</p>
                    </li>
                </ul>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <button
                    v-if="canApprove"
                    type="button"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                    @click="approve"
                >
                    Approve Request
                </button>

                <form v-if="canDeny" class="space-y-2 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:col-span-2" @submit.prevent="deny">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Deny Request</label>
                    <textarea v-model="denyForm.denial_reason" rows="2" class="block w-full rounded-md border-slate-300 text-sm shadow-sm" />
                    <button type="submit" class="rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500">Deny</button>
                </form>
            </section>

            <section v-if="canUpdateStage" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Processing Stage</h3>
                <form class="mt-3 flex flex-wrap items-center gap-3" @submit.prevent="updateStage">
                    <select v-model="stageForm.processing_stage" class="rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="processing">Processing</option>
                        <option value="ready_for_pickup">Ready for pickup</option>
                        <option value="released">Released</option>
                    </select>
                    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Update Stage</button>
                </form>
            </section>
        </div>
    </StaffLayout>
</template>
