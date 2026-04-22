<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

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

const applyFilters = () => {
    router.get(route('department.clearances.index'), form, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Clearances" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Clearances</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-5">
                <select v-model="form.status" class="rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="pending">Pending</option>
                    <option value="cleared">Cleared</option>
                    <option value="denied">Denied</option>
                </select>
                <input v-model="form.course" type="text" placeholder="Course" class="rounded-md border-slate-300 text-sm shadow-sm" />
                <input v-model="form.year" type="number" min="1" max="8" placeholder="Year" class="rounded-md border-slate-300 text-sm shadow-sm" />
                <input v-model="form.search" type="text" placeholder="Student name or ID" class="rounded-md border-slate-300 text-sm shadow-sm" />
                <button
                    type="button"
                    class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 md:col-span-5"
                    @click="applyFilters"
                >
                    Apply filters
                </button>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Reference</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Student</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Your status</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Overall</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="row in clearances.data" :key="row.id">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ row.document_request?.reference_no }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ row.user?.fullname }}</td>
                            <td class="px-4 py-3 capitalize text-slate-700">{{ row[departmentStatusColumn] }}</td>
                            <td class="px-4 py-3 capitalize text-slate-700">{{ row.overall_status }}</td>
                            <td class="px-4 py-3">
                                <Link :href="route('department.clearances.show', row.id)" class="font-semibold text-indigo-600 hover:text-indigo-500">Open</Link>
                            </td>
                        </tr>
                        <tr v-if="clearances.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No clearances found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </StaffLayout>
</template>
