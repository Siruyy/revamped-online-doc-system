<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="Email Verification" />

        <div class="mb-6">
            <h1 class="font-display text-2xl font-bold text-slate-950">Verify your email</h1>
            <p class="mt-1 text-sm text-slate-600">
                Use the verification link we emailed to activate your SVCI Docs account.
            </p>
        </div>

        <div
            v-if="verificationLinkSent"
            class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm font-medium text-emerald-700"
        >
            A new verification link has been sent to the email address you provided during registration.
        </div>

        <form :aria-busy="form.processing ? 'true' : undefined" @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    :aria-busy="form.processing ? 'true' : undefined"
                >
                    {{ form.processing ? 'Sending...' : 'Resend Verification Email' }}
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >Log Out</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
