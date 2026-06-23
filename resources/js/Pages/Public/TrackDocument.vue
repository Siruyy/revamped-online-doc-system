<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon, DocumentMagnifyingGlassIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    reference: { type: String, default: '' },
});

const form = useForm({ reference_no: props.reference });
</script>

<template>
    <Head title="Track Document" />

    <main class="min-h-screen bg-slate-50 text-slate-900">
        <section
            class="mx-auto grid min-h-screen max-w-6xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8"
        >
            <div class="flex flex-col justify-center">
                <Link href="/" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to home
                </Link>
                <p class="mt-10 text-sm font-semibold uppercase tracking-[0.25em] text-brand-700">Reference tracking</p>
                <h1 class="mt-4 font-display text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">
                    Check where your document request stands.
                </h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-slate-600">
                    Enter only the reference number shown after submission. Tracking returns a privacy-safe status view
                    and does not expose uploaded files or contact details.
                </p>
            </div>

            <div class="flex items-center">
                <form
                    class="w-full rounded-[2rem] bg-white p-6 shadow-xl ring-1 ring-slate-200 sm:p-8"
                    @submit.prevent="form.post(route('track-document.show'))"
                >
                    <div
                        class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700"
                    >
                        <DocumentMagnifyingGlassIcon class="h-8 w-8" />
                    </div>
                    <label class="mt-8 block text-sm font-semibold text-slate-700" for="reference_no">
                        Reference number
                    </label>
                    <input
                        id="reference_no"
                        v-model="form.reference_no"
                        class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-4 font-mono text-base uppercase tracking-wide shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
                        maxlength="20"
                        placeholder="REQ-2026-123456"
                        type="text"
                        required
                    />
                    <p v-if="form.errors.reference_no" class="mt-2 text-sm text-rose-600">
                        {{ form.errors.reference_no }}
                    </p>
                    <button
                        class="mt-6 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 disabled:opacity-60"
                        type="submit"
                        :disabled="form.processing"
                    >
                        Track document
                    </button>
                </form>
            </div>
        </section>
    </main>
</template>
