<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    clearances: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const form = reactive({
    overall_status: props.filters.overall_status || '',
    course: props.filters.course || '',
    year: props.filters.year || '',
});

const applyFilters = () => {
    router.get(route('admin.clearances.index'), form, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Clearance Monitor" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Clearance Monitor</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
                <select v-model="form.overall_status" class="rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="">All statuses</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="denied">Denied</option>
                </select>
                <input
                    v-model="form.course"
                    type="text"
                    placeholder="Course"
                    class="rounded-md border-slate-300 text-sm shadow-sm"
                />
                <input
                    v-model="form.year"
                    type="number"
                    min="1"
                    max="8"
                    placeholder="Year"
                    class="rounded-md border-slate-300 text-sm shadow-sm"
                />
                <button
                    type="button"
                    class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                    @click="applyFilters"
                >
                    Apply
                </button>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Reference</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Student</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Teacher</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Dean</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Accounting</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">SAO</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Overall</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="item in clearances.data" :key="item.id">
                            <td class="px-4 py-3">{{ item.document_request?.reference_no }}</td>
                            <td class="px-4 py-3">{{ item.user?.fullname }}</td>
                            <td class="px-4 py-3 capitalize">{{ item.teacher_status }}</td>
                            <td class="px-4 py-3 capitalize">{{ item.dean_status }}</td>
                            <td class="px-4 py-3 capitalize">{{ item.accounting_status }}</td>
                            <td class="px-4 py-3 capitalize">{{ item.sao_status }}</td>
                            <td class="px-4 py-3 capitalize">{{ item.overall_status }}</td>
                            <td class="px-4 py-3">
                                <Link
                                    :href="route('admin.clearances.show', item.id)"
                                    class="font-semibold text-indigo-600 hover:text-indigo-500"
                                    >View</Link
                                >
                            </td>
                        </tr>
                        <tr v-if="clearances.data.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">No clearances found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </StaffLayout>
</template>
