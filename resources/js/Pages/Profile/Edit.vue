<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const user = page.props.auth.user;

const isDepartmentOfficer = computed(() => ['teacher', 'dean', 'accounting', 'sao'].includes(user?.role));

const profileUpdateRoute = computed(() => (isDepartmentOfficer.value ? 'department.profile.update' : 'profile.update'));

const Layout = computed(() => (isDepartmentOfficer.value ? StaffLayout : AuthenticatedLayout));

const signatureForm = useForm({
    signature: null,
});

const onSignatureChange = (event) => {
    signatureForm.signature = event.target.files?.[0] ?? null;
};

const submitSignature = () => {
    signatureForm.post(route('department.profile.signature'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => signatureForm.reset('signature'),
    });
};
</script>

<template>
    <Head title="Profile" />

    <component :is="Layout">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Profile</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        :profile-update-route="profileUpdateRoute"
                        class="max-w-xl"
                    />
                </div>

                <div v-if="isDepartmentOfficer" class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">Department signature</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Upload a PNG or JPG signature (transparent PNG recommended).
                        </p>
                    </header>
                    <form class="mt-6 space-y-4" @submit.prevent="submitSignature">
                        <div>
                            <InputLabel for="signature" value="Signature image" />
                            <input
                                id="signature"
                                type="file"
                                accept="image/png,image/jpeg,image/jpg"
                                class="mt-1 block w-full text-sm text-gray-700"
                                @change="onSignatureChange"
                            />
                            <InputError class="mt-2" :message="signatureForm.errors.signature" />
                        </div>
                        <PrimaryButton :disabled="signatureForm.processing">Save signature</PrimaryButton>
                    </form>
                </div>

                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </component>
</template>
