<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    requests: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const form = reactive({
    status: props.filters.status || '',
    course: props.filters.course || '',
    year: props.filters.year || '',
    document_type: props.filters.document_type || '',
    from: props.filters.from || '',
    to: props.filters.to || '',
    search: props.filters.search || '',
});

const applyFilters = () => {
    router.get(route('admin.requests.index'), form, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Manage Requests" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Requests Management</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
                <input v-model="form.search" type="text" placeholder="Search reference, student, document" class="rounded-md border-slate-300 text-sm shadow-sm md:col-span-2" />
                <select v-model="form.status" class="rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="denied">Denied</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <input v-model="form.course" type="text" placeholder="Course" class="rounded-md border-slate-300 text-sm shadow-sm" />
                <input v-model="form.year" type="number" min="1" max="8" placeholder="Year" class="rounded-md border-slate-300 text-sm shadow-sm" />
                <input v-model="form.document_type" type="number" min="1" placeholder="Document Type ID" class="rounded-md border-slate-300 text-sm shadow-sm" />
                <input v-model="form.from" type="date" class="rounded-md border-slate-300 text-sm shadow-sm" />
                <input v-model="form.to" type="date" class="rounded-md border-slate-300 text-sm shadow-sm" />
                <button type="button" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 md:col-span-4" @click="applyFilters">
                    Apply filters
                </button>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Reference</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Student</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Document</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Payment</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="item in requests.data" :key="item.id">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ item.reference_no }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ item.user?.fullname }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ item.document_type?.name }}</td>
                            <td class="px-4 py-3 capitalize text-slate-700">{{ item.status }}</td>
                            <td class="px-4 py-3 capitalize text-slate-700">{{ item.payments?.[0]?.status || 'n/a' }}</td>
                            <td class="px-4 py-3">
                                <Link :href="route('admin.requests.show', item.id)" class="font-semibold text-indigo-600 hover:text-indigo-500">Open</Link>
                            </td>
                        </tr>
                        <tr v-if="requests.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No requests found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </StaffLayout>
</template>
