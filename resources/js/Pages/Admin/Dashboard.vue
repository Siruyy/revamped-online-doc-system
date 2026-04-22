<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    requestCounts: { type: Object, required: true },
    paymentCounts: { type: Object, required: true },
    todaySubmissions: { type: Number, required: true },
    pendingQueue: { type: Array, required: true },
});
</script>

<template>
    <Head title="Admin Dashboard" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Admin Dashboard</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Pending Requests</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ requestCounts.pending }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Pending Payments</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ paymentCounts.pending_approval }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Completed Requests</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ requestCounts.completed }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Today's Submissions</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ todaySubmissions }}</p>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Pending Action Queue</h3>
                <div v-if="pendingQueue.length === 0" class="mt-3 text-sm text-slate-500">No pending requests right now.</div>
                <ul v-else class="mt-3 divide-y divide-slate-200">
                    <li v-for="item in pendingQueue" :key="item.id" class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <p class="font-semibold text-slate-800">{{ item.reference_no }} — {{ item.document_type?.name }}</p>
                            <p class="text-slate-500">{{ item.user?.fullname }} | {{ item.user?.course }} Year {{ item.user?.year_level }}</p>
                        </div>
                        <a :href="route('admin.requests.show', item.id)" class="font-semibold text-indigo-600 hover:text-indigo-500">Review</a>
                    </li>
                </ul>
            </section>
        </div>
    </StaffLayout>
</template>
