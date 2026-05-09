<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { ArrowRightIcon, FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    clearances: { type: Object, required: true },
    filters: { type: Object, required: true },
    departmentStatusColumn: { type: String, required: true },
});

const form = reactive({
    status: props.filters.status || 'pending',
    course: props.filters.course || '',
    year: props.filters.year || '',
    search: props.filters.search || '',
});

const applyFilters = () =>
    router.get(route('department.clearances.index'), form, { preserveState: true, replace: true });
const clearFilters = () => {
    Object.keys(form).forEach((k) => (form[k] = k === 'status' ? 'pending' : ''));
    applyFilters();
};

function badge(status) {
    return (
        {
            pending: 'bg-amber-100 text-amber-800',
            cleared: 'bg-emerald-100 text-emerald-800',
            denied: 'bg-rose-100 text-rose-800',
            completed: 'bg-emerald-100 text-emerald-800',
        }[status] ?? 'bg-slate-100 text-slate-600'
    );
}
</script>

<template>
    <Head title="Clearances" />

    <StaffLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-display font-bold text-slate-900">Clearance Queue</h2>
                <p class="text-sm text-slate-500">Review and act on clearance signatures assigned to your office.</p>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-5 px-4 pb-12 sm:px-6 lg:px-8">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="mb-4 flex items-center gap-2">
                    <FunnelIcon class="h-4 w-4 text-slate-500" />
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Filters</p>
                </div>
                <div class="grid gap-3 md:grid-cols-12">
                    <select v-model="form.status" class="rounded-lg border-slate-300 text-sm shadow-sm md:col-span-2">
                        <option value="pending">Pending</option>
                        <option value="cleared">Cleared</option>
                        <option value="denied">Denied</option>
                    </select>
                    <input
                        v-model="form.course"
                        type="text"
                        placeholder="Course"
                        class="rounded-lg border-slate-300 text-sm shadow-sm md:col-span-3"
                    />
                    <input
                        v-model="form.year"
                        type="number"
                        min="1"
                        max="8"
                        placeholder="Year"
                        class="rounded-lg border-slate-300 text-sm shadow-sm md:col-span-1"
                    />
                    <input
                        v-model="form.search"
                        type="text"
                        placeholder="Student name or ID"
                        class="rounded-lg border-slate-300 text-sm shadow-sm md:col-span-4"
                        @keyup.enter="applyFilters"
                    />
                    <div class="md:col-span-2 flex gap-2">
                        <button
                            type="button"
                            class="flex-1 rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-500"
                            @click="applyFilters"
                        >
                            Apply
                        </button>
                    </div>
                </div>
                <button
                    type="button"
                    class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-slate-700"
                    @click="clearFilters"
                >
                    <XMarkIcon class="h-3.5 w-3.5" /> Clear
                </button>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left">Reference</th>
                            <th class="px-5 py-3 text-left">Student</th>
                            <th class="px-5 py-3 text-left">Your Status</th>
                            <th class="px-5 py-3 text-left">Overall</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="row in clearances.data" :key="row.id" class="transition hover:bg-brand-50/30">
                            <td class="px-5 py-3 font-mono text-xs text-slate-700">
                                {{ row.document_request?.reference_no }}
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-slate-900">{{ row.user?.fullname }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ row.user?.course }} · Y{{ row.user?.year_level }}
                                </p>
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                        badge(row[departmentStatusColumn]),
                                    ]"
                                >
                                    {{ row[departmentStatusColumn] }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                        badge(row.overall_status),
                                    ]"
                                >
                                    {{ row.overall_status?.replaceAll('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <Link
                                    :href="route('department.clearances.show', row.id)"
                                    class="inline-flex items-center gap-1 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700"
                                >
                                    Open <ArrowRightIcon class="h-3.5 w-3.5" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="clearances.data.length === 0">
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">
                                No clearances match these filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </StaffLayout>
</template>
