<script setup>
import EmptyState from '@/Components/UI/EmptyState.vue';
import DataTableShell from '@/Components/UI/DataTableShell.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import ResponsiveRecordList from '@/Components/UI/ResponsiveRecordList.vue';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { UsersIcon } from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const page = usePage();
const banner = computed(() => page.props.flash?.banner ?? null);

const filterForm = useForm({
    role: props.filters.role || '',
    status: props.filters.status || '',
    course: props.filters.course || '',
    year: props.filters.year || '',
    search: props.filters.search || '',
});

const selected = ref([]);
const showBulkDelete = ref(false);
const confirmText = ref('');

const applyFilters = () => {
    filterForm.get(route('superadmin.users.index'), {
        preserveState: true,
        replace: true,
    });
};

const exportFilters = computed(() => ({
    role: filterForm.role,
    status: filterForm.status,
    course: filterForm.course,
    year: filterForm.year,
    search: filterForm.search,
}));

const toggleAll = (e) => {
    if (e.target.checked) {
        selected.value = props.users.data.map((u) => u.id);
    } else {
        selected.value = [];
    }
};

const onRowCheck = (id, checked) => {
    if (checked) {
        if (!selected.value.includes(id)) {
            selected.value = [...selected.value, id];
        }
    } else {
        selected.value = selected.value.filter((x) => x !== id);
    }
};

const bulkDelete = () => {
    router.post(
        route('superadmin.users.bulk-destroy'),
        {
            user_ids: selected.value,
            confirmation: confirmText.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showBulkDelete.value = false;
                confirmText.value = '';
                selected.value = [];
            },
        },
    );
};
</script>

<template>
    <Head title="Users" />

    <StaffLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-slate-900">Users</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        :href="route('superadmin.users.export', exportFilters)"
                        class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Export CSV
                    </a>
                    <Link
                        :href="route('superadmin.users.create')"
                        class="rounded-md bg-violet-600 px-3 py-2 text-sm font-semibold text-white hover:bg-violet-500"
                    >
                        Create staff
                    </Link>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div
                v-if="banner"
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
            >
                {{ banner }}
            </div>

            <form
                class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6"
                @submit.prevent="applyFilters"
            >
                <input
                    v-model="filterForm.search"
                    type="search"
                    placeholder="Search name, email, ID"
                    class="rounded-md border-slate-300 text-sm shadow-sm lg:col-span-2"
                />
                <select v-model="filterForm.role" class="rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="">All roles</option>
                    <option value="student">Student</option>
                    <option value="admin">Admin</option>
                    <option value="teacher">Teacher</option>
                    <option value="dean">Dean</option>
                    <option value="accounting">Accounting</option>
                    <option value="sao">SAO</option>
                    <option value="superadmin">SuperAdmin</option>
                </select>
                <select v-model="filterForm.status" class="rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="rejected">Rejected</option>
                </select>
                <input
                    v-model="filterForm.course"
                    type="text"
                    placeholder="Course"
                    class="rounded-md border-slate-300 text-sm shadow-sm"
                />
                <input
                    v-model="filterForm.year"
                    type="number"
                    min="1"
                    max="4"
                    placeholder="Year"
                    class="rounded-md border-slate-300 text-sm shadow-sm"
                />
                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-6">
                    <button
                        type="submit"
                        class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Apply
                    </button>
                    <Link
                        :href="route('superadmin.users.index')"
                        class="text-sm font-semibold text-slate-600 hover:text-slate-900"
                        >Reset</Link
                    >
                </div>
            </form>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-800 hover:bg-rose-50 disabled:opacity-40"
                    :disabled="selected.length === 0"
                    @click="showBulkDelete = true"
                >
                    Bulk delete ({{ selected.length }})
                </button>
            </div>

            <ResponsiveRecordList :empty="users.data.length === 0">
                <template #empty>
                    <EmptyState
                        title="No users match"
                        description="Adjust filters or create a staff account if this role should exist."
                        :icon="UsersIcon"
                        compact
                    >
                        <template #actions>
                            <Link
                                :href="route('superadmin.users.create')"
                                class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500"
                            >
                                Create staff
                            </Link>
                        </template>
                    </EmptyState>
                </template>

                <template #cards>
                    <article
                        v-for="row in users.data"
                        :key="row.id"
                        class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <label class="flex min-w-0 items-start gap-3">
                                <input
                                    type="checkbox"
                                    class="mt-0.5 rounded border-slate-300"
                                    :checked="selected.includes(row.id)"
                                    @change="(e) => onRowCheck(row.id, e.target.checked)"
                                />
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-slate-950">{{
                                        row.fullname
                                    }}</span>
                                    <span class="mt-0.5 block truncate text-xs text-slate-500">{{ row.email }}</span>
                                </span>
                            </label>
                            <span
                                class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold capitalize text-slate-700"
                            >
                                {{ row.status }}
                            </span>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                            <div>
                                <dt class="font-medium text-slate-500">Role</dt>
                                <dd class="mt-0.5 capitalize text-slate-800">{{ row.role }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Student ID</dt>
                                <dd class="mt-0.5 text-slate-800">{{ row.student_id || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Course</dt>
                                <dd class="mt-0.5 text-slate-800">{{ row.course || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Year</dt>
                                <dd class="mt-0.5 text-slate-800">{{ row.year_level || '—' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4">
                            <Link
                                :href="route('superadmin.users.edit', row.id)"
                                class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-violet-600 px-3 text-sm font-semibold text-white hover:bg-violet-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600"
                            >
                                Edit user
                            </Link>
                        </div>
                    </article>
                </template>

                <template #table>
                    <DataTableShell label="Users table" min-width="min-w-[56rem]">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="w-10 px-3 py-3">
                                        <input type="checkbox" class="rounded border-slate-300" @change="toggleAll" />
                                    </th>
                                    <th class="px-3 py-3 text-left font-semibold text-slate-700">Name</th>
                                    <th class="px-3 py-3 text-left font-semibold text-slate-700">Email</th>
                                    <th class="px-3 py-3 text-left font-semibold text-slate-700">Role</th>
                                    <th class="px-3 py-3 text-left font-semibold text-slate-700">Status</th>
                                    <th class="px-3 py-3 text-left font-semibold text-slate-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <tr v-for="row in users.data" :key="row.id">
                                    <td class="px-3 py-3">
                                        <input
                                            type="checkbox"
                                            class="rounded border-slate-300"
                                            :checked="selected.includes(row.id)"
                                            @change="(e) => onRowCheck(row.id, e.target.checked)"
                                        />
                                    </td>
                                    <td class="px-3 py-3 text-slate-900">{{ row.fullname }}</td>
                                    <td class="px-3 py-3 text-slate-600">{{ row.email }}</td>
                                    <td class="px-3 py-3 capitalize text-slate-700">{{ row.role }}</td>
                                    <td class="px-3 py-3 capitalize text-slate-700">{{ row.status }}</td>
                                    <td class="px-3 py-3">
                                        <Link
                                            :href="route('superadmin.users.edit', row.id)"
                                            class="font-semibold text-violet-700 hover:text-violet-600"
                                        >
                                            Edit
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </DataTableShell>
                </template>
            </ResponsiveRecordList>

            <Pagination :meta="users" label="Users pagination" />
        </div>

        <div
            v-if="showBulkDelete"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-slate-900">Confirm bulk delete</h3>
                <p class="mt-2 text-sm text-slate-600">
                    This will soft-delete {{ selected.length }} account(s). Type <strong>DELETE</strong> to confirm.
                </p>
                <input
                    v-model="confirmText"
                    type="text"
                    class="mt-4 w-full rounded-md border-slate-300 text-sm shadow-sm"
                    autocomplete="off"
                />
                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700"
                        @click="showBulkDelete = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="rounded-md bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-500 disabled:opacity-40"
                        :disabled="confirmText !== 'DELETE'"
                        @click="bulkDelete"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>
