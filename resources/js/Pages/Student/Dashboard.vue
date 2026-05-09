<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowRightIcon,
    BellAlertIcon,
    CalendarDaysIcon,
    CheckCircleIcon,
    ClockIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    MegaphoneIcon,
    QuestionMarkCircleIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    stats: { type: Object, default: null },
    latestRequest: { type: Object, default: null },
    nextAction: { type: Object, default: null },
    announcements: { type: Array, default: () => [] },
    faqs: { type: Array, default: () => [] },
    notifications: { type: Array, default: () => [] },
});

const showSkeleton = computed(() => !props.stats);

const toneClasses = {
    primary: 'from-brand-500 to-brand-700 text-white',
    success: 'from-emerald-500 to-emerald-700 text-white',
    warning: 'from-amber-500 to-orange-600 text-white',
    info: 'from-sky-500 to-indigo-600 text-white',
};

const toneIcon = {
    primary: SparklesIcon,
    success: CheckCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
};

function statusBadge(status) {
    return (
        {
            pending: 'bg-amber-100 text-amber-800',
            approved: 'bg-sky-100 text-sky-800',
            completed: 'bg-emerald-100 text-emerald-800',
            denied: 'bg-rose-100 text-rose-800',
            cancelled: 'bg-slate-100 text-slate-600',
        }[status] ?? 'bg-slate-100 text-slate-600'
    );
}

function formatDate(value) {
    if (!value) return '—';
    try {
        return new Date(value).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } catch {
        return value;
    }
}
</script>

<template>
    <Head title="Dashboard" />

    <StudentLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-display font-bold text-slate-900 tracking-tight">
                    Welcome back, {{ $page.props.auth.user.fullname?.split(' ')[0] || 'Student' }}
                </h2>
                <p class="text-sm text-slate-500">
                    Request documents, track their status, and stay up to date with the Office of the Registrar.
                </p>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-8 px-4 pb-12 sm:px-6 lg:px-8">
            <!-- Next action hero -->
            <section v-if="nextAction" class="overflow-hidden rounded-3xl shadow-lg">
                <div :class="['bg-gradient-to-br p-8', toneClasses[nextAction.tone] ?? toneClasses.primary]">
                    <div class="flex flex-col justify-between gap-6 md:flex-row md:items-center">
                        <div class="flex items-start gap-4 max-w-2xl">
                            <component
                                :is="toneIcon[nextAction.tone] ?? SparklesIcon"
                                class="h-10 w-10 flex-none opacity-90"
                            />
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest opacity-80">
                                    Next best action
                                </p>
                                <h3 class="mt-1 text-xl font-display font-semibold sm:text-2xl">
                                    {{ nextAction.title }}
                                </h3>
                                <p class="mt-2 text-sm opacity-90">{{ nextAction.description }}</p>
                            </div>
                        </div>
                        <Link
                            v-if="nextAction.cta_href"
                            :href="nextAction.cta_href"
                            class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-5 py-3 font-medium backdrop-blur transition hover:bg-white/25 ring-1 ring-white/30"
                        >
                            {{ nextAction.cta_label }}
                            <ArrowRightIcon class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Stats -->
            <section v-if="showSkeleton" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="index in 3" :key="index" class="h-28 animate-pulse rounded-2xl bg-slate-200/70" />
            </section>

            <section v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="group rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-brand-100 p-2.5 text-brand-700">
                            <DocumentTextIcon class="h-5 w-5" />
                        </div>
                        <p class="text-sm text-slate-500">Active Requests</p>
                    </div>
                    <p class="mt-3 text-3xl font-display font-bold text-slate-900">{{ stats.active_requests }}</p>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-amber-100 p-2.5 text-amber-700">
                            <ClockIcon class="h-5 w-5" />
                        </div>
                        <p class="text-sm text-slate-500">Payments Pending</p>
                    </div>
                    <p class="mt-3 text-3xl font-display font-bold text-slate-900">{{ stats.pending_payments }}</p>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-emerald-100 p-2.5 text-emerald-700">
                            <CheckCircleIcon class="h-5 w-5" />
                        </div>
                        <p class="text-sm text-slate-500">Clearance</p>
                    </div>
                    <p class="mt-3 text-xl font-display font-bold capitalize text-slate-900">
                        {{ stats.clearance_status?.replaceAll('_', ' ') }}
                    </p>
                </div>
            </section>

            <!-- Latest request card -->
            <section v-if="latestRequest" class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div
                    class="flex flex-col items-start justify-between gap-3 border-b border-slate-100 px-6 py-4 sm:flex-row sm:items-center"
                >
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Latest Request</p>
                        <p class="mt-1 font-display font-semibold text-slate-900">
                            {{ latestRequest.document_type?.name }}
                        </p>
                        <p class="text-xs text-slate-500">Ref. {{ latestRequest.reference_no }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            :class="[
                                'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold capitalize',
                                statusBadge(latestRequest.status),
                            ]"
                        >
                            {{ latestRequest.status }}
                        </span>
                        <Link
                            :href="route('student.requests.show', latestRequest.id)"
                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                        >
                            View <ArrowRightIcon class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>
                <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="flex items-start gap-3">
                        <CalendarDaysIcon class="h-5 w-5 flex-none text-brand-600" />
                        <div>
                            <p class="text-xs uppercase tracking-wider text-slate-500">Expected release</p>
                            <p class="font-medium text-slate-900">
                                {{ formatDate(latestRequest.expected_release_on) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <ClockIcon class="h-5 w-5 flex-none text-brand-600" />
                        <div>
                            <p class="text-xs uppercase tracking-wider text-slate-500">Processing stage</p>
                            <p class="font-medium capitalize text-slate-900">
                                {{ (latestRequest.processing_stage || 'not_started').replaceAll('_', ' ') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <DocumentTextIcon class="h-5 w-5 flex-none text-brand-600" />
                        <div>
                            <p class="text-xs uppercase tracking-wider text-slate-500">Estimated total</p>
                            <p class="font-medium text-slate-900">
                                ₱{{
                                    Number(latestRequest.fee_snapshot || 0).toLocaleString('en-PH', {
                                        minimumFractionDigits: 2,
                                    })
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Announcements + FAQ -->
            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-2">
                        <MegaphoneIcon class="h-5 w-5 text-brand-600" />
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">Announcements</h3>
                    </div>
                    <div
                        v-if="announcements.length === 0"
                        class="mt-4 rounded-lg bg-slate-50 p-6 text-center text-sm text-slate-500"
                    >
                        No announcements yet.
                    </div>
                    <div v-else class="mt-4 space-y-3">
                        <article
                            v-for="item in announcements"
                            :key="item.id"
                            class="rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition hover:border-brand-200 hover:bg-white"
                        >
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-slate-900">{{ item.title }}</p>
                                <span
                                    v-if="item.pinned"
                                    class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-amber-800"
                                >
                                    Pinned
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ item.body }}</p>
                        </article>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center gap-2">
                        <QuestionMarkCircleIcon class="h-5 w-5 text-brand-600" />
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">Top FAQs</h3>
                    </div>
                    <div
                        v-if="faqs.length === 0"
                        class="mt-4 rounded-lg bg-slate-50 p-6 text-center text-sm text-slate-500"
                    >
                        No FAQ entries yet.
                    </div>
                    <details
                        v-for="faq in faqs"
                        :key="faq.id"
                        class="mt-3 rounded-xl border border-slate-100 p-4 open:border-brand-200 open:bg-brand-50/30"
                    >
                        <summary class="cursor-pointer font-medium text-slate-800 outline-none">
                            {{ faq.question }}
                        </summary>
                        <p class="mt-2 text-sm text-slate-600">{{ faq.answer }}</p>
                    </details>
                </div>
            </section>

            <!-- Recent Activity -->
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center gap-2">
                    <BellAlertIcon class="h-5 w-5 text-brand-600" />
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">Recent Activity</h3>
                </div>
                <div
                    v-if="notifications.length === 0"
                    class="mt-4 rounded-lg bg-slate-50 p-6 text-center text-sm text-slate-500"
                >
                    No recent notifications yet.
                </div>
                <ul v-else class="mt-3 divide-y divide-slate-100">
                    <li v-for="item in notifications" :key="item.id" class="py-3 first:pt-0 last:pb-0">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-sm text-slate-800">{{ item.message }}</p>
                            <span class="whitespace-nowrap text-xs text-slate-400">{{
                                formatDate(item.created_at)
                            }}</span>
                        </div>
                    </li>
                </ul>
            </section>
        </div>
    </StudentLayout>
</template>
