<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRightIcon,
    ArrowTrendingUpIcon,
    BoltIcon,
    CalendarDaysIcon,
    CheckBadgeIcon,
    ClipboardDocumentListIcon,
    ClockIcon,
    CreditCardIcon,
    ExclamationTriangleIcon,
    InboxStackIcon,
    TicketIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    requestCounts: { type: Object, required: true },
    paymentCounts: { type: Object, required: true },
    todaySubmissions: { type: Number, required: true },
    overdueCount: { type: Number, default: 0 },
    dueTodayCount: { type: Number, default: 0 },
    missingRequirementsCount: { type: Number, default: 0 },
    readyForPickup: { type: Number, default: 0 },
    pendingQueue: { type: Array, required: true },
    overdueRequests: { type: Array, default: () => [] },
    claimToday: { type: Array, default: () => [] },
});

function relativeAge(value) {
    if (!value) return '—';
    const now = new Date();
    const then = new Date(value);
    const diff = Math.floor((now - then) / 86400000);
    if (diff <= 0) return 'today';
    if (diff === 1) return '1 day';
    if (diff < 7) return `${diff} days`;
    return `${Math.round(diff / 7)} wks`;
}
</script>

<template>
    <Head title="Admin Dashboard" />

    <StaffLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-display font-bold text-slate-900">Operations Dashboard</h2>
                <p class="text-sm text-slate-500">
                    Triage requests, monitor SLAs, and clear bottlenecks across the registrar pipeline.
                </p>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-8 px-4 pb-12 sm:px-6 lg:px-8">
            <!-- KPI strip -->
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    :href="route('admin.requests.index', { status: 'pending' })"
                    class="group rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-slate-500">Pending review</p>
                            <p class="mt-2 text-3xl font-display font-bold text-slate-900">
                                {{ requestCounts.pending }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-amber-100 p-2.5 text-amber-700">
                            <InboxStackIcon class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs font-medium text-amber-700 group-hover:underline">Open queue →</p>
                </Link>

                <Link
                    :href="route('admin.payments.index', { status: 'pending_approval' })"
                    class="group rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-slate-500">Payment proofs</p>
                            <p class="mt-2 text-3xl font-display font-bold text-slate-900">
                                {{ paymentCounts.pending_approval }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-sky-100 p-2.5 text-sky-700">
                            <CreditCardIcon class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs font-medium text-sky-700 group-hover:underline">Verify receipts →</p>
                </Link>

                <Link
                    :href="route('admin.requests.index', { status: 'approved' })"
                    class="group rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-slate-500">Overdue SLA</p>
                            <p class="mt-2 text-3xl font-display font-bold text-rose-600">{{ overdueCount }}</p>
                        </div>
                        <div class="rounded-xl bg-rose-100 p-2.5 text-rose-700">
                            <ExclamationTriangleIcon class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs font-medium text-rose-700 group-hover:underline">Review overdue →</p>
                </Link>

                <Link
                    :href="route('admin.requests.index', { status: 'approved' })"
                    class="group rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-slate-500">Ready for pickup</p>
                            <p class="mt-2 text-3xl font-display font-bold text-emerald-600">{{ readyForPickup }}</p>
                        </div>
                        <div class="rounded-xl bg-emerald-100 p-2.5 text-emerald-700">
                            <TicketIcon class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs font-medium text-emerald-700 group-hover:underline">Manage releases →</p>
                </Link>
            </section>

            <!-- Secondary KPIs -->
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-gradient-to-br from-brand-600 to-indigo-700 p-5 text-white shadow-md">
                    <div class="flex items-center gap-3">
                        <ArrowTrendingUpIcon class="h-6 w-6 opacity-80" />
                        <p class="text-xs uppercase tracking-widest opacity-90">Today's intake</p>
                    </div>
                    <p class="mt-3 text-3xl font-display font-bold">{{ todaySubmissions }}</p>
                    <p class="mt-1 text-xs opacity-90">requests submitted today</p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-3">
                        <CalendarDaysIcon class="h-5 w-5 text-amber-600" />
                        <p class="text-xs uppercase tracking-wider text-slate-500">Due today</p>
                    </div>
                    <p class="mt-3 text-2xl font-display font-bold text-slate-900">{{ dueTodayCount }}</p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-3">
                        <ClipboardDocumentListIcon class="h-5 w-5 text-rose-600" />
                        <p class="text-xs uppercase tracking-wider text-slate-500">Missing attachments</p>
                    </div>
                    <p class="mt-3 text-2xl font-display font-bold text-slate-900">{{ missingRequirementsCount }}</p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-3">
                        <CheckBadgeIcon class="h-5 w-5 text-emerald-600" />
                        <p class="text-xs uppercase tracking-wider text-slate-500">Completed</p>
                    </div>
                    <p class="mt-3 text-2xl font-display font-bold text-slate-900">{{ requestCounts.completed }}</p>
                </div>
            </section>

            <!-- Two-column ops panels -->
            <section class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 lg:col-span-2">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <div class="flex items-center gap-2">
                            <BoltIcon class="h-5 w-5 text-brand-600" />
                            <h3 class="font-display text-lg font-semibold text-slate-900">Triage Queue</h3>
                        </div>
                        <Link
                            :href="route('admin.requests.index', { status: 'pending' })"
                            class="text-xs font-semibold text-brand-700 hover:underline"
                        >
                            View all →
                        </Link>
                    </div>
                    <div v-if="pendingQueue.length === 0" class="px-6 py-10 text-center text-sm text-slate-500">
                        No pending requests right now.
                    </div>
                    <ul v-else class="divide-y divide-slate-100">
                        <li
                            v-for="item in pendingQueue"
                            :key="item.id"
                            class="flex items-center justify-between gap-3 px-6 py-3 transition hover:bg-brand-50/40"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate font-semibold text-slate-900">{{ item.document_type?.name }}</p>
                                    <span
                                        v-if="item.requirements?.some((r) => r.status === 'missing')"
                                        class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-rose-700"
                                    >
                                        Missing reqs
                                    </span>
                                </div>
                                <p class="truncate text-xs text-slate-500">
                                    <span class="font-mono">{{ item.reference_no }}</span> · {{ item.user?.fullname }} ·
                                    {{ item.user?.course || '—' }} Y{{ item.user?.year_level || '?' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase text-slate-600"
                                >
                                    <ClockIcon class="mr-1 inline-block h-3 w-3" />
                                    {{ relativeAge(item.created_at) }}
                                </span>
                                <Link
                                    :href="route('admin.requests.show', item.id)"
                                    class="inline-flex items-center gap-1 rounded-lg bg-brand-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-brand-500"
                                >
                                    Review <ArrowRightIcon class="h-3.5 w-3.5" />
                                </Link>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <div class="flex items-center gap-2">
                            <TicketIcon class="h-5 w-5 text-emerald-600" />
                            <h3 class="font-display text-lg font-semibold text-slate-900">Pickups Today</h3>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Students expected to claim documents today.</p>
                    </div>
                    <div v-if="claimToday.length === 0" class="px-6 py-10 text-center text-sm text-slate-500">
                        No pickups scheduled.
                    </div>
                    <ul v-else class="divide-y divide-slate-100">
                        <li v-for="slip in claimToday" :key="slip.id" class="px-6 py-3">
                            <p class="text-sm font-semibold text-slate-900">{{ slip.user?.fullname }}</p>
                            <p class="text-xs text-slate-500">
                                <span class="font-mono">{{ slip.claim_number }}</span> ·
                                {{ slip.document_request?.document_type?.name }}
                            </p>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Overdue table -->
            <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <ExclamationTriangleIcon class="h-5 w-5 text-rose-600" />
                        <h3 class="font-display text-lg font-semibold text-slate-900">Overdue Requests</h3>
                    </div>
                    <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">
                        {{ overdueRequests.length }} flagged
                    </span>
                </div>
                <div v-if="overdueRequests.length === 0" class="px-6 py-10 text-center text-sm text-slate-500">
                    No overdue requests.
                </div>
                <table v-else class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Reference</th>
                            <th class="px-6 py-3 text-left">Student</th>
                            <th class="px-6 py-3 text-left">Document</th>
                            <th class="px-6 py-3 text-left">Expected</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in overdueRequests" :key="item.id" class="hover:bg-rose-50/40">
                            <td class="px-6 py-3 font-mono text-xs text-slate-700">{{ item.reference_no }}</td>
                            <td class="px-6 py-3 text-slate-900">{{ item.user?.fullname }}</td>
                            <td class="px-6 py-3 text-slate-700">{{ item.document_type?.name }}</td>
                            <td class="px-6 py-3 text-rose-700 font-semibold">
                                {{
                                    new Date(item.expected_release_on).toLocaleDateString('en-US', {
                                        month: 'short',
                                        day: 'numeric',
                                    })
                                }}
                            </td>
                            <td class="px-6 py-3 text-right">
                                <Link
                                    :href="route('admin.requests.show', item.id)"
                                    class="text-brand-700 font-semibold hover:underline"
                                    >Open</Link
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </StaffLayout>
</template>
