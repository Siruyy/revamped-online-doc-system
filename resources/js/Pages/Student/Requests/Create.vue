<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import FormField from '@/Components/UI/FormField.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    CheckCircleIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ClockIcon,
    DocumentTextIcon,
    InformationCircleIcon,
    BuildingOffice2Icon,
    CurrencyDollarIcon,
    ClipboardDocumentCheckIcon,
    PlusCircleIcon,
    MinusCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    documentTypeGroups: { type: Object, required: true },
    pendingRequestExists: { type: Boolean, required: true },
    student: { type: Object, required: true },
});

const step = ref(1);
const totalSteps = 4;

const categoryOrder = ['Academic', 'Certification', 'BasicEd', 'Special'];
const sortedGroups = computed(() => {
    const entries = Object.entries(props.documentTypeGroups || {});
    return entries.sort(([a], [b]) => {
        const ia = categoryOrder.indexOf(a);
        const ib = categoryOrder.indexOf(b);
        return (ia === -1 ? 99 : ia) - (ib === -1 ? 99 : ib);
    });
});

const allDocumentTypes = computed(() => Object.values(props.documentTypeGroups || {}).flat());

// Cart: { [docTypeId]: { copies: number } }
const cart = ref({});

const cartItems = computed(() =>
    Object.entries(cart.value)
        .map(([id, data]) => {
            const type = allDocumentTypes.value.find((t) => t.id === Number(id));
            if (!type) return null;
            const pageCount = type.default_page_count || 1;
            const lineTotal = computeLineTotal(type, pageCount, data.copies);
            return { type, copies: data.copies, pageCount, lineTotal };
        })
        .filter(Boolean),
);

const grandTotal = computed(() => cartItems.value.reduce((sum, item) => sum + item.lineTotal, 0));

function computeLineTotal(type, pageCount, copies) {
    return +(Number(type.fee) * pageCount * copies).toFixed(2);
}

function toggleDoc(type) {
    if (cart.value[type.id]) {
        delete cart.value[type.id];
    } else {
        cart.value[type.id] = { copies: 1 };
    }
}

function setCopies(typeId, val) {
    const n = Math.max(1, Math.min(20, Number(val)));
    if (cart.value[typeId]) {
        cart.value[typeId].copies = n;
    }
}

const form = useForm({
    items: [],
    purpose: '',
    has_cno: false,
    has_external_notice: false,
    special_class_eligibility: {
        graduating_this_term: false,
        subject_deficiency_certified: false,
        subject_conflict: false,
    },
});

// Policy flags across selected items
const hasSpecialClass = computed(() =>
    cartItems.value.some((i) => i.type.flags?.includes('eligibility_special_class')),
);
const isTransferredStudent = computed(() => ['transferred', 'dismissed'].includes(props.student.academic_status));

function prev() {
    if (step.value > 1) step.value -= 1;
}
function next() {
    if (!canAdvance.value) return;
    if (step.value < totalSteps) step.value += 1;
}

const canAdvance = computed(() => {
    if (props.pendingRequestExists) return false;
    if (step.value === 1) return Object.keys(cart.value).length > 0;
    if (step.value === 2) {
        if (!form.purpose || form.purpose.trim().length < 5) return false;
        if (hasSpecialClass.value) {
            const e = form.special_class_eligibility;
            if (!e.graduating_this_term && !e.subject_deficiency_certified && !e.subject_conflict) return false;
        }
        if (isTransferredStudent.value) {
            if (!form.has_cno || !form.has_external_notice) return false;
        }
        return true;
    }
    return true;
});

function submit() {
    form.items = cartItems.value.map((item) => ({
        document_type_id: item.type.id,
        copies: item.copies,
    }));
    form.post(route('student.requests.wizard.store'));
}

function formatPeso(n) {
    return `₱${Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function feeLabel(type) {
    const pages = type.default_page_count || 1;
    return `${formatPeso(type.fee)}/page × ${pages} page${pages !== 1 ? 's' : ''}`;
}
</script>

<template>
    <Head title="New Document Request" />

    <StudentLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="font-display text-2xl font-bold tracking-tight text-slate-900">Submit a Document Request</h2>
                <p class="text-sm text-slate-500">
                    Select one or more documents, set copies, then follow the guided steps.
                </p>
            </div>
        </template>

        <div class="mx-auto max-w-5xl px-4 pb-16 sm:px-6 lg:px-8">
            <!-- Blocking banner -->
            <div
                v-if="pendingRequestExists"
                class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 shadow-sm"
            >
                <InformationCircleIcon class="mt-0.5 h-5 w-5 flex-none" />
                <div>
                    <p class="font-semibold">You already have an active request.</p>
                    <p>Please wait until it is resolved before submitting another.</p>
                </div>
            </div>

            <!-- Stepper -->
            <ol
                class="mb-8 flex items-center justify-between gap-2 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200"
            >
                <li
                    v-for="(label, index) in ['Select Documents', 'Details', 'Fees & Timeline', 'Review']"
                    :key="label"
                    class="flex flex-1 items-center gap-2"
                >
                    <div
                        class="flex h-8 w-8 flex-none items-center justify-center rounded-full text-xs font-semibold transition"
                        :class="
                            step > index + 1
                                ? 'bg-brand-600 text-white'
                                : step === index + 1
                                  ? 'bg-brand-100 text-brand-700 ring-2 ring-brand-500'
                                  : 'bg-slate-100 text-slate-500'
                        "
                    >
                        <CheckCircleIcon v-if="step > index + 1" class="h-5 w-5" />
                        <span v-else>{{ index + 1 }}</span>
                    </div>
                    <span
                        class="hidden text-sm font-medium sm:inline"
                        :class="step === index + 1 ? 'text-slate-900' : 'text-slate-500'"
                    >
                        {{ label }}
                    </span>
                    <span v-if="index < 3" class="ml-2 hidden h-px flex-1 bg-slate-200 sm:block"></span>
                </li>
            </ol>

            <form :aria-busy="form.processing ? 'true' : undefined" @submit.prevent="submit">
                <!-- STEP 1: Select documents + set copies -->
                <section v-if="step === 1" class="space-y-6">
                    <!-- Cart summary strip -->
                    <div
                        v-if="Object.keys(cart).length"
                        class="flex flex-wrap items-center gap-3 rounded-xl border border-brand-200 bg-brand-50 px-5 py-3"
                    >
                        <span class="text-sm font-semibold text-brand-700">
                            {{ Object.keys(cart).length }} document{{ Object.keys(cart).length !== 1 ? 's' : '' }}
                            selected
                        </span>
                        <span class="text-sm text-brand-600">·</span>
                        <span class="text-sm font-bold text-brand-700"
                            >{{ formatPeso(grandTotal) }} estimated total</span
                        >
                    </div>

                    <div
                        v-for="[category, docs] in sortedGroups"
                        :key="category"
                        class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200"
                    >
                        <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-3">
                            <DocumentTextIcon class="h-5 w-5 text-brand-600" />
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">
                                {{ category }}
                            </h3>
                        </div>

                        <div class="divide-y divide-slate-100">
                            <div
                                v-for="doc in docs"
                                :key="doc.id"
                                class="flex cursor-pointer items-start gap-4 px-5 py-4 transition hover:bg-brand-50/40"
                                :class="cart[doc.id] ? 'bg-brand-50/60' : ''"
                            >
                                <!-- Checkbox toggle -->
                                <input
                                    :id="`document-type-${doc.id}`"
                                    :checked="!!cart[doc.id]"
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 flex-none rounded text-brand-600 focus:ring-brand-500"
                                    @change="toggleDoc(doc)"
                                />
                                <!-- Doc info -->
                                <label class="flex-1 cursor-pointer" :for="`document-type-${doc.id}`">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="font-semibold text-slate-900">{{ doc.name }}</p>
                                        <div class="flex items-center gap-3 text-xs text-slate-500">
                                            <span class="inline-flex items-center gap-1">
                                                <ClockIcon class="h-3.5 w-3.5" />
                                                {{ doc.sla_days }}d
                                            </span>
                                            <span class="inline-flex items-center gap-1 font-medium text-brand-700">
                                                <CurrencyDollarIcon class="h-3.5 w-3.5" />
                                                {{ feeLabel(doc) }}
                                            </span>
                                        </div>
                                    </div>
                                    <p v-if="doc.description" class="mt-0.5 text-sm text-slate-500">
                                        {{ doc.description }}
                                    </p>
                                </label>
                                <!-- Copies stepper (only when selected) -->
                                <div v-if="cart[doc.id]" class="flex flex-none items-center gap-2" @click.stop>
                                    <IconButton
                                        :label="`Decrease copies for ${doc.name}`"
                                        @click="setCopies(doc.id, (cart[doc.id]?.copies || 1) - 1)"
                                    >
                                        <MinusCircleIcon class="h-6 w-6" aria-hidden="true" />
                                    </IconButton>
                                    <label :for="`copies-${doc.id}`" class="sr-only">Copies for {{ doc.name }}</label>
                                    <input
                                        :id="`copies-${doc.id}`"
                                        :value="cart[doc.id]?.copies"
                                        type="number"
                                        min="1"
                                        max="20"
                                        class="w-14 rounded-md border-slate-300 text-center text-sm shadow-sm"
                                        @input="setCopies(doc.id, $event.target.value)"
                                    />
                                    <IconButton
                                        :label="`Increase copies for ${doc.name}`"
                                        @click="setCopies(doc.id, (cart[doc.id]?.copies || 1) + 1)"
                                    >
                                        <PlusCircleIcon class="h-6 w-6" aria-hidden="true" />
                                    </IconButton>
                                </div>
                            </div>
                        </div>
                    </div>
                    <InputError :message="form.errors.items" />
                    <p
                        v-if="!Object.keys(cart).length && !form.errors.items"
                        class="text-center text-sm text-slate-500"
                    >
                        Check the documents you need above. You can request multiple at once.
                    </p>
                </section>

                <!-- STEP 2: Purpose + policy gates -->
                <section v-else-if="step === 2" class="space-y-6">
                    <!-- Selected items quick list -->
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-600">
                            Selected Documents
                        </h4>
                        <ul class="space-y-2 text-sm">
                            <li
                                v-for="item in cartItems"
                                :key="item.type.id"
                                class="flex items-center justify-between gap-4 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2"
                            >
                                <span class="font-medium text-slate-800">{{ item.type.name }}</span>
                                <span class="text-slate-500"
                                    >{{ item.copies }} {{ item.copies === 1 ? 'copy' : 'copies' }}</span
                                >
                            </li>
                        </ul>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <FormField
                            id="request-purpose"
                            class="sm:col-span-2"
                            label="Purpose"
                            :error="form.errors.purpose"
                            help="Briefly describe why you need these documents (minimum 5 characters)."
                            required
                        >
                            <template #default="{ id, describedBy, invalid }">
                                <textarea
                                    :id="id"
                                    v-model="form.purpose"
                                    rows="3"
                                    maxlength="500"
                                    required
                                    placeholder="e.g., For scholarship application, employment, board exam, transfer to another school…"
                                    class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                    :class="{ 'border-rose-400': form.errors.purpose }"
                                    :aria-describedby="describedBy"
                                    :aria-invalid="invalid ? 'true' : undefined"
                                ></textarea>
                            </template>
                        </FormField>
                    </div>

                    <!-- Special class eligibility gate -->
                    <div v-if="hasSpecialClass" class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                        <h4 class="flex items-center gap-2 font-semibold text-amber-800">
                            <ClipboardDocumentCheckIcon class="h-5 w-5" />
                            Special Class Eligibility (Policy §12.1)
                        </h4>
                        <p class="mt-1 text-sm text-amber-800">
                            You must confirm at least one eligibility criterion below.
                        </p>
                        <div class="mt-4 space-y-3 text-sm text-amber-900">
                            <label class="flex items-start gap-3" for="special-graduating-this-term">
                                <input
                                    id="special-graduating-this-term"
                                    v-model="form.special_class_eligibility.graduating_this_term"
                                    type="checkbox"
                                    class="mt-0.5 rounded border-amber-400 text-amber-600 focus:ring-amber-500"
                                />
                                I am graduating in the immediate term.
                            </label>
                            <label class="flex items-start gap-3" for="special-subject-deficiency-certified">
                                <input
                                    id="special-subject-deficiency-certified"
                                    v-model="form.special_class_eligibility.subject_deficiency_certified"
                                    type="checkbox"
                                    class="mt-0.5 rounded border-amber-400 text-amber-600 focus:ring-amber-500"
                                />
                                I have a subject deficiency certified by the Office of the Registrar.
                            </label>
                            <label class="flex items-start gap-3" for="special-subject-conflict">
                                <input
                                    id="special-subject-conflict"
                                    v-model="form.special_class_eligibility.subject_conflict"
                                    type="checkbox"
                                    class="mt-0.5 rounded border-amber-400 text-amber-600 focus:ring-amber-500"
                                />
                                I have a subject conflict with other enrolled subjects this term.
                            </label>
                        </div>
                    </div>

                    <!-- §16 transferred-student gate -->
                    <div v-if="isTransferredStudent" class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
                        <h4 class="flex items-center gap-2 font-semibold text-rose-800">
                            <InformationCircleIcon class="h-5 w-5" />
                            Transferred / Dismissed Student Notice (Policy §16)
                        </h4>
                        <p class="mt-1 text-sm text-rose-800">
                            SVCI no longer releases records for transferred students. You may only proceed if you can
                            submit a
                            <strong>Certificate of No Objection (CNO)</strong> from your receiving institution and an
                            official external notice.
                        </p>
                        <div class="mt-4 space-y-3 text-sm text-rose-900">
                            <label class="flex items-start gap-3" for="has-cno">
                                <input
                                    id="has-cno"
                                    v-model="form.has_cno"
                                    type="checkbox"
                                    class="mt-0.5 rounded border-rose-400 text-rose-600 focus:ring-rose-500"
                                />
                                I have a Certificate of No Objection from my receiving institution.
                            </label>
                            <label class="flex items-start gap-3" for="has-external-notice">
                                <input
                                    id="has-external-notice"
                                    v-model="form.has_external_notice"
                                    type="checkbox"
                                    class="mt-0.5 rounded border-rose-400 text-rose-600 focus:ring-rose-500"
                                />
                                I have the official external notice from the requesting agency/employer.
                            </label>
                        </div>
                    </div>
                </section>

                <!-- STEP 3: Itemized fees & timeline -->
                <section v-else-if="step === 3" class="space-y-6">
                    <!-- Fee breakdown table -->
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <h4
                            class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-slate-600"
                        >
                            <CurrencyDollarIcon class="h-5 w-5 text-brand-600" />
                            Itemized Fee Breakdown
                        </h4>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                                    <th class="pb-2 text-left font-semibold">Document</th>
                                    <th class="pb-2 text-center font-semibold">Pages</th>
                                    <th class="pb-2 text-center font-semibold">Copies</th>
                                    <th class="pb-2 text-right font-semibold">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="item in cartItems" :key="item.type.id" class="text-slate-700">
                                    <td class="py-2.5">
                                        <p class="font-medium text-slate-900">{{ item.type.name }}</p>
                                        <p class="text-xs text-slate-400">{{ formatPeso(item.type.fee) }} / page</p>
                                    </td>
                                    <td class="py-2.5 text-center">
                                        {{ item.pageCount }}
                                    </td>
                                    <td class="py-2.5 text-center">{{ item.copies }}</td>
                                    <td class="py-2.5 text-right font-semibold text-slate-900">
                                        {{ formatPeso(item.lineTotal) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-slate-200">
                                    <td colspan="3" class="pt-3 text-sm font-semibold text-slate-700">Grand Total</td>
                                    <td class="pt-3 text-right text-xl font-bold text-brand-700">
                                        {{ formatPeso(grandTotal) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                        <p class="mt-3 text-xs text-slate-500">
                            The Accounting Office may adjust the final amount (e.g., actual TOR pages).
                            <strong>You pay only after admin approval of your request.</strong>
                        </p>
                    </div>

                    <!-- Per-document timelines -->
                    <div class="space-y-4">
                        <div
                            v-for="item in cartItems"
                            :key="item.type.id"
                            class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"
                        >
                            <div class="flex items-center gap-2">
                                <ClockIcon class="h-5 w-5 text-brand-600" />
                                <h5 class="text-sm font-semibold text-slate-700">{{ item.type.name }}</h5>
                                <span class="ml-auto rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                                    {{ item.type.sla_days }} working day{{ item.type.sla_days === 1 ? '' : 's' }}
                                </span>
                            </div>
                            <div v-if="item.type.offices?.length" class="mt-2 flex flex-wrap gap-2">
                                <span
                                    v-for="office in item.type.offices"
                                    :key="office.key"
                                    class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-700"
                                >
                                    <BuildingOffice2Icon class="h-3 w-3" />
                                    {{ office.label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- STEP 4: Review & Submit -->
                <section v-else class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <h4 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-600">
                            Review Your Request
                        </h4>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                                    <th class="pb-2 text-left font-semibold">Document</th>
                                    <th class="pb-2 text-center font-semibold">Pages</th>
                                    <th class="pb-2 text-center font-semibold">Copies</th>
                                    <th class="pb-2 text-right font-semibold">Line Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="item in cartItems" :key="item.type.id" class="text-slate-700">
                                    <td class="py-2.5 font-medium text-slate-900">{{ item.type.name }}</td>
                                    <td class="py-2.5 text-center text-slate-600">{{ item.pageCount }}</td>
                                    <td class="py-2.5 text-center text-slate-600">{{ item.copies }}</td>
                                    <td class="py-2.5 text-right font-semibold">{{ formatPeso(item.lineTotal) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-slate-200">
                                    <td colspan="3" class="pt-3 font-semibold text-slate-700">Grand Total</td>
                                    <td class="pt-3 text-right text-xl font-bold text-brand-700">
                                        {{ formatPeso(grandTotal) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <p class="text-sm text-slate-500">
                                <span class="font-medium text-slate-700">Purpose:</span> {{ form.purpose }}
                            </p>
                        </div>
                    </div>

                    <!-- What Happens Next — policy-initial flow -->
                    <div class="rounded-2xl bg-brand-50 p-6 ring-1 ring-brand-100">
                        <h4 class="text-sm font-semibold uppercase tracking-wider text-brand-700">What Happens Next</h4>
                        <ol class="mt-3 space-y-3 text-sm text-brand-900">
                            <li class="flex items-start gap-3">
                                <span
                                    class="flex h-6 w-6 flex-none items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white"
                                    >1</span
                                >
                                <span
                                    ><strong>Admin reviews</strong> your request for policy eligibility and approves
                                    it.</span
                                >
                            </li>
                            <li class="flex items-start gap-3">
                                <span
                                    class="flex h-6 w-6 flex-none items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white"
                                    >2</span
                                >
                                <span
                                    ><strong>Once approved,</strong> you'll see the payment details (bank account or QR
                                    code) on your request page. The estimated amount is
                                    <strong>{{ formatPeso(grandTotal) }}</strong
                                    >.</span
                                >
                            </li>
                            <li class="flex items-start gap-3">
                                <span
                                    class="flex h-6 w-6 flex-none items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white"
                                    >3</span
                                >
                                <span
                                    ><strong>Upload your payment receipt</strong> (screenshot or scan of your transfer
                                    confirmation).</span
                                >
                            </li>
                            <li class="flex items-start gap-3">
                                <span
                                    class="flex h-6 w-6 flex-none items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white"
                                    >4</span
                                >
                                <span
                                    ><strong>Admin verifies</strong> your payment, then department offices sign off on
                                    clearance.</span
                                >
                            </li>
                            <li class="flex items-start gap-3">
                                <span
                                    class="flex h-6 w-6 flex-none items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white"
                                    >5</span
                                >
                                <span
                                    ><strong>Claim slip issued</strong> once processing is complete — present it at the
                                    releasing window.</span
                                >
                            </li>
                        </ol>
                    </div>
                </section>

                <!-- Actions -->
                <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <SecondaryButton v-if="step > 1" type="button" @click="prev">
                        <ChevronLeftIcon class="mr-1 h-4 w-4" />
                        Back
                    </SecondaryButton>
                    <div v-else></div>

                    <PrimaryButton v-if="step < totalSteps" type="button" :disabled="!canAdvance" @click="next">
                        Continue
                        <ChevronRightIcon class="ml-1 h-4 w-4" />
                    </PrimaryButton>
                    <PrimaryButton
                        v-else
                        type="submit"
                        :disabled="pendingRequestExists || form.processing || !cartItems.length"
                        :aria-busy="form.processing ? 'true' : undefined"
                    >
                        <CheckCircleIcon class="mr-1 h-5 w-5" />
                        {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </StudentLayout>
</template>
