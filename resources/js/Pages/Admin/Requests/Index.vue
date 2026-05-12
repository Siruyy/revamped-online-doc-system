<script setup>
import EmptyState from '@/Components/UI/EmptyState.vue';
import DataTableShell from '@/Components/UI/DataTableShell.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import ResponsiveRecordList from '@/Components/UI/ResponsiveRecordList.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { useEchoPrivateChannel } from '@/Composables/useEchoPrivateChannel';
import { useRealtimeOrPoll } from '@/Composables/useRealtimeOrPoll';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import {
    ArrowRightIcon,
    ArrowsRightLeftIcon,
    BoltIcon,
    DocumentMagnifyingGlassIcon,
    ExclamationTriangleIcon,
    FunnelIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    requests: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const page = usePage();
const isAdmin = computed(() => ['admin', 'superadmin'].includes(page.props.auth?.user?.role ?? ''));

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

const clearFilters = () => {
    Object.keys(form).forEach((key) => (form[key] = ''));
    applyFilters();
};

const reloadRequests = () => {
    router.reload({ only: ['requests', 'filters'], preserveScroll: true });
};

useEchoPrivateChannel(() => (isAdmin.value ? 'role.admin' : null), {
    RequestSubmitted: reloadRequests,
    PaymentSubmitted: reloadRequests,
    ClearanceUpdated: reloadRequests,
});

useRealtimeOrPoll(reloadRequests, { intervalMs: 90000 });

function statusTone(status) {
    return (
        {
            pending: 'warning',
            approved: 'info',
            completed: 'success',
            denied: 'danger',
            cancelled: 'neutral',
        }[status] ?? 'neutral'
    );
}

function paymentTone(status) {
    return (
        {
            pending: 'neutral',
            pending_approval: 'warning',
            approved: 'success',
            denied: 'danger',
        }[status] ?? 'neutral'
    );
}

function isOverdue(expected) {
    if (!expected) return false;
    return new Date(expected) < new Date(new Date().toDateString());
}

function relativeAge(value) {
    if (!value) return '—';
    const diff = Math.floor((Date.now() - new Date(value).getTime()) / 86400000);
    if (diff <= 0) return 'today';
    if (diff === 1) return '1d';
    if (diff < 7) return `${diff}d`;
    return `${Math.round(diff / 7)}w`;
}
</script>

<template>
    <Head title="Manage Requests" />

    <StaffLayout>
        <template #header>
            <PageHeader
                title="Document Requests"
                subtitle="Triage, approve, and manage document requests across all students."
            >
                <template #actions>
                    <Link
                        :href="route('admin.dashboard')"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-brand-700 hover:bg-brand-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                    >
                        Back to dashboard →
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6 pb-12">
            <!-- Filters -->
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="mb-4 flex items-center gap-2">
                    <FunnelIcon class="h-4 w-4 text-slate-500" />
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Filters</p>
                </div>
                <div class="grid gap-3 md:grid-cols-12">
                    <div class="relative md:col-span-4">
                        <DocumentMagnifyingGlassIcon
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        />
                        <input
                            v-model="form.search"
                            type="text"
                            placeholder="Search ref, student, document…"
                            class="block w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <select
                        v-model="form.status"
                        class="md:col-span-2 rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
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
                        class="md:col-span-2 rounded-lg border-slate-300 text-sm shadow-sm"
                    />
                    <input
                        v-model="form.year"
                        type="number"
                        min="1"
                        max="8"
                        placeholder="Year"
                        class="md:col-span-1 rounded-lg border-slate-300 text-sm shadow-sm"
                    />
                    <input
                        v-model="form.from"
                        type="date"
                        class="md:col-span-1 rounded-lg border-slate-300 text-sm shadow-sm"
                    />
                    <input
                        v-model="form.to"
                        type="date"
                        class="md:col-span-1 rounded-lg border-slate-300 text-sm shadow-sm"
                    />
                    <div class="flex gap-2 md:col-span-1">
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
                    <XMarkIcon class="h-3.5 w-3.5" /> Clear all
                </button>
            </div>

            <!-- Results -->
            <ResponsiveRecordList :empty="requests.data.length === 0">
                <template #empty>
                    <EmptyState
                        title="No requests match your filters"
                        description="Try clearing filters or widening your date range. New submissions will appear here automatically."
                        :icon="BoltIcon"
                        variant="panel"
                        compact
                    >
                        <template #actions>
                            <button
                                type="button"
                                class="inline-flex min-h-11 items-center gap-1 rounded-lg border border-slate-200 px-3 text-xs font-semibold text-brand-700 hover:bg-brand-50"
                                @click="clearFilters"
                            >
                                <ArrowsRightLeftIcon class="h-3.5 w-3.5" /> Reset filters
                            </button>
                        </template>
                    </EmptyState>
                </template>

                <template #cards>
                    <article
                        v-for="item in requests.data"
                        :key="item.id"
                        class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-semibold text-slate-950">{{ item.user?.fullname }}</h3>
                                <p class="mt-0.5 truncate text-xs text-slate-500">
                                    {{ item.document_type?.name }} · {{ item.reference_no }}
                                </p>
                            </div>
                            <StatusBadge :label="item.status" :tone="statusTone(item.status)" class="shrink-0" />
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                            <div>
                                <dt class="font-medium text-slate-500">Submitted</dt>
                                <dd class="mt-0.5 text-slate-800">{{ relativeAge(item.created_at) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Payment</dt>
                                <dd class="mt-0.5 text-slate-800">
                                    {{ item.payments?.[0]?.status?.replaceAll('_', ' ') || 'n/a' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Student</dt>
                                <dd class="mt-0.5 text-slate-800">
                                    {{ item.user?.course || '—' }} Y{{ item.user?.year_level || '?' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">SLA</dt>
                                <dd class="mt-0.5 text-slate-800">
                                    <span v-if="isOverdue(item.expected_release_on)" class="font-semibold text-rose-700"
                                        >Overdue</span
                                    >
                                    <span v-else-if="item.expected_release_on">
                                        {{
                                            new Date(item.expected_release_on).toLocaleDateString('en-US', {
                                                month: 'short',
                                                day: 'numeric',
                                            })
                                        }}
                                    </span>
                                    <span v-else>—</span>
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-4">
                            <Link
                                :href="route('admin.requests.show', item.id)"
                                class="inline-flex min-h-11 w-full items-center justify-center gap-1 rounded-lg bg-slate-900 px-3 text-sm font-semibold text-white hover:bg-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900"
                            >
                                Review <ArrowRightIcon class="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    </article>
                </template>

                <template #table>
                    <DataTableShell label="Admin requests table" min-width="min-w-[64rem]">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 text-left">Reference</th>
                                    <th class="px-5 py-3 text-left">Student</th>
                                    <th class="px-5 py-3 text-left">Document</th>
                                    <th class="px-5 py-3 text-left">Status</th>
                                    <th class="px-5 py-3 text-left">Payment</th>
                                    <th class="px-5 py-3 text-left">Age</th>
                                    <th class="px-5 py-3 text-left">SLA</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="item in requests.data"
                                    :key="item.id"
                                    class="transition hover:bg-brand-50/30"
                                >
                                    <td class="px-5 py-3 font-mono text-xs text-slate-700">{{ item.reference_no }}</td>
                                    <td class="px-5 py-3">
                                        <p class="font-semibold text-slate-900">{{ item.user?.fullname }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ item.user?.course || '—' }} Y{{ item.user?.year_level || '?' }}
                                        </p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-slate-900">{{ item.document_type?.name }}</p>
                                        <p class="text-xs text-slate-500">{{ item.document_type?.category }}</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <StatusBadge :label="item.status" :tone="statusTone(item.status)" />
                                    </td>
                                    <td class="px-5 py-3">
                                        <StatusBadge
                                            v-if="item.payments?.[0]"
                                            :label="item.payments[0].status?.replaceAll('_', ' ')"
                                            :tone="paymentTone(item.payments[0].status)"
                                        />
                                        <span v-else class="text-xs text-slate-400">n/a</span>
                                    </td>
                                    <td class="px-5 py-3 text-xs text-slate-600">{{ relativeAge(item.created_at) }}</td>
                                    <td class="px-5 py-3">
                                        <span
                                            v-if="isOverdue(item.expected_release_on)"
                                            class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700"
                                        >
                                            <ExclamationTriangleIcon class="h-3 w-3" /> Overdue
                                        </span>
                                        <span v-else-if="item.expected_release_on" class="text-xs text-slate-600">
                                            {{
                                                new Date(item.expected_release_on).toLocaleDateString('en-US', {
                                                    month: 'short',
                                                    day: 'numeric',
                                                })
                                            }}
                                        </span>
                                        <span v-else class="text-xs text-slate-400">—</span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <Link
                                            :href="route('admin.requests.show', item.id)"
                                            class="inline-flex items-center gap-1 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700"
                                        >
                                            Open <ArrowRightIcon class="h-3.5 w-3.5" />
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </DataTableShell>
                </template>
            </ResponsiveRecordList>

            <Pagination :meta="requests" label="Requests pagination" />
        </div>
    </StaffLayout>
</template>
