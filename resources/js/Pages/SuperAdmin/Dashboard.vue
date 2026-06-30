<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ChartBarIcon, ClipboardDocumentListIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

defineProps({
    userCountsByRole: { type: Object, required: true },
    userCountsByStatus: { type: Object, required: true },
    requestCounts: { type: Object, required: true },
    paymentCounts: { type: Object, required: true },
    clearanceCounts: { type: Object, required: true },
    recentActivity: { type: Array, required: true },
});

const page = usePage();
const banner = computed(() => page.props.flash?.banner ?? null);

const roleLabels = {
    student: 'Students',
    admin: 'Admins',
    teacher: 'Teachers',
    dean: 'Deans',
    accounting: 'Accounting',
    sao: 'SAO',
    superadmin: 'SuperAdmins',
};
</script>

<template>
    <Head title="SuperAdmin Dashboard" />

    <StaffLayout>
        <template #header>
            <PageHeader
                title="SuperAdmin Dashboard"
                subtitle="Monitor requests, payments, clearances, users, and recent system activity."
            />
        </template>

        <div class="space-y-6">
            <div
                v-if="banner"
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
            >
                {{ banner }}
            </div>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <StatCard label="Pending document requests" :value="requestCounts.pending" tone="warning" />
                <StatCard label="Payments awaiting approval" :value="paymentCounts.pending_approval" tone="warning" />
                <StatCard label="Completed requests" :value="requestCounts.completed" tone="success" />
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Users by role</h3>
                    <EmptyState
                        v-if="Object.keys(userCountsByRole).length === 0"
                        title="No role data yet"
                        description="User role totals will appear after accounts are created."
                        :icon="ChartBarIcon"
                        compact
                        class="mt-3"
                    />
                    <ul v-else class="mt-3 divide-y divide-slate-100 text-sm">
                        <li v-for="(count, role) in userCountsByRole" :key="role" class="flex justify-between py-2">
                            <span class="text-slate-700">{{ roleLabels[role] ?? role }}</span>
                            <span class="font-semibold text-slate-900">{{ count }}</span>
                        </li>
                    </ul>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Users by status</h3>
                    <EmptyState
                        v-if="Object.keys(userCountsByStatus).length === 0"
                        title="No status data yet"
                        description="User status totals will appear after accounts enter the approval workflow."
                        :icon="ChartBarIcon"
                        compact
                        class="mt-3"
                    />
                    <ul v-else class="mt-3 divide-y divide-slate-100 text-sm">
                        <li
                            v-for="(count, status) in userCountsByStatus"
                            :key="status"
                            class="flex justify-between py-2 capitalize"
                        >
                            <span class="text-slate-700">{{ status }}</span>
                            <span class="font-semibold text-slate-900">{{ count }}</span>
                        </li>
                    </ul>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">
                    Clearances by overall status
                </h3>
                <div class="mt-3 flex flex-wrap gap-3 text-sm">
                    <StatusBadge
                        v-for="(count, overallKey) in clearanceCounts"
                        :key="overallKey"
                        :label="`${overallKey.replace(/_/g, ' ')}: ${count}`"
                        tone="neutral"
                        size="md"
                    />
                    <EmptyState
                        v-if="Object.keys(clearanceCounts).length === 0"
                        title="No clearances yet"
                        description="Clearance totals will appear after requests enter department signing."
                        :icon="ShieldCheckIcon"
                        compact
                        class="w-full"
                    />
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Recent activity</h3>
                    <Link
                        :href="route('superadmin.logs.index')"
                        class="text-xs font-semibold text-violet-700 hover:text-violet-600"
                    >
                        View all logs
                    </Link>
                </div>
                <EmptyState
                    v-if="recentActivity.length === 0"
                    title="No activity logged yet"
                    description="Auditable system events will appear here after users take action."
                    :icon="ClipboardDocumentListIcon"
                    compact
                    class="mt-3"
                />
                <ul v-else class="mt-3 divide-y divide-slate-100">
                    <li v-for="log in recentActivity" :key="log.id" class="py-3 text-sm">
                        <p class="font-medium text-slate-900">{{ log.action }}</p>
                        <p class="text-slate-600">{{ log.description }}</p>
                        <p class="mt-1 text-xs text-slate-400">
                            {{ log.created_at }} · Actor: {{ log.user?.email ?? '—' }}
                        </p>
                    </li>
                </ul>
            </section>
        </div>
    </StaffLayout>
</template>
