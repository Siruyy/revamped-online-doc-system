<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import FormField from '@/Components/UI/FormField.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <div class="mb-6">
            <h1 class="font-display text-2xl font-bold text-slate-950">Reset your password</h1>
            <p class="mt-1 text-sm text-slate-600">
                Enter your email and we will send you a secure password reset link.
            </p>
        </div>

        <div
            v-if="status"
            class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm font-medium text-emerald-700"
        >
            {{ status }}
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

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    :aria-busy="form.processing ? 'true' : undefined"
                >
                    {{ form.processing ? 'Sending...' : 'Email Password Reset Link' }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
