<script setup>
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { BellIcon } from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';

const { notifications, filters, routePrefix } = defineProps({
    notifications: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    routePrefix: {
        type: String,
        default: 'student',
    },
});

const decodeLabel = (label) => label.replace('&laquo;', '').replace('&raquo;', '').trim();
const activeLayout = computed(() => (routePrefix === 'student' ? StudentLayout : StaffLayout));
const markAllReadForm = useForm({});
const markingReadIds = ref(new Set());

const filterByReadState = (value) => {
    router.get(route(`${routePrefix}.notifications.index`), { read: value }, { preserveState: true, replace: true });
};

const markAllAsRead = () => {
    markAllReadForm.post(route(`${routePrefix}.notifications.mark-all-read`), { preserveScroll: true });
};

const markAsRead = (notification) => {
    markingReadIds.value = new Set(markingReadIds.value).add(notification.id);

    router.post(
        route(`${routePrefix}.notifications.mark-read`, notification.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                const next = new Set(markingReadIds.value);
                next.delete(notification.id);
                markingReadIds.value = next;
            },
        },
    );
};
</script>

<template>
    <Head title="Notifications" />

    <component :is="activeLayout">
        <template #header>
            <PageHeader
                title="Notifications"
                subtitle="Review updates about requests, payments, clearances, and releases."
            >
                <template #actions>
                    <button
                        type="button"
                        :disabled="markAllReadForm.processing"
                        :aria-busy="markAllReadForm.processing ? 'true' : undefined"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="markAllAsRead"
                    >
                        {{ markAllReadForm.processing ? 'Marking...' : 'Mark all as read' }}
                    </button>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6">
            <div class="flex gap-2">
                <button
                    type="button"
                    class="rounded border px-3 py-1 text-sm"
                    :class="
                        !filters.read
                            ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                            : 'border-slate-300 text-slate-600'
                    "
                    @click="filterByReadState('')"
                >
                    All
                </button>
                <button
                    type="button"
                    class="rounded border px-3 py-1 text-sm"
                    :class="
                        filters.read === 'unread'
                            ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                            : 'border-slate-300 text-slate-600'
                    "
                    @click="filterByReadState('unread')"
                >
                    Unread
                </button>
                <button
                    type="button"
                    class="rounded border px-3 py-1 text-sm"
                    :class="
                        filters.read === 'read'
                            ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                            : 'border-slate-300 text-slate-600'
                    "
                    @click="filterByReadState('read')"
                >
                    Read
                </button>
            </div>

            <div class="space-y-3">
                <article
                    v-for="notification in notifications.data"
                    :key="notification.id"
                    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ notification.type }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ notification.message }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ notification.created_at }}</p>
                        </div>
                        <StatusBadge
                            :label="notification.read_at ? 'Read' : 'Unread'"
                            :tone="notification.read_at ? 'success' : 'warning'"
                        />
                    </div>
                    <button
                        v-if="!notification.read_at"
                        type="button"
                        :disabled="markingReadIds.has(notification.id)"
                        :aria-busy="markingReadIds.has(notification.id) ? 'true' : undefined"
                        class="mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="markAsRead(notification)"
                    >
                        {{ markingReadIds.has(notification.id) ? 'Marking...' : 'Mark as read' }}
                    </button>
                </article>

                <div v-if="notifications.data.length === 0">
                    <EmptyState
                        title="No notifications found"
                        description="Updates about requests, payments, clearances, and releases will appear here."
                        :icon="BellIcon"
                        variant="inline"
                        compact
                    />
                </div>
            </div>

            <div v-if="notifications.links?.length > 3" class="flex flex-wrap gap-2">
                <Link
                    v-for="link in notifications.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="rounded border px-3 py-1 text-sm"
                    :class="
                        link.active
                            ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                            : 'border-slate-300 text-slate-600'
                    "
                >
                    {{ decodeLabel(link.label) }}
                </Link>
            </div>
        </div>
    </component>
</template>
