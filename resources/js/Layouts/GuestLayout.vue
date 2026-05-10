<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-dvh flex-col bg-gradient-to-br from-slate-50 via-white to-brand-50/60 px-4 py-8 text-slate-900 sm:px-6 lg:px-8"
    >
        <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
            <div class="mb-8 text-center">
                <Link href="/" class="inline-flex items-center justify-center gap-3">
                    <span
                        class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-sm"
                    >
                        <ApplicationLogo class="h-7 w-7 fill-current" />
                    </span>
                    <span class="font-display text-xl font-bold tracking-tight text-slate-950">SVCI Docs</span>
                </Link>
                <p class="mt-3 text-sm text-slate-600">Official SVCI document request portal</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
