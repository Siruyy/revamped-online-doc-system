<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const { stats, announcements, faqs, notifications } = defineProps({
    stats: { type: Object, default: null },
    announcements: { type: Array, default: () => [] },
    faqs: { type: Array, default: () => [] },
    notifications: { type: Array, default: () => [] },
});

const showSkeleton = computed(() => !stats);
</script>

<template>
    <Head title="Student Dashboard" />

    <StudentLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Student Dashboard</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section v-if="showSkeleton" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="index in 3" :key="index" class="h-24 animate-pulse rounded-lg bg-slate-200" />
            </section>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Active Requests</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ stats.active_requests }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Pending Payments</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ stats.pending_payments }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Clearance Status</p>
                    <p class="mt-2 text-2xl font-bold capitalize text-slate-900">{{ stats.clearance_status }}</p>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Announcements</h3>
                    <div v-if="announcements.length === 0" class="mt-4 text-sm text-slate-500">No announcements yet.</div>
                    <div v-for="item in announcements" :key="item.id" class="mt-4 rounded-md border border-slate-200 p-3">
                        <p class="font-semibold text-slate-900">
                            {{ item.title }} <span v-if="item.pinned" class="text-xs text-amber-600">(Pinned)</span>
                        </p>
                        <p class="mt-1 text-sm text-slate-600">{{ item.body }}</p>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Top FAQs</h3>
                    <div v-if="faqs.length === 0" class="mt-4 text-sm text-slate-500">No FAQ entries yet.</div>
                    <details v-for="faq in faqs" :key="faq.id" class="mt-3 rounded-md border border-slate-200 p-3">
                        <summary class="cursor-pointer font-medium text-slate-800">{{ faq.question }}</summary>
                        <p class="mt-2 text-sm text-slate-600">{{ faq.answer }}</p>
                    </details>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Recent Activity</h3>
                <div v-if="notifications.length === 0" class="mt-4 text-sm text-slate-500">No recent notifications yet.</div>
                <ul v-else class="mt-3 space-y-3">
                    <li v-for="item in notifications" :key="item.id" class="rounded-md border border-slate-200 p-3">
                        <p class="text-sm text-slate-800">{{ item.message }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ item.created_at }}</p>
                    </li>
                </ul>
            </section>
        </div>
    </StudentLayout>
</template>
