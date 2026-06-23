<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    reference: {
        type: String,
        default: '',
    },
});

const form = useForm({
    reference_no: props.reference,
});
</script>

<template>
    <Head title="Track Document" />

    <main class="mx-auto flex min-h-screen max-w-2xl flex-col justify-center px-6 py-12">
        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-blue-700">SVCI Documents</p>
        <h1 class="mt-4 text-3xl font-bold text-slate-950">Track your request</h1>
        <p class="mt-3 text-sm text-slate-600">Enter the reference number from your submitted request.</p>

        <form class="mt-8 space-y-4" @submit.prevent="form.post(route('track-document.show'))">
            <div>
                <label class="text-sm font-medium text-slate-700" for="reference_no">Reference number</label>
                <input
                    id="reference_no"
                    v-model="form.reference_no"
                    class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm uppercase tracking-wide shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    maxlength="20"
                    placeholder="REQ-2026-123456"
                    type="text"
                />
                <p v-if="form.errors.reference_no" class="mt-2 text-sm text-red-600">{{ form.errors.reference_no }}</p>
            </div>

            <button
                class="rounded-xl bg-blue-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:opacity-60"
                type="submit"
                :disabled="form.processing"
            >
                Track request
            </button>
        </form>
    </main>
</template>
