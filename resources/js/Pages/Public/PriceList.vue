<script setup>
import DocumentRate from '@/Components/DocumentRate.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({ documentTypes: { type: Array, required: true } });
const groups = computed(() =>
    props.documentTypes.reduce((result, type) => {
        (result[type.category] ||= []).push(type);
        return result;
    }, {}),
);
</script>

<template>
    <Head title="Document Price List" />
    <main class="min-h-screen bg-slate-50 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-5xl px-4 py-7 sm:px-6 lg:px-8">
                <Link
                    href="/"
                    class="inline-flex min-h-11 items-center gap-2 rounded-lg text-sm font-semibold text-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600"
                >
                    <ArrowLeftIcon class="h-4 w-4" aria-hidden="true" /> Back to home
                </Link>
                <p class="mt-5 text-sm font-semibold uppercase tracking-widest text-brand-700">
                    Office of the Registrar
                </p>
                <h1 class="mt-2 font-display text-3xl font-bold tracking-tight sm:text-5xl">Document price list</h1>
                <p class="mt-4 max-w-3xl text-base leading-7 text-slate-600">
                    View the current reference rates before requesting your records. The Registrar confirms the final
                    quote after reviewing your documents and page counts. Authentication, documentary stamps, and
                    delivery may add charges. Payment comes after clearance.
                </p>
                <Link
                    :href="route('public.requests.create')"
                    class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-lg bg-brand-700 px-5 py-3 font-semibold text-white hover:bg-brand-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                >
                    Request document <ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
                </Link>
            </div>
        </header>
        <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <section
                v-for="(types, category) in groups"
                :key="category"
                :aria-label="category"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
            >
                <h2 class="border-b border-slate-200 bg-slate-100 px-5 py-4 font-display text-xl font-bold">
                    {{ category === 'BasicEd' ? 'Basic Education' : category }}
                </h2>
                <dl class="divide-y divide-slate-100">
                    <div
                        v-for="type in types"
                        :key="type.id"
                        class="grid gap-2 px-5 py-4 sm:grid-cols-[1fr_auto] sm:gap-6"
                    >
                        <dt class="font-medium">
                            {{ type.name }}
                            <p
                                v-if="type.code === 'tor_transfer'"
                                class="mt-1 max-w-xl text-sm font-normal leading-6 text-slate-600"
                            >
                                Transfer Credentials include Honorable Dismissal, Good Moral, and TOR. The Registrar
                                evaluates the full bundle before confirming the total.
                            </p>
                        </dt>
                        <dd class="sm:text-right">
                            <DocumentRate :fee="type.fee" :formula="type.fee_formula || 'per_page'" />
                        </dd>
                    </div>
                </dl>
            </section>
            <p v-if="!documentTypes.length" class="rounded-2xl border border-slate-200 bg-white p-6 text-slate-600">
                No document prices are currently available. Please contact the Registrar for assistance.
            </p>
            <p class="text-sm leading-6 text-slate-600">
                These rates are maintained by authorized staff. A reference rate of ₱0.00 does not confirm that a
                request is free; the Registrar will provide the official quote. Previously confirmed quotes keep their
                agreed amounts.
            </p>
        </div>
    </main>
</template>
