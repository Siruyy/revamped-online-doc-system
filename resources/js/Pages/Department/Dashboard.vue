<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Department Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Department Dashboard</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    Welcome to the department officer dashboard.
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
