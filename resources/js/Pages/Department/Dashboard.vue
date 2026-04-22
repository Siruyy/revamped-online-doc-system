<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
    pendingLatest: { type: Array, required: true },
    department: { type: String, required: true },
});
</script>

<template>
    <Head title="Department Dashboard" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Department Dashboard</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Pending (your desk)</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ stats.pending }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Signed today</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ stats.signed_today }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Denied (total)</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ stats.denied }}</p>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Latest pending clearances</h3>
                    <Link :href="route('department.clearances.index')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                        View all
                    </Link>
                </div>
                <ul v-if="pendingLatest.length" class="mt-4 divide-y divide-slate-200">
                    <li v-for="row in pendingLatest" :key="row.id" class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <p class="font-semibold text-slate-800">{{ row.document_request?.reference_no }}</p>
                            <p class="text-slate-500">{{ row.user?.fullname }} · {{ row.user?.course }} Year {{ row.user?.year_level }}</p>
                        </div>
                        <Link :href="route('department.clearances.show', row.id)" class="font-semibold text-indigo-600 hover:text-indigo-500">Open</Link>
                    </li>
                </ul>
                <p v-else class="mt-4 text-sm text-slate-500">No pending clearances for your department.</p>
            </section>
        </div>
    </StaffLayout>
</template>
