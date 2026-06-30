<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, CheckCircleIcon, ClockIcon, XCircleIcon } from '@heroicons/vue/24/outline';

defineProps({
    // Laravel/Inertia payload keeps the public contract as snake_case.
    // eslint-disable-next-line vue/prop-name-casing
    reference_no: { type: String, required: true },
    notFound: { type: Boolean, default: false },
    result: { type: Object, default: null },
});

const stages = ['not_started', 'processing', 'ready_for_pickup', 'released'];

function statusLabel(value) {
    return String(value || '').replaceAll('_', ' ');
}
</script>

<template>
    <Head title="Tracking Result" />

    <main class="min-h-screen bg-slate-50 px-4 py-10 text-slate-900 sm:px-6 lg:px-8">
        <section class="mx-auto max-w-4xl">
            <Link
                :href="route('track-document')"
                class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700"
            >
                <ArrowLeftIcon class="h-4 w-4" /> Back to tracking
            </Link>

            <div class="mt-8 rounded-[2rem] bg-white p-6 shadow-xl ring-1 ring-slate-200 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-slate-500">{{ reference_no }}</p>

                <div v-if="notFound" class="mt-8 rounded-3xl bg-slate-50 p-6">
                    <XCircleIcon class="h-10 w-10 text-rose-600" />
                    <h1 class="mt-4 font-display text-3xl font-bold text-slate-950">No request found</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Check the reference number and try again. For privacy, tracking does not reveal whether a
                        similar reference exists.
                    </p>
                </div>

                <div v-else-if="result" class="mt-8 space-y-8">
                    <div class="grid gap-4 md:grid-cols-[1fr_auto] md:items-start">
                        <div>
                            <h1 class="font-display text-3xl font-bold capitalize text-slate-950">
                                {{ statusLabel(result.status) }}
                            </h1>
                            <p class="mt-2 text-sm text-slate-600">
                                Current stage:
                                <strong class="capitalize">{{ statusLabel(result.processing_stage) }}</strong>
                            </p>
                            <p v-if="result.expected_release_on" class="mt-1 text-sm text-slate-600">
                                Expected release: {{ result.expected_release_on }}
                            </p>
                        </div>
                        <div class="rounded-2xl bg-brand-50 px-5 py-4 text-sm font-semibold text-brand-800">
                            Submitted {{ result.submitted_at }}
                        </div>
                    </div>

                    <div class="rounded-2xl bg-sky-50 p-4 text-sm leading-6 text-sky-900 ring-1 ring-sky-100">
                        <p class="font-semibold">What happens next</p>
                        <p class="mt-1">{{ result.next_step }}</p>
                    </div>

                    <p
                        v-if="result.denial_reason"
                        class="rounded-2xl bg-rose-50 p-4 text-sm leading-6 text-rose-700 ring-1 ring-rose-100"
                    >
                        <strong>Denied reason:</strong> {{ result.denial_reason }}
                    </p>

                    <ol class="grid gap-3 sm:grid-cols-4">
                        <li
                            v-for="stage in stages"
                            :key="stage"
                            class="rounded-2xl border p-4 text-sm"
                            :class="
                                stages.indexOf(result.processing_stage) >= stages.indexOf(stage)
                                    ? 'border-brand-200 bg-brand-50 text-brand-800'
                                    : 'border-slate-200 bg-slate-50 text-slate-500'
                            "
                        >
                            <CheckCircleIcon
                                v-if="stages.indexOf(result.processing_stage) >= stages.indexOf(stage)"
                                class="mb-2 h-5 w-5"
                            />
                            <ClockIcon v-else class="mb-2 h-5 w-5" />
                            <span class="capitalize">{{ statusLabel(stage) }}</span>
                        </li>
                    </ol>

                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Documents</h2>
                        <ul class="mt-3 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                            <li
                                v-for="(document, index) in result.documents"
                                :key="`${document.name}-${document.copies}-${index}`"
                                class="flex justify-between gap-4 p-4 text-sm"
                            >
                                <span>{{ document.name }} × {{ document.copies }}</span>
                                <span class="font-semibold">PHP {{ document.line_total }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div v-if="result.payment" class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payment</p>
                            <p class="mt-2 capitalize">
                                <strong>{{ statusLabel(result.payment.status) }}</strong>
                            </p>
                            <p>PHP {{ result.payment.total_amount }}</p>
                        </div>
                        <div v-if="result.clearance" class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Clearance</p>
                            <p class="mt-2 capitalize">
                                <strong>{{ statusLabel(result.clearance.overall_status) }}</strong>
                            </p>
                        </div>
                        <div v-if="result.claim_slip" class="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Claim slip</p>
                            <p class="mt-2">
                                <strong>{{ result.claim_slip.claim_number }}</strong>
                            </p>
                            <p v-if="result.claim_slip.claim_date">{{ result.claim_slip.claim_date }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>
