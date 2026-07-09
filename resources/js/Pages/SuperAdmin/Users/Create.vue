<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import FormField from '@/Components/UI/FormField.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const form = useForm({
    fullname: '',
    email: '',
    role: 'admin',
});

const page = usePage();
const banner = computed(() => page.props.flash?.banner ?? null);
const roleOptions = [
    { value: 'admin', label: 'Admin' },
    { value: 'dean', label: 'Dean' },
    { value: 'president', label: 'Office of the President' },
    { value: 'librarian', label: 'Librarian' },
    { value: 'student_affairs', label: 'Dean of Student Affairs' },
    { value: 'alumni', label: 'SVC Alumni Officer' },
    { value: 'guidance', label: 'Guidance Counselor' },
];

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

            <form
                class="space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm"
                :aria-busy="form.processing ? 'true' : undefined"
                @submit.prevent="submit"
            >
                <FormField id="staff-fullname" label="Full name" :error="form.errors.fullname" required>
                    <template #default="{ id, describedBy, invalid }">
                        <input
                            :id="id"
                            v-model="form.fullname"
                            type="text"
                            class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm"
                            required
                            :aria-describedby="describedBy"
                            :aria-invalid="invalid ? 'true' : undefined"
                        />
                    </template>
                </FormField>
                <FormField id="staff-email" label="Email" :error="form.errors.email" required>
                    <template #default="{ id, describedBy, invalid }">
                        <input
                            :id="id"
                            v-model="form.email"
                            type="email"
                            class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm"
                            required
                            :aria-describedby="describedBy"
                            :aria-invalid="invalid ? 'true' : undefined"
                        />
                    </template>
                </FormField>
                <FormField id="staff-role" label="Role" :error="form.errors.role">
                    <template #default="{ id, describedBy, invalid }">
                        <select
                            :id="id"
                            v-model="form.role"
                            class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm"
                            :aria-describedby="describedBy"
                            :aria-invalid="invalid ? 'true' : undefined"
                        >
                            <option v-for="option in roleOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </template>
                </FormField>
                <p class="text-xs text-slate-500">
                    An email with a password reset link will be sent so they can set a password.
                </p>
                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500 disabled:opacity-50"
                        :disabled="form.processing"
                        :aria-busy="form.processing ? 'true' : undefined"
                    >
                        {{ form.processing ? 'Creating...' : 'Create' }}
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
