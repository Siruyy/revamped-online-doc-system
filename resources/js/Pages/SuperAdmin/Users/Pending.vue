<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
});

const approve = (userId) => {
    router.post(route('superadmin.users.approve', userId));
};

const reject = (userId) => {
    const reason = window.prompt('Optional rejection reason:');
    router.post(route('superadmin.users.reject', userId), { reason });
};
</script>

<template>
    <Head title="Pending Registrations" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Pending Registrations</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Email</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Student ID</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="user in props.users" :key="user.id">
                                <td class="px-4 py-3 text-slate-900">{{ user.fullname }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ user.email }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ user.student_id }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
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
                            <tr v-if="props.users.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500">No pending users.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
