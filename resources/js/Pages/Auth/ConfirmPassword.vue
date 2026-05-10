<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import FormField from '@/Components/UI/FormField.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <div class="mb-6">
            <h1 class="font-display text-2xl font-bold text-slate-950">Confirm password</h1>
            <p class="mt-1 text-sm text-slate-600">This is a secure area. Confirm your password before continuing.</p>
        </div>

        <form :aria-busy="form.processing ? 'true' : undefined" @submit.prevent="submit">
            <FormField id="password" label="Password" :error="form.errors.password" required>
                <template #default="{ id, describedBy, invalid }">
                    <TextInput
                        :id="id"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="current-password"
                        autofocus
                        :described-by="describedBy"
                        :invalid="invalid"
                    />
                </template>
            </FormField>

            <div class="mt-4 flex justify-end">
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    :aria-busy="form.processing ? 'true' : undefined"
                >
                    {{ form.processing ? 'Confirming...' : 'Confirm' }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
