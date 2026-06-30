<script setup>
import EmptyState from '@/Components/UI/EmptyState.vue';
import DataTableShell from '@/Components/UI/DataTableShell.vue';
import ResponsiveRecordList from '@/Components/UI/ResponsiveRecordList.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { CheckBadgeIcon } from '@heroicons/vue/24/outline';
import { reactive } from 'vue';

const props = defineProps({
    clearances: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const form = reactive({
    overall_status: props.filters.overall_status || '',
    course: props.filters.course || '',
    year: props.filters.year || '',
    search: props.filters.search || '',
});

const applyFilters = () => {
    router.get(route('admin.clearances.index'), form, { preserveState: true, replace: true });
};

const statusTone = (status) => {
    if (['cleared', 'completed', 'approved'].includes(status)) return 'success';
    if (['denied', 'rejected'].includes(status)) return 'danger';
    if (['pending', 'in_progress'].includes(status)) return 'warning';

    return 'neutral';
};

const statusLabel = (status) => status?.replaceAll('_', ' ') || 'N/A';
const isPublicClearance = (item) => !item.user_id;
const requestorName = (item) => item.user?.fullname || item.document_request?.requester_name || 'Public requestor';
const requestorCourseYear = (item) => {
    const course = item.user?.course || item.document_request?.requester_course || 'N/A';
    const year = item.user?.year_level || item.document_request?.requester_year_level || 'N/A';

    return `${course} · Y${year}`;
};
</script>

<template>
    <Head title="Clearance Monitor" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Clearance Monitor</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-sky-50 px-4 py-3 text-sm leading-6 text-sky-900 ring-1 ring-sky-100">
                Public request clearances are handled internally by staff. Completed public clearance PDFs are private
                staff downloads only and are not exposed through public tracking.
            </div>

            <div class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-5">
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
                <input
                    v-model="form.search"
                    type="text"
                    placeholder="Name, student ID, or ref"
                    class="rounded-md border-slate-300 text-sm shadow-sm"
                    @keyup.enter="applyFilters"
                />
                <button
                    type="button"
                    class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                    @click="applyFilters"
                >
                    Apply
                </button>
            </div>

            <ResponsiveRecordList :empty="clearances.data.length === 0">
                <template #empty>
                    <EmptyState
                        title="No clearances found"
                        description="Clearance records will appear after approved requests require department signing."
                        :icon="CheckBadgeIcon"
                        compact
                    />
                </template>

                <template #cards>
                    <article
                        v-for="item in clearances.data"
                        :key="item.id"
                        class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="truncate text-sm font-semibold text-slate-950">
                                        {{ requestorName(item) }}
                                    </h3>
                                    <span
                                        v-if="isPublicClearance(item)"
                                        class="shrink-0 rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-800"
                                    >
                                        Public
                                    </span>
                                </div>
                                <p class="mt-0.5 truncate font-mono text-xs text-slate-500">
                                    {{ item.document_request?.reference_no }}
                                </p>
                            </div>
                            <StatusBadge
                                class="shrink-0"
                                :tone="statusTone(item.overall_status)"
                                :label="statusLabel(item.overall_status)"
                            />
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                            <div>
                                <dt class="font-medium text-slate-500">Request</dt>
                                <dd class="mt-0.5 capitalize text-slate-800">
                                    {{ item.document_request?.status || '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Updated</dt>
                                <dd class="mt-0.5 text-slate-800">{{ item.updated_at || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Teacher</dt>
                                <dd class="mt-0.5">
                                    <StatusBadge
                                        :tone="statusTone(item.teacher_status)"
                                        :label="statusLabel(item.teacher_status)"
                                        size="xs"
                                    />
                                </dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Dean</dt>
                                <dd class="mt-0.5">
                                    <StatusBadge
                                        :tone="statusTone(item.dean_status)"
                                        :label="statusLabel(item.dean_status)"
                                        size="xs"
                                    />
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-4">
                            <Link
                                :href="route('admin.clearances.show', item.id)"
                                class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-indigo-600 px-3 text-sm font-semibold text-white hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                            >
                                View clearance
                            </Link>
                        </div>
                    </article>
                </template>

                <template #table>
                    <DataTableShell label="Admin clearances table" min-width="min-w-[64rem]">
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
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <p class="font-semibold text-slate-900">{{ requestorName(item) }}</p>
                                            <span
                                                v-if="isPublicClearance(item)"
                                                class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-800"
                                            >
                                                Public
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500">{{ requestorCourseYear(item) }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge
                                            :tone="statusTone(item.teacher_status)"
                                            :label="statusLabel(item.teacher_status)"
                                            size="xs"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge
                                            :tone="statusTone(item.dean_status)"
                                            :label="statusLabel(item.dean_status)"
                                            size="xs"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge
                                            :tone="statusTone(item.accounting_status)"
                                            :label="statusLabel(item.accounting_status)"
                                            size="xs"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge
                                            :tone="statusTone(item.sao_status)"
                                            :label="statusLabel(item.sao_status)"
                                            size="xs"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge
                                            :tone="statusTone(item.overall_status)"
                                            :label="statusLabel(item.overall_status)"
                                            size="xs"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <Link
                                            :href="route('admin.clearances.show', item.id)"
                                            class="font-semibold text-indigo-600 hover:text-indigo-500"
                                            >View</Link
                                        >
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </DataTableShell>
                </template>
            </ResponsiveRecordList>
        </div>
    </StaffLayout>
</template>
