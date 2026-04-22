<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    userCountsByRole: { type: Object, required: true },
    userCountsByStatus: { type: Object, required: true },
    pendingRegistrations: { type: Number, required: true },
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
            <h2 class="text-xl font-semibold leading-tight text-slate-900">SuperAdmin Dashboard</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div
                v-if="banner"
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
            >
                {{ banner }}
            </div>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    :href="route('superadmin.users.pending')"
                    class="rounded-lg border border-violet-200 bg-white p-4 shadow-sm transition hover:border-violet-400"
                >
                    <p class="text-xs uppercase text-slate-500">Pending registrations</p>
                    <p class="mt-2 text-2xl font-bold text-violet-800">{{ pendingRegistrations }}</p>
                    <p class="mt-1 text-xs text-violet-600">Review queue →</p>
                </Link>
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Pending document requests</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ requestCounts.pending }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Payments awaiting approval</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ paymentCounts.pending_approval }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Completed requests</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ requestCounts.completed }}</p>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Users by role</h3>
                    <ul class="mt-3 divide-y divide-slate-100 text-sm">
                        <li v-for="(count, role) in userCountsByRole" :key="role" class="flex justify-between py-2">
                            <span class="text-slate-700">{{ roleLabels[role] ?? role }}</span>
                            <span class="font-semibold text-slate-900">{{ count }}</span>
                        </li>
                        <li v-if="Object.keys(userCountsByRole).length === 0" class="py-2 text-slate-500">No data.</li>
                    </ul>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Users by status</h3>
                    <ul class="mt-3 divide-y divide-slate-100 text-sm">
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
                    <span
                        v-for="(count, overallKey) in clearanceCounts"
                        :key="overallKey"
                        class="rounded-full bg-slate-100 px-3 py-1 capitalize text-slate-800"
                    >
                        {{ overallKey.replace(/_/g, ' ') }}: <strong>{{ count }}</strong>
                    </span>
                    <span v-if="Object.keys(clearanceCounts).length === 0" class="text-slate-500"
                        >No clearances yet.</span
                    >
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
                <div v-if="recentActivity.length === 0" class="mt-3 text-sm text-slate-500">
                    No activity logged yet.
                </div>
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
