<script setup>
import { useEchoPrivateChannel } from '@/Composables/useEchoPrivateChannel';
import { useRealtimeOrPoll } from '@/Composables/useRealtimeOrPoll';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRightIcon,
    BuildingOffice2Icon,
    CheckCircleIcon,
    ClipboardDocumentCheckIcon,
    InboxStackIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    stats: { type: Object, required: true },
    pendingLatest: { type: Array, required: true },
    department: { type: String, required: true },
});

const reloadDashboard = () => {
    router.reload({ only: ['stats', 'pendingLatest', 'department'], preserveScroll: true });
};

useEchoPrivateChannel(() => `role.department.${props.department}`, {
    ClearanceCreated: reloadDashboard,
    ClearanceUpdated: reloadDashboard,
});

useRealtimeOrPoll(reloadDashboard, { intervalMs: 90000 });

function relativeAge(value) {
    if (!value) return '—';
    const diff = Math.floor((Date.now() - new Date(value).getTime()) / 86400000);
    if (diff <= 0) return 'today';
    if (diff === 1) return '1 day';
    return `${diff} days`;
}
</script>

<template>
    <Head title="Department Dashboard" />

    <StaffLayout>
        <template #header>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Department Workspace</p>
                <h2 class="mt-1 text-2xl font-display font-bold text-slate-900 capitalize">{{ department }} Office</h2>
                <p class="text-sm text-slate-500">Sign or deny clearances assigned to your office.</p>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-8 px-4 pb-12 sm:px-6 lg:px-8">
            <!-- Stats -->
            <section class="grid gap-4 sm:grid-cols-3">
                <Link
                    :href="route('department.clearances.index', { status: 'pending' })"
                    class="group rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 p-6 text-white shadow-md transition hover:-translate-y-0.5"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-widest opacity-90">Pending on your desk</p>
                            <p class="mt-2 text-4xl font-display font-bold">{{ stats.pending }}</p>
                        </div>
                        <InboxStackIcon class="h-10 w-10 opacity-80" />
                    </div>
                    <p
                        class="mt-3 inline-flex items-center gap-1 text-xs font-semibold opacity-90 group-hover:underline"
                    >
                        Open queue <ArrowRightIcon class="h-3.5 w-3.5" />
                    </p>
                </Link>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-emerald-100 p-2.5 text-emerald-700">
                            <CheckCircleIcon class="h-5 w-5" />
                        </div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Signed today</p>
                    </div>
                    <p class="mt-3 text-3xl font-display font-bold text-slate-900">{{ stats.signed_today }}</p>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-rose-100 p-2.5 text-rose-700"><XCircleIcon class="h-5 w-5" /></div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Denied (total)</p>
                    </div>
                    <p class="mt-3 text-3xl font-display font-bold text-slate-900">{{ stats.denied }}</p>
                </div>
            </section>

            <!-- Latest queue -->
            <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <ClipboardDocumentCheckIcon class="h-5 w-5 text-brand-600" />
                        <h3 class="font-display text-lg font-semibold text-slate-900">Latest pending clearances</h3>
                    </div>
                    <Link
                        :href="route('department.clearances.index')"
                        class="text-xs font-semibold text-brand-700 hover:underline"
                    >
                        View all →
                    </Link>
                </div>
                <ul v-if="pendingLatest.length" class="divide-y divide-slate-100">
                    <li
                        v-for="row in pendingLatest"
                        :key="row.id"
                        class="flex items-center justify-between px-6 py-3 transition hover:bg-brand-50/30"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-900">{{ row.user?.fullname }}</p>
                            <p class="text-xs text-slate-500">
                                <span class="font-mono">{{ row.document_request?.reference_no }}</span> ·
                                {{ row.user?.course || '—' }} Y{{ row.user?.year_level || '?' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-600">{{
                                relativeAge(row.created_at)
                            }}</span>
                            <Link
                                :href="route('department.clearances.show', row.id)"
                                class="inline-flex items-center gap-1 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-500"
                            >
                                Open <ArrowRightIcon class="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    </li>
                </ul>
                <div v-else class="px-6 py-12 text-center">
                    <BuildingOffice2Icon class="mx-auto h-10 w-10 text-slate-300" />
                    <p class="mt-2 font-display font-semibold text-slate-700">All caught up</p>
                    <p class="text-xs text-slate-500">
                        Your inbox is clear. New clearances will appear here in real time.
                    </p>
                </div>
            </section>
        </div>
    </StaffLayout>
</template>
