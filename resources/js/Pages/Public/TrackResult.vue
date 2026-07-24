<script setup>
import FileUploadField from '@/Components/Public/FileUploadField.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import { ArrowLeftIcon, CheckCircleIcon, ClockIcon, XCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    // Laravel/Inertia payload keeps the public contract as snake_case.
    // eslint-disable-next-line vue/prop-name-casing
    reference_no: { type: String, required: true },
    notFound: { type: Boolean, default: false },
    result: { type: Object, default: null },
});
const accessCode = ref('');
const requirementFiles = ref({});
const paymentForm = useForm({ access_code: '', payment_method: '', reference_number: '', receipt: null });
const requirementForm = useForm({ access_code: '', file: null });
const downloadingClaimSlip = ref(false);
const claimSlipError = ref('');

function uploadRequirement(requirement) {
    requirementForm.access_code = accessCode.value;
    requirementForm.file = requirementFiles.value[requirement.id] || null;
    requirementForm.post(route('public.requests.requirements.upload', [props.reference_no, requirement.id]), {
        forceFormData: true,
        preserveScroll: true,
    });
}

function uploadPayment() {
    paymentForm.access_code = accessCode.value;
    paymentForm.post(route('public.requests.payment.upload', props.reference_no), {
        forceFormData: true,
        preserveScroll: true,
    });
}

async function downloadClaimSlip() {
    claimSlipError.value = '';
    downloadingClaimSlip.value = true;
    try {
        const response = await axios.post(
            route('public.requests.claim-slip.download', props.reference_no),
            { access_code: accessCode.value },
            { responseType: 'blob' },
        );
        const url = URL.createObjectURL(response.data);
        const link = document.createElement('a');
        link.href = url;
        link.download = `SVCI-Claim-Slip-${props.result.claim_slip.claim_number}.pdf`;
        link.click();
        URL.revokeObjectURL(url);
    } catch {
        claimSlipError.value = 'The private access code is incorrect or the claim slip is unavailable.';
    } finally {
        downloadingClaimSlip.value = false;
    }
}

function statusLabel(value) {
    return String(value || '').replaceAll('_', ' ');
}

function timelineTone(state) {
    return (
        {
            complete: 'border-emerald-200 bg-emerald-50 text-emerald-800',
            active: 'border-brand-200 bg-brand-50 text-brand-800',
            denied: 'border-rose-200 bg-rose-50 text-rose-800',
            upcoming: 'border-slate-200 bg-slate-50 text-slate-500',
        }[state] ?? 'border-slate-200 bg-slate-50 text-slate-500'
    );
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
                                <strong>{{ result.stage_label || statusLabel(result.processing_stage) }}</strong>
                            </p>
                            <p v-if="result.stage_description" class="mt-1 text-sm leading-6 text-slate-600">
                                {{ result.stage_description }}
                            </p>
                            <p v-if="result.expected_release_on" class="mt-1 text-sm text-slate-600">
                                Expected release: {{ result.expected_release_on }}
                            </p>
                        </div>
                        <div class="rounded-2xl bg-brand-50 px-5 py-4 text-sm font-semibold text-brand-800">
                            Submitted {{ result.submitted_at }}
                        </div>
                    </div>

                    <div class="rounded-2xl bg-brand-50 p-5 text-sm leading-6 text-brand-900 ring-1 ring-brand-100">
                        <p class="font-semibold">What happens next</p>
                        <p class="mt-1">{{ result.next_step }}</p>
                    </div>

                    <div
                        v-if="
                            result.clearance &&
                            result.clearance.overall_status !== 'completed' &&
                            !result.action_requirements?.length
                        "
                        class="rounded-2xl bg-sky-50 p-4 text-sm leading-6 text-sky-900 ring-1 ring-sky-100"
                    >
                        <p class="font-semibold">Clearance is moving through the required offices</p>
                        <p class="mt-1">
                            School staff are handling clearance from the request attachments you already submitted.
                        </p>
                    </div>

                    <div v-if="result.clearance?.steps?.length" class="rounded-2xl border border-slate-200 p-5">
                        <h2 class="font-semibold text-slate-900">Clearance detail</h2>
                        <ol class="mt-4 space-y-3">
                            <li
                                v-for="(clearanceStep, index) in result.clearance.steps"
                                :key="`${clearanceStep.label}-${index}`"
                                class="flex items-start gap-3 text-sm"
                            >
                                <span
                                    :class="[
                                        'mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-bold',
                                        clearanceStep.status === 'cleared'
                                            ? 'bg-emerald-100 text-emerald-800'
                                            : clearanceStep.status === 'needs_action'
                                              ? 'bg-rose-100 text-rose-800'
                                              : 'bg-slate-100 text-slate-600',
                                    ]"
                                    >{{ index + 1 }}</span
                                >
                                <span
                                    ><strong>{{ clearanceStep.label }}</strong
                                    ><span class="ml-2 capitalize text-slate-500">{{
                                        statusLabel(clearanceStep.status)
                                    }}</span
                                    ><span v-if="clearanceStep.remarks" class="mt-1 block text-rose-700">{{
                                        clearanceStep.remarks
                                    }}</span></span
                                >
                            </li>
                        </ol>
                    </div>

                    <div
                        v-if="result.action_requirements?.length"
                        class="space-y-4 rounded-2xl border border-rose-200 bg-rose-50 p-5"
                    >
                        <div>
                            <h2 class="font-semibold text-rose-950">A correction is needed</h2>
                            <p class="mt-1 text-sm text-rose-800">
                                Enter the private access code saved when you submitted, then replace each requested
                                file.
                            </p>
                        </div>
                        <label class="block text-sm font-semibold text-rose-950"
                            >Private access code<input
                                v-model="accessCode"
                                autocomplete="one-time-code"
                                class="mt-1 min-h-11 w-full rounded-lg border-rose-300 bg-white font-mono uppercase"
                        /></label>
                        <form
                            v-for="requirement in result.action_requirements"
                            :key="requirement.id"
                            class="rounded-xl bg-white p-4"
                            @submit.prevent="uploadRequirement(requirement)"
                        >
                            <p class="font-semibold">{{ requirement.label }}</p>
                            <p class="mt-1 text-sm text-rose-700">{{ requirement.notes }}</p>
                            <FileUploadField
                                :id="`replacement-${requirement.id}`"
                                class="mt-3"
                                label="Replacement file"
                                required
                                @change="requirementFiles[requirement.id] = $event"
                            />
                            <button
                                type="submit"
                                :disabled="
                                    requirementForm.processing || !requirementFiles[requirement.id] || !accessCode
                                "
                                class="mt-3 min-h-11 rounded-lg bg-rose-700 px-4 text-sm font-semibold text-white disabled:opacity-40"
                            >
                                Upload correction
                            </button>
                        </form>
                    </div>

                    <form
                        v-if="result.payment_open"
                        class="space-y-4 rounded-2xl border border-brand-200 bg-brand-50 p-5"
                        @submit.prevent="uploadPayment"
                    >
                        <div>
                            <h2 class="font-semibold text-brand-950">Submit payment receipt</h2>
                            <p class="mt-1 text-sm text-brand-900">
                                Quoted total: PHP
                                {{
                                    result.payment?.total_amount ||
                                    result.documents.reduce((sum, item) => sum + Number(item.line_total), 0).toFixed(2)
                                }}
                            </p>
                        </div>
                        <label class="block text-sm font-semibold"
                            >Private access code<input
                                v-model="accessCode"
                                autocomplete="one-time-code"
                                class="mt-1 min-h-11 w-full rounded-lg border-brand-300 font-mono uppercase"
                        /></label>
                        <label class="block text-sm font-semibold"
                            >Payment method<input
                                v-model="paymentForm.payment_method"
                                class="mt-1 min-h-11 w-full rounded-lg border-brand-300"
                                placeholder="e.g. GCash or bank deposit"
                        /></label>
                        <label class="block text-sm font-semibold"
                            >Payment reference (optional)<input
                                v-model="paymentForm.reference_number"
                                class="mt-1 min-h-11 w-full rounded-lg border-brand-300"
                        /></label>
                        <FileUploadField
                            id="payment-receipt"
                            label="Payment receipt"
                            required
                            :error="paymentForm.errors.receipt"
                            @change="paymentForm.receipt = $event"
                        />
                        <button
                            type="submit"
                            :disabled="
                                paymentForm.processing ||
                                !accessCode ||
                                !paymentForm.payment_method ||
                                !paymentForm.receipt
                            "
                            class="min-h-11 rounded-lg bg-brand-700 px-5 font-semibold text-white disabled:opacity-40"
                        >
                            {{ paymentForm.processing ? 'Uploading…' : 'Submit receipt' }}
                        </button>
                    </form>

                    <div
                        v-if="result.denial_reason"
                        class="rounded-2xl bg-rose-50 p-4 text-sm leading-6 text-rose-800 ring-1 ring-rose-100"
                    >
                        <p class="font-semibold">Request denied</p>
                        <p class="mt-1"><strong>Reason:</strong> {{ result.denial_reason }}</p>
                        <p class="mt-2">Review the reason above and contact the registrar if you need to resubmit.</p>
                    </div>

                    <div
                        v-if="result.claim_slip"
                        class="rounded-2xl bg-emerald-50 p-4 text-sm leading-6 text-emerald-900 ring-1 ring-emerald-100"
                    >
                        <p class="font-semibold">Ready for pickup</p>
                        <p class="mt-1">
                            Claim number: <strong>{{ result.claim_slip.claim_number }}</strong>
                        </p>
                        <p v-if="result.claim_slip.claim_date">Pickup date: {{ result.claim_slip.claim_date }}</p>
                        <label class="mt-3 block font-semibold"
                            >Private access code<input
                                v-model="accessCode"
                                autocomplete="one-time-code"
                                class="mt-1 min-h-11 w-full rounded-lg border-emerald-300 bg-white font-mono uppercase text-slate-900"
                        /></label>
                        <button
                            type="button"
                            :disabled="!accessCode || downloadingClaimSlip"
                            class="mt-3 min-h-11 rounded-lg bg-emerald-700 px-4 font-semibold text-white disabled:opacity-40"
                            @click="downloadClaimSlip"
                        >
                            {{ downloadingClaimSlip ? 'Preparing…' : 'Download claim slip' }}
                        </button>
                        <p v-if="claimSlipError" class="mt-2 text-rose-700">{{ claimSlipError }}</p>
                    </div>

                    <div
                        v-if="result.processing_stage === 'released'"
                        class="rounded-2xl bg-emerald-50 p-4 text-sm leading-6 text-emerald-900 ring-1 ring-emerald-100"
                    >
                        <p class="font-semibold">Completed</p>
                        <p class="mt-1">This request has been released. Keep the reference number for your records.</p>
                    </div>

                    <ol class="grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
                        <li
                            v-for="stage in result.timeline"
                            :key="stage.key"
                            class="rounded-2xl border p-4 text-sm"
                            :class="timelineTone(stage.state)"
                        >
                            <CheckCircleIcon v-if="['complete', 'active'].includes(stage.state)" class="mb-2 h-5 w-5" />
                            <XCircleIcon v-else-if="stage.state === 'denied'" class="mb-2 h-5 w-5" />
                            <ClockIcon v-else class="mb-2 h-5 w-5" />
                            <span class="block font-semibold">{{ stage.label }}</span>
                            <span class="mt-1 block text-xs leading-5">{{ stage.description }}</span>
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
                    <div class="rounded-2xl bg-slate-100 p-4 text-sm text-slate-700">
                        Need help? Contact the Registrar at <strong>{{ result.registrar_contact.phone }}</strong> or
                        <a
                            :href="`mailto:${result.registrar_contact.email}`"
                            class="font-semibold text-brand-700 underline"
                            >{{ result.registrar_contact.email }}</a
                        >.
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>
