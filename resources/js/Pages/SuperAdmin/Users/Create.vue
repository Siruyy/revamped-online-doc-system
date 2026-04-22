<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const form = useForm({
    fullname: '',
    email: '',
    role: 'admin',
});

const page = usePage();
const banner = computed(() => page.props.flash?.banner ?? null);

const submit = () => {
    form.post(route('superadmin.users.store'));
};
</script>

<template>
    <Head title="Create staff" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Create staff account</h2>
        </template>

        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div
                v-if="banner"
                class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
            >
                {{ banner }}
            </div>

            <form class="space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Full name</label>
                    <input
                        v-model="form.fullname"
                        type="text"
                        class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm"
                        required
                    />
                    <p v-if="form.errors.fullname" class="mt-1 text-sm text-rose-600">{{ form.errors.fullname }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm"
                        required
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Role</label>
                    <select v-model="form.role" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="admin">Admin</option>
                        <option value="teacher">Teacher</option>
                        <option value="dean">Dean</option>
                        <option value="accounting">Accounting</option>
                        <option value="sao">SAO</option>
                    </select>
                    <p v-if="form.errors.role" class="mt-1 text-sm text-rose-600">{{ form.errors.role }}</p>
                </div>
                <p class="text-xs text-slate-500">
                    An email with a password reset link will be sent so they can set a password.
                </p>
                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        Create
                    </button>
                    <a
                        :href="route('superadmin.users.index')"
                        class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                        >Cancel</a
                    >
                </div>
            </form>
        </div>
    </StaffLayout>
</template>
