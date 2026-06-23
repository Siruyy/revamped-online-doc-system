<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardDocumentIcon, DocumentMagnifyingGlassIcon, HomeIcon } from '@heroicons/vue/24/outline';

defineProps({
    reference: { type: String, required: true },
});
</script>

<template>
    <Head title="Request Submitted" />

    <main class="min-h-screen bg-slate-950 px-4 py-12 text-white sm:px-6 lg:px-8">
        <section class="mx-auto flex min-h-[calc(100vh-6rem)] max-w-3xl flex-col justify-center">
            <div class="rounded-[2rem] bg-white p-6 text-slate-950 shadow-2xl sm:p-10">
                <div
                    class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700"
                >
                    <ClipboardDocumentIcon class="h-8 w-8" />
                </div>
                <p class="mt-6 text-sm font-semibold uppercase tracking-[0.25em] text-brand-700">Request received</p>
                <h1 class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-5xl">
                    Save this reference number.
                </h1>
                <div class="mt-8 rounded-3xl border border-dashed border-brand-300 bg-brand-50 px-5 py-6 text-center">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-700">Reference number</p>
                    <p class="mt-3 break-all font-mono text-3xl font-bold text-brand-950 sm:text-5xl">
                        {{ reference }}
                    </p>
                </div>
                <p class="mt-6 text-sm leading-6 text-slate-600">
                    Use this reference on the Track Document page. Staff will validate your requirements and payment
                    receipt before processing starts. No private file links are shown here.
                </p>
                <div class="mt-8 grid gap-3 sm:grid-cols-2">
                    <Link
                        :href="route('track-document', { reference_no: reference })"
                        class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-500"
                    >
                        <DocumentMagnifyingGlassIcon class="h-5 w-5" /> Track document
                    </Link>
                    <Link
                        href="/"
                        class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        <HomeIcon class="h-5 w-5" /> Back to home
                    </Link>
                </div>
            </div>
        </section>
    </main>
</template>
