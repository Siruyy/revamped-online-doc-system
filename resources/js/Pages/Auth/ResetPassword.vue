<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import FormField from '@/Components/UI/FormField.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

        <div class="mb-6">
            <h1 class="font-display text-2xl font-bold text-slate-950">Set a new password</h1>
            <p class="mt-1 text-sm text-slate-600">Choose a new password for your SVCI Docs account.</p>
        </div>

        <form :aria-busy="form.processing ? 'true' : undefined" @submit.prevent="submit">
            <FormField id="email" label="Email" :error="form.errors.email" required>
                <template #default="{ id, describedBy, invalid }">
                    <TextInput
                        :id="id"
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full"
                        required
                        autofocus
                        autocomplete="username"
                        :described-by="describedBy"
                        :invalid="invalid"
                    />
                </template>
            </FormField>

            <FormField id="password" class="mt-4" label="Password" :error="form.errors.password" required>
                <template #default="{ id, describedBy, invalid }">
                    <TextInput
                        :id="id"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="new-password"
                        :described-by="describedBy"
                        :invalid="invalid"
                    />
                </template>
            </FormField>

            <FormField
                id="password_confirmation"
                class="mt-4"
                label="Confirm Password"
                :error="form.errors.password_confirmation"
                required
            >
                <template #default="{ id, describedBy, invalid }">
                    <TextInput
                        :id="id"
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="new-password"
                        :described-by="describedBy"
                        :invalid="invalid"
                    />
                </template>
            </FormField>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    :aria-busy="form.processing ? 'true' : undefined"
                >
                    {{ form.processing ? 'Resetting...' : 'Reset Password' }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
