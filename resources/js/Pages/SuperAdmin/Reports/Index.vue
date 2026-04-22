<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    filters: { type: Object, required: true },
    requestsByStatus: { type: Object, required: true },
    paymentsByStatus: { type: Object, required: true },
    clearancesByOverall: { type: Object, required: true },
    registrationsByStatus: { type: Object, required: true },
});

const form = useForm({
    from: props.filters.from,
    to: props.filters.to,
});

const apply = () => {
    form.get(route('superadmin.reports.index'), { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Reports" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">System reports</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <p class="text-sm text-slate-600">
                Summary counts for the selected date range. Excel exports are planned for Phase 09.
            </p>

            <form
                class="flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
                @submit.prevent="apply"
            >
                <div>
                    <label class="block text-xs font-medium text-slate-600">From</label>
                    <input v-model="form.from" type="date" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">To</label>
                    <input v-model="form.to" type="date" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm" />
                </div>
                <button
                    type="submit"
                    class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Apply range
                </button>
                <Link
                    :href="route('superadmin.reports.index')"
                    class="text-sm font-semibold text-slate-600 hover:text-slate-900"
                    >Reset</Link
                >
            </form>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Document requests</h3>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li
                            v-for="(count, status) in requestsByStatus"
                            :key="status"
                            class="flex justify-between capitalize"
                        >
                            <span>{{ status }}</span>
                            <strong>{{ count }}</strong>
                        </li>
                        <li v-if="Object.keys(requestsByStatus).length === 0" class="text-slate-500">
                            No data in range.
                        </li>
                    </ul>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Payments</h3>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li
                            v-for="(count, status) in paymentsByStatus"
                            :key="status"
                            class="flex justify-between capitalize"
                        >
                            <span>{{ status.replace(/_/g, ' ') }}</span>
                            <strong>{{ count }}</strong>
                        </li>
                        <li v-if="Object.keys(paymentsByStatus).length === 0" class="text-slate-500">
                            No data in range.
                        </li>
                    </ul>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Clearances</h3>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li
                            v-for="(count, overallKey) in clearancesByOverall"
                            :key="overallKey"
                            class="flex justify-between capitalize"
                        >
                            <span>{{ overallKey.replace(/_/g, ' ') }}</span>
                            <strong>{{ count }}</strong>
                        </li>
                        <li v-if="Object.keys(clearancesByOverall).length === 0" class="text-slate-500">
                            No data in range.
                        </li>
                    </ul>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">
                        New student registrations
                    </h3>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li
                            v-for="(count, status) in registrationsByStatus"
                            :key="status"
                            class="flex justify-between capitalize"
                        >
                            <span>{{ status }}</span>
                            <strong>{{ count }}</strong>
                        </li>
                        <li v-if="Object.keys(registrationsByStatus).length === 0" class="text-slate-500">
                            No data in range.
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </StaffLayout>
</template>
