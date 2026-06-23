<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    // Laravel/Inertia payload keeps the public contract as snake_case.
    // eslint-disable-next-line vue/prop-name-casing
    reference_no: {
        type: String,
        required: true,
    },
    notFound: {
        type: Boolean,
        default: false,
    },
    result: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Tracking Result" />

    <main class="mx-auto min-h-screen max-w-3xl px-6 py-12">
        <Link class="text-sm font-semibold text-blue-700" :href="route('track-document')">Back to tracking</Link>

        <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-slate-500">{{ reference_no }}</p>

            <div v-if="notFound" class="mt-6">
                <h1 class="text-2xl font-bold text-slate-950">No request found</h1>
                <p class="mt-2 text-sm text-slate-600">Check the reference number and try again.</p>
            </div>

            <div v-else-if="result" class="mt-6 space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-950">Request status: {{ result.status }}</h1>
                    <p class="mt-2 text-sm text-slate-600">Stage: {{ result.processing_stage }}</p>
                    <p v-if="result.expected_release_on" class="mt-1 text-sm text-slate-600">
                        Expected release: {{ result.expected_release_on }}
                    </p>
                    <p v-if="result.denial_reason" class="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">
                        {{ result.denial_reason }}
                    </p>
                </div>

                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Documents</h2>
                    <ul class="mt-3 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                        <li
                            v-for="(document, index) in result.documents"
                            :key="`${document.name}-${document.copies}-${index}`"
                            class="flex justify-between p-4 text-sm"
                        >
                            <span>{{ document.name }} x {{ document.copies }}</span>
                            <span class="font-semibold">PHP {{ document.line_total }}</span>
                        </li>
                    </ul>
                </div>

                <div v-if="result.payment" class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                    Payment: <strong>{{ result.payment.status }}</strong> · PHP {{ result.payment.total_amount }}
                </div>

                <div v-if="result.clearance" class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                    Clearance: <strong>{{ result.clearance.overall_status }}</strong>
                </div>

                <div v-if="result.claim_slip" class="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800">
                    Claim slip: <strong>{{ result.claim_slip.claim_number }}</strong>
                    <span v-if="result.claim_slip.claim_date"> · {{ result.claim_slip.claim_date }}</span>
                </div>
            </div>
        </section>
    </main>
</template>
