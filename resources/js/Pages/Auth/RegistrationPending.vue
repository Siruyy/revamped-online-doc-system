<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <GuestLayout>
        <Head title="Registration Pending" />

        <div class="space-y-4 text-center">
            <div>
                <h1 class="font-display text-2xl font-bold text-slate-950">Registration submitted</h1>
                <p class="mt-1 text-sm text-slate-600">Your SVCI Docs account is waiting for approval.</p>
            </div>
            <p class="text-sm text-slate-600">
                Your account is pending SuperAdmin approval. You will be able to log in once it has been approved.
            </p>
            <Link
                :href="route('login')"
                class="inline-flex min-h-11 items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
            >
                Back to login
            </Link>
        </div>
    </GuestLayout>
</template>
