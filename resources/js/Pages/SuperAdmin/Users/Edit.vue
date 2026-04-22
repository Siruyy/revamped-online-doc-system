<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    user: { type: Object, required: true },
});

const page = usePage();
const banner = computed(() => page.props.flash?.banner ?? null);
const isSelf = computed(() => page.props.auth?.user?.id === props.user.id);

const form = useForm({
    fullname: props.user.fullname,
    email: props.user.email,
    role: props.user.role,
    status: props.user.status,
    course: props.user.course ?? '',
    year_level: props.user.year_level ?? '',
    student_id: props.user.student_id ?? '',
    contact_number: props.user.contact_number ?? '',
});

const submit = () => {
    form.patch(route('superadmin.users.update', props.user.id));
};

const suspend = () => {
    if (!confirm('Suspend this user?')) return;
    router.post(route('superadmin.users.suspend', props.user.id));
};

const reactivate = () => {
    router.post(route('superadmin.users.reactivate', props.user.id));
};

const destroyUser = () => {
    if (!confirm('Soft-delete this user? They can be restored from the database if needed.')) return;
    router.delete(route('superadmin.users.destroy', props.user.id));
};
</script>

<template>
    <Head :title="`Edit ${user.fullname}`" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Edit user</h2>
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
                    />
                    <p v-if="form.errors.fullname" class="mt-1 text-sm text-rose-600">{{ form.errors.fullname }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Role</label>
                    <select v-model="form.role" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="student">Student</option>
                        <option value="admin">Admin</option>
                        <option value="teacher">Teacher</option>
                        <option value="dean">Dean</option>
                        <option value="accounting">Accounting</option>
                        <option value="sao">SAO</option>
                        <option value="superadmin">SuperAdmin</option>
                    </select>
                    <p v-if="form.errors.role" class="mt-1 text-sm text-rose-600">{{ form.errors.role }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Status</label>
                    <select v-model="form.status" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <p v-if="form.errors.status" class="mt-1 text-sm text-rose-600">{{ form.errors.status }}</p>
                </div>
                <div v-if="form.role === 'student'" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Course</label>
                        <input
                            v-model="form.course"
                            type="text"
                            class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Year level</label>
                        <input
                            v-model="form.year_level"
                            type="number"
                            min="1"
                            max="4"
                            class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Student ID</label>
                        <input
                            v-model="form.student_id"
                            type="text"
                            class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm"
                        />
                        <p v-if="form.errors.student_id" class="mt-1 text-sm text-rose-600">
                            {{ form.errors.student_id }}
                        </p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Contact number</label>
                    <input
                        v-model="form.contact_number"
                        type="text"
                        class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm"
                    />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="submit"
                        class="rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        Save changes
                    </button>
                    <a
                        :href="route('superadmin.users.index')"
                        class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                        >Back to list</a
                    >
                </div>
            </form>

            <div class="mt-6 space-y-3 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-800">Account actions</h3>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="user.status !== 'suspended'"
                        type="button"
                        class="rounded-md border border-amber-300 px-3 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-50"
                        @click="suspend"
                    >
                        Suspend
                    </button>
                    <button
                        v-if="user.status === 'suspended'"
                        type="button"
                        class="rounded-md border border-emerald-300 px-3 py-2 text-sm font-semibold text-emerald-900 hover:bg-emerald-50"
                        @click="reactivate"
                    >
                        Reactivate
                    </button>
                    <button
                        v-if="!isSelf"
                        type="button"
                        class="rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-800 hover:bg-rose-50"
                        @click="destroyUser"
                    >
                        Soft delete
                    </button>
                </div>
                <p v-if="isSelf" class="text-xs text-slate-500">You cannot delete your own account from this screen.</p>
            </div>
        </div>
    </StaffLayout>
</template>
