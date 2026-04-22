<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const filterByReadState = (value) => {
    router.get(route(`${routePrefix}.notifications.index`), { read: value }, { preserveState: true, replace: true });
};

const markAllAsRead = () => {
    router.post(route(`${routePrefix}.notifications.mark-all-read`));
};
</script>

<template>
    <Head title="Notifications" />

    <component :is="activeLayout">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-slate-900">Notifications</h2>
                <button
                    type="button"
                    class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                    @click="markAllAsRead"
                >
                    Mark all as read
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
            <div class="flex gap-2">
                <button
                    type="button"
                    class="rounded border px-3 py-1 text-sm"
                    :class="!filters.read ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-600'"
                    @click="filterByReadState('')"
                >
                    All
                </button>
                <button
                    type="button"
                    class="rounded border px-3 py-1 text-sm"
                    :class="filters.read === 'unread' ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-600'"
                    @click="filterByReadState('unread')"
                >
                    Unread
                </button>
                <button
                    type="button"
                    class="rounded border px-3 py-1 text-sm"
                    :class="filters.read === 'read' ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-600'"
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
                        <span
                            class="rounded-full px-2 py-1 text-xs font-semibold"
                            :class="notification.read_at ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                        >
                            {{ notification.read_at ? 'Read' : 'Unread' }}
                        </span>
                    </div>
                    <button
                        v-if="!notification.read_at"
                        type="button"
                        class="mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-500"
                        @click="router.post(route(`${routePrefix}.notifications.mark-read`, notification.id))"
                    >
                        Mark as read
                    </button>
                </article>

                <div v-if="notifications.data.length === 0" class="rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">
                    No notifications found.
                </div>
            </div>

            <div v-if="notifications.links?.length > 3" class="flex flex-wrap gap-2">
                <Link
                    v-for="link in notifications.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="rounded border px-3 py-1 text-sm"
                    :class="link.active ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-600'"
                >
                    {{ decodeLabel(link.label) }}
                </Link>
            </div>
        </div>
    </component>
</template>
