<script setup>
import EmptyState from '@/Components/UI/EmptyState.vue';
import DataTableShell from '@/Components/UI/DataTableShell.vue';
import ResponsiveRecordList from '@/Components/UI/ResponsiveRecordList.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { DocumentTextIcon } from '@heroicons/vue/24/outline';
import { reactive } from 'vue';

const props = defineProps({
    requests: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
});

const filterForm = reactive({
    status: props.filters.status || '',
    from: props.filters.from || '',
    to: props.filters.to || '',
    search: props.filters.search || '',
});

const applyFilters = () => {
    router.get(route('student.requests.index'), filterForm, { preserveState: true, replace: true });
};

const decodeLabel = (label) => label.replace('&laquo;', '').replace('&raquo;', '').trim();
</script>

<template>
    <Head title="My Requests" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-slate-900">My Requests</h2>
                <Link
                    :href="route('student.requests.create')"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2"
                >
                    New Request
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
                <input
                    v-model="filterForm.search"
                    type="text"
                    placeholder="Search reference or document"
                    class="rounded-md border-slate-300 text-sm shadow-sm"
                />
                <select v-model="filterForm.status" class="rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="denied">Denied</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="completed">Completed</option>
                </select>
                <input v-model="filterForm.from" type="date" class="rounded-md border-slate-300 text-sm shadow-sm" />
                <input v-model="filterForm.to" type="date" class="rounded-md border-slate-300 text-sm shadow-sm" />
                <button
                    type="button"
                    class="md:col-span-4 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                    @click="applyFilters"
                >
                    Apply filters
                </button>
            </div>

            <ResponsiveRecordList :empty="requests.data.length === 0">
                <template #empty>
                    <EmptyState
                        title="No requests found"
                        description="Start a new request or adjust your filters to see older submissions."
                        :icon="DocumentTextIcon"
                        compact
                    >
                        <template #actions>
                            <Link
                                :href="route('student.requests.create')"
                                class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500"
                            >
                                New request
                            </Link>
                        </template>
                    </EmptyState>
                </template>

                <template #cards>
                    <article
                        v-for="row in requests.data"
                        :key="row.id"
                        class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-semibold text-slate-950">
                                    {{ row.document_type?.name || 'Document request' }}
                                </h3>
                                <p class="mt-0.5 truncate font-mono text-xs text-slate-500">{{ row.reference_no }}</p>
                            </div>
                            <span
                                class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold capitalize text-slate-700"
                            >
                                {{ row.status }}
                            </span>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                            <div>
                                <dt class="font-medium text-slate-500">Submitted</dt>
                                <dd class="mt-0.5 text-slate-800">{{ row.created_at }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Payment</dt>
                                <dd class="mt-0.5 capitalize text-slate-800">
                                    {{ row.payments?.[0]?.status || 'n/a' }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-4">
                            <Link
                                :href="route('student.requests.show', row.id)"
                                class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-brand-600 px-3 text-sm font-semibold text-white hover:bg-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                            >
                                View details
                            </Link>
                        </div>
                    </article>
                </template>

                <template #table>
                    <DataTableShell label="Student requests table" min-width="min-w-[48rem]">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Reference</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Document</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Payment</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Created</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <tr v-for="row in requests.data" :key="row.id">
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ row.reference_no }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ row.document_type?.name }}</td>
                                    <td class="px-4 py-3 capitalize text-slate-700">{{ row.status }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ row.payments?.[0]?.status || 'n/a' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ row.created_at }}</td>
                                    <td class="px-4 py-3">
                                        <Link
                                            :href="route('student.requests.show', row.id)"
                                            class="font-semibold text-brand-700 hover:text-brand-600"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </DataTableShell>
                </template>
            </ResponsiveRecordList>

            <div v-if="requests.links?.length > 3" class="flex flex-wrap gap-2">
                <Link
                    v-for="link in requests.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="rounded border px-3 py-1 text-sm"
                    :class="
                        link.active ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-slate-300 text-slate-600'
                    "
                >
                    {{ decodeLabel(link.label) }}
                </Link>
            </div>
        </div>
    </StudentLayout>
</template>
