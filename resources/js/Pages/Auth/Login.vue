<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import FormField from '@/Components/UI/FormField.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

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

            <FormField id="password" class="mt-4" label="Password" :error="form.errors.password" required>
                <template #default="{ id, describedBy, invalid }">
                    <TextInput
                        :id="id"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="current-password"
                        :described-by="describedBy"
                        :invalid="invalid"
                    />
                </template>
            </FormField>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox v-model:checked="form.remember" name="remember" />
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-slate-600 underline hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
                >
                    Forgot your password?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    :aria-busy="form.processing ? 'true' : undefined"
                >
                    {{ form.processing ? 'Logging in...' : 'Log in' }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
