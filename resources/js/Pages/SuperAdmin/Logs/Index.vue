<script setup>
import EmptyState from '@/Components/UI/EmptyState.vue';
import DataTableShell from '@/Components/UI/DataTableShell.vue';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ClipboardDocumentListIcon } from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const expanded = ref({});

const form = useForm({
    action: props.filters.action || '',
    user_id: props.filters.user_id || '',
    affected_user_id: props.filters.affected_user_id || '',
    from: props.filters.from || '',
    to: props.filters.to || '',
    q: props.filters.q || '',
});

const apply = () => {
    form.get(route('superadmin.logs.index'), { preserveState: true, replace: true });
};

const exportFilters = computed(() => ({
    action: form.action,
    user_id: form.user_id,
    affected_user_id: form.affected_user_id,
    from: form.from,
    to: form.to,
    q: form.q,
}));

const toggle = (id) => {
    expanded.value = { ...expanded.value, [id]: !expanded.value[id] };
};
</script>

<template>
    <Head title="Activity logs" />

    <StaffLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-slate-900">Activity logs</h2>
                <a
                    :href="route('superadmin.logs.export', exportFilters)"
                    class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Export CSV
                </a>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form
                class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-6"
                @submit.prevent="apply"
            >
                <input
                    v-model="form.action"
                    type="text"
                    placeholder="Action"
                    class="rounded-md border-slate-300 text-sm shadow-sm"
                />
                <input
                    v-model="form.user_id"
                    type="number"
                    placeholder="Actor user ID"
                    class="rounded-md border-slate-300 text-sm shadow-sm"
                />
                <input
                    v-model="form.affected_user_id"
                    type="number"
                    placeholder="Affected user ID"
                    class="rounded-md border-slate-300 text-sm shadow-sm"
                />
                <input v-model="form.from" type="date" class="rounded-md border-slate-300 text-sm shadow-sm" />
                <input v-model="form.to" type="date" class="rounded-md border-slate-300 text-sm shadow-sm" />
                <input
                    v-model="form.q"
                    type="search"
                    placeholder="Search description"
                    class="rounded-md border-slate-300 text-sm shadow-sm lg:col-span-2"
                />
                <div class="flex items-end gap-2 lg:col-span-6">
                    <button
                        type="submit"
                        class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Apply
                    </button>
                    <Link
                        :href="route('superadmin.logs.index')"
                        class="text-sm font-semibold text-slate-600 hover:text-slate-900"
                        >Reset</Link
                    >
                </div>
            </form>

            <p class="text-xs text-slate-500 md:hidden">Swipe horizontally to view all columns.</p>
            <DataTableShell label="Activity logs table" min-width="min-w-[64rem]">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-3 text-left font-semibold text-slate-700">When</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-700">Action</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-700">Actor</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-700">Affected</th>
                            <th class="px-3 py-3 text-left font-semibold text-slate-700"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <template v-for="log in logs.data" :key="log.id">
                            <tr>
                                <td class="px-3 py-3 whitespace-nowrap text-slate-600">{{ log.created_at }}</td>
                                <td class="px-3 py-3 font-medium text-slate-900">{{ log.action }}</td>
                                <td class="px-3 py-3 text-slate-600">{{ log.user?.email ?? '—' }}</td>
                                <td class="px-3 py-3 text-slate-600">{{ log.affected_user?.email ?? '—' }}</td>
                                <td class="px-3 py-3 text-right">
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-violet-700 hover:text-violet-600"
                                        @click="toggle(log.id)"
                                    >
                                        {{ expanded[log.id] ? 'Hide' : 'Details' }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="expanded[log.id]">
                                <td colspan="5" class="bg-slate-50 px-3 py-3 text-xs text-slate-700">
                                    <p class="font-semibold text-slate-800">Description</p>
                                    <p class="mt-1">{{ log.description }}</p>
                                    <p
                                        v-if="log.metadata && Object.keys(log.metadata).length"
                                        class="mt-2 font-mono text-[11px] text-slate-600"
                                    >
                                        {{ JSON.stringify(log.metadata) }}
                                    </p>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="logs.data.length === 0">
                            <td colspan="5" class="p-8">
                                <EmptyState
                                    variant="table"
                                    title="No log entries"
                                    description="System activity will appear here after users take auditable actions."
                                    :icon="ClipboardDocumentListIcon"
                                    compact
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </DataTableShell>

            <div v-if="logs.links?.length > 3" class="flex flex-wrap gap-2">
                <Link
                    v-for="link in logs.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="rounded border px-3 py-1 text-sm"
                    :class="
                        link.active
                            ? 'border-violet-600 bg-violet-50 text-violet-800'
                            : 'border-slate-300 text-slate-600'
                    "
                    preserve-scroll
                >
                    {{ link.label.replace('&laquo;', '«').replace('&raquo;', '»') }}
                </Link>
            </div>
        </div>
    </StaffLayout>
</template>
