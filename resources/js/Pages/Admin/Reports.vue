<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    summary: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const form = reactive({
    from: props.filters.from || '',
    to: props.filters.to || '',
    status: props.filters.status || '',
    course: props.filters.course || '',
});

const applyFilters = () => {
    router.get(route('admin.reports.index'), form, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Reports" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Reports</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
                <input v-model="form.from" type="date" class="rounded-md border-slate-300 text-sm shadow-sm" />
                <input v-model="form.to" type="date" class="rounded-md border-slate-300 text-sm shadow-sm" />
                <select v-model="form.status" class="rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="denied">Denied</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <input
                    v-model="form.course"
                    type="text"
                    placeholder="Course"
                    class="rounded-md border-slate-300 text-sm shadow-sm"
                />
                <button
                    type="button"
                    class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 md:col-span-4"
                    @click="applyFilters"
                >
                    Generate
                </button>
            </section>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Requests Total</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.requests_total }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Requests Completed</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.requests_completed }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Requests Denied</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.requests_denied }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Payments Total</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.payments_total }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Payments Approved</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.payments_approved }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Payments Denied</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.payments_denied }}</p>
                </div>
            </section>
        </div>
    </StaffLayout>
</template>
