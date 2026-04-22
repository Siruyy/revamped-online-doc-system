<script setup>
import { useEchoPrivateChannel } from '@/Composables/useEchoPrivateChannel';
import { useRealtimeOrPoll } from '@/Composables/useRealtimeOrPoll';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    users: { type: Array, required: true },
});

const page = usePage();
const banner = computed(() => page.props.flash?.banner ?? null);
const isSuperAdmin = computed(() => page.props.auth?.user?.role === 'superadmin');

const reloadPending = () => {
    router.reload({ only: ['users'], preserveScroll: true });
};

useEchoPrivateChannel(
    () => (isSuperAdmin.value ? 'role.superadmin' : null),
    {
        RegistrationSubmitted: reloadPending,
    },
);

useRealtimeOrPoll(reloadPending, { intervalMs: 120000 });

const selected = ref([]);

const toggleAll = (e) => {
    if (e.target.checked) {
        selected.value = props.users.map((u) => u.id);
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

const approve = (userId) => {
    router.post(route('superadmin.users.approve', userId));
};

const reject = (userId) => {
    const reason = window.prompt('Optional rejection reason:');
    if (reason === null) return;
    router.post(route('superadmin.users.reject', userId), { reason });
};

const bulkApprove = () => {
    if (selected.value.length === 0) return;
    if (!confirm(`Approve ${selected.value.length} user(s)?`)) return;
    router.post(
        route('superadmin.users.bulk-approve'),
        { user_ids: selected.value },
        {
            onSuccess: () => {
                selected.value = [];
            },
        },
    );
};
</script>

<template>
    <Head title="Pending Registrations" />

    <StaffLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-slate-900">Pending Registrations</h2>
                <button
                    type="button"
                    class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-40"
                    :disabled="selected.length === 0"
                    @click="bulkApprove"
                >
                    Bulk approve ({{ selected.length }})
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                v-if="banner"
                class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
            >
                {{ banner }}
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" class="rounded border-slate-300" @change="toggleAll" />
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Email</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Student ID</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="user in users" :key="user.id">
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-slate-300"
                                    :checked="selected.includes(user.id)"
                                    @change="(e) => onRowCheck(user.id, e.target.checked)"
                                />
                            </td>
                            <td class="px-4 py-3 text-slate-900">{{ user.fullname }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ user.email }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ user.student_id }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500"
                                        @click="approve(user.id)"
                                    >
                                        Approve
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-md bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500"
                                        @click="reject(user.id)"
                                    >
                                        Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No pending users.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </StaffLayout>
</template>
