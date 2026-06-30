<script setup>
import FileUploadField from '@/Components/Public/FileUploadField.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    ArrowLeftIcon,
    ArrowRightIcon,
    BanknotesIcon,
    CheckCircleIcon,
    ClipboardDocumentCheckIcon,
    DocumentTextIcon,
    MinusCircleIcon,
    PlusCircleIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    documentTypeGroups: { type: Object, required: true },
    paymentProfile: { type: Object, default: null },
});

const steps = [
    { label: 'Choose documents', description: 'Select records and copies.' },
    { label: 'Requestor details', description: 'Tell us who the request is for.' },
    { label: 'Upload requirements', description: 'Attach required IDs or forms.' },
    { label: 'Payment receipt', description: 'Add payment details and receipt.' },
    { label: 'Review & submit', description: 'Confirm before sending.' },
];

const step = ref(1);
const cart = ref({});
const mobileSummaryOpen = ref(false);

const categoryOrder = ['Academic', 'Certification', 'BasicEd', 'Special'];
const sortedGroups = computed(() =>
    Object.entries(props.documentTypeGroups || {}).sort(([a], [b]) => {
        const ia = categoryOrder.indexOf(a);
        const ib = categoryOrder.indexOf(b);
        return (ia === -1 ? 99 : ia) - (ib === -1 ? 99 : ib);
    }),
);
const allDocumentTypes = computed(() => Object.values(props.documentTypeGroups || {}).flat());
const cartItems = computed(() =>
    Object.entries(cart.value)
        .map(([id, data]) => {
            const type = allDocumentTypes.value.find((doc) => doc.id === Number(id));
            if (!type) return null;
            const pageCount = type.default_page_count || 1;
            const lineTotal = Number(type.fee || 0) * pageCount * data.copies;
            return { type, copies: data.copies, pageCount, lineTotal };
        })
        .filter(Boolean),
);
const requirementList = computed(() => {
    const map = new Map();
    cartItems.value.forEach((item) => {
        (item.type.requirements || []).forEach((requirement) => map.set(requirement.key, requirement));
    });
    return Array.from(map.values());
});
const grandTotal = computed(() => cartItems.value.reduce((sum, item) => sum + item.lineTotal, 0));

const form = useForm({
    requester_name: '',
    requester_email: '',
    requester_contact_number: '',
    items: [],
    purpose: '',
    requirements: {},
    payment_method: '',
    payment_reference_number: '',
    receipt: null,
});

const currentStep = computed(() => steps[step.value - 1]);
const selectedRequirementFilesCount = computed(
    () => requirementList.value.filter((requirement) => form.requirements[requirement.key]).length,
);
const missingRequirementCount = computed(() =>
    Math.max(requirementList.value.length - selectedRequirementFilesCount.value, 0),
);
const hasReceipt = computed(() => Boolean(form.receipt));
const submitErrors = computed(() => Object.entries(form.errors));
const hasSubmitErrors = computed(() => submitErrors.value.length > 0);
const firstErrorMessage = computed(() => submitErrors.value[0]?.[1] ?? '');

watch(requirementList, (requirements) => {
    const keys = requirements.map((requirement) => requirement.key);
    Object.keys(form.requirements).forEach((key) => {
        if (!keys.includes(key)) delete form.requirements[key];
    });
});

function toggleDoc(type) {
    if (cart.value[type.id]) {
        delete cart.value[type.id];
        return;
    }

    cart.value[type.id] = { copies: 1 };
}

function setCopies(typeId, value) {
    if (!cart.value[typeId]) return;
    cart.value[typeId].copies = Math.max(1, Math.min(20, Number(value) || 1));
}

function formatPeso(value) {
    return `PHP ${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function errorStep(field) {
    if (field === 'items' || field.startsWith('items.')) return 1;
    if (['requester_name', 'requester_email', 'requester_contact_number', 'purpose'].includes(field)) return 2;
    if (field.startsWith('requirements.')) return 3;
    if (['payment_method', 'payment_reference_number', 'receipt'].includes(field)) return 4;

    return 5;
}

function submit() {
    form.items = cartItems.value.map((item) => ({ document_type_id: item.type.id, copies: item.copies }));
    form.post(route('public.requests.store'), {
        forceFormData: true,
        preserveScroll: true,
        onError: (errors) => {
            const firstField = Object.keys(errors)[0];
            if (firstField) step.value = errorStep(firstField);
        },
    });
}

function canContinue() {
    if (step.value === 1) return cartItems.value.length > 0;
    if (step.value === 2) {
        return form.requester_name && form.requester_contact_number && form.purpose.trim().length >= 5;
    }
    if (step.value === 3) return missingRequirementCount.value === 0;
    if (step.value === 4) return form.payment_method && hasReceipt.value;

    return true;
}

function requirementMissing(requirement) {
    return step.value >= 3 && !form.requirements[requirement.key];
}
</script>

<template>
    <Head title="Request Document" />

    <main class="min-h-screen bg-slate-50 text-slate-900">
        <section class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                <Link href="/" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to home
                </Link>
                <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_0.75fr] lg:items-end">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-brand-700">Public intake</p>
                        <h1 class="mt-3 font-display text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">
                            Request documents without creating an account.
                        </h1>
                        <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                            Submit requestor details, selected documents, required attachments, and your offline payment
                            receipt in one secure form. Save the reference number after submission.
                        </p>
                    </div>
                    <div class="rounded-3xl bg-slate-950 p-5 text-white shadow-xl">
                        <p class="text-sm font-semibold text-brand-100">Estimated total</p>
                        <p class="mt-2 font-display text-4xl font-bold">{{ formatPeso(grandTotal) }}</p>
                        <p class="mt-2 text-sm text-slate-300">
                            {{ cartItems.length }} selected document{{ cartItems.length === 1 ? '' : 's' }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <form class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8" @submit.prevent="submit">
            <div
                v-if="hasSubmitErrors"
                class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"
                role="alert"
            >
                <p class="font-semibold">Please fix the highlighted information before submitting.</p>
                <p class="mt-1">{{ firstErrorMessage }}</p>
            </div>

            <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:hidden">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Step {{ step }} of {{ steps.length }}
                </p>
                <p class="mt-1 font-display text-xl font-bold text-slate-950">{{ currentStep.label }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ currentStep.description }}</p>
            </div>

            <ol class="mb-8 hidden grid-cols-5 gap-3 md:grid">
                <li
                    v-for="(item, index) in steps"
                    :key="item.label"
                    class="rounded-2xl border px-4 py-3 text-sm"
                    :class="
                        step === index + 1
                            ? 'border-brand-300 bg-brand-50 text-brand-800'
                            : step > index + 1
                              ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                              : 'border-slate-200 bg-white text-slate-500'
                    "
                >
                    <span
                        class="mb-2 inline-flex h-7 w-7 items-center justify-center rounded-full bg-white text-xs font-bold"
                    >
                        {{ index + 1 }}
                    </span>
                    <span class="block font-semibold">{{ item.label }}</span>
                    <span class="mt-1 block text-xs leading-5">{{ item.description }}</span>
                </li>
            </ol>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
                <div class="space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:hidden">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-3 text-left text-sm font-semibold text-slate-900"
                            @click="mobileSummaryOpen = !mobileSummaryOpen"
                        >
                            Request summary
                            <span>{{ mobileSummaryOpen ? 'Hide' : 'Show' }}</span>
                        </button>
                        <div v-if="mobileSummaryOpen" class="mt-4 space-y-3 text-sm">
                            <p class="flex justify-between">
                                <span>Documents</span>
                                <strong>{{ cartItems.length }}</strong>
                            </p>
                            <p class="flex justify-between">
                                <span>Missing requirements</span>
                                <strong>{{ missingRequirementCount }}</strong>
                            </p>
                            <p class="flex justify-between">
                                <span>Receipt</span>
                                <strong>{{ hasReceipt ? 'Selected' : 'Missing' }}</strong>
                            </p>
                            <p class="flex justify-between border-t border-slate-100 pt-3">
                                <span>Total</span>
                                <strong>{{ formatPeso(grandTotal) }}</strong>
                            </p>
                        </div>
                    </div>

                    <section v-if="step === 1" class="space-y-6">
                        <div
                            v-for="[category, docs] in sortedGroups"
                            :key="category"
                            class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-200"
                        >
                            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                                <DocumentTextIcon class="h-5 w-5 text-brand-700" />
                                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-600">
                                    {{ category }}
                                </h2>
                            </div>
                            <div class="divide-y divide-slate-100">
                                <article
                                    v-for="doc in docs"
                                    :key="doc.id"
                                    class="grid gap-4 px-5 py-4 md:grid-cols-[1fr_auto]"
                                    :class="cart[doc.id] ? 'bg-brand-50/60' : 'bg-white'"
                                >
                                    <label class="flex cursor-pointer gap-3">
                                        <input
                                            :checked="!!cart[doc.id]"
                                            type="checkbox"
                                            class="mt-1 h-5 w-5 rounded text-brand-600 focus:ring-brand-500"
                                            @change="toggleDoc(doc)"
                                        />
                                        <span>
                                            <span class="block font-semibold text-slate-950">{{ doc.name }}</span>
                                            <span class="mt-1 block text-sm leading-6 text-slate-500">
                                                {{ doc.description }}
                                            </span>
                                            <span class="mt-2 block text-sm font-semibold text-brand-700">
                                                {{ formatPeso(Number(doc.fee) * (doc.default_page_count || 1)) }} base ·
                                                {{ doc.sla_days }} working days
                                            </span>
                                            <span
                                                v-if="cart[doc.id]"
                                                class="mt-1 block text-sm font-bold text-slate-950"
                                            >
                                                Subtotal:
                                                {{
                                                    formatPeso(
                                                        Number(doc.fee) *
                                                            (doc.default_page_count || 1) *
                                                            cart[doc.id].copies,
                                                    )
                                                }}
                                            </span>
                                        </span>
                                    </label>
                                    <div v-if="cart[doc.id]" class="flex items-center gap-2 md:justify-end">
                                        <button
                                            type="button"
                                            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-full text-slate-500 hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                            @click="setCopies(doc.id, cart[doc.id].copies - 1)"
                                        >
                                            <MinusCircleIcon class="h-7 w-7" />
                                        </button>
                                        <input
                                            :value="cart[doc.id].copies"
                                            type="number"
                                            min="1"
                                            max="20"
                                            class="min-h-11 w-20 rounded-xl border-slate-300 text-center text-sm"
                                            @input="setCopies(doc.id, $event.target.value)"
                                        />
                                        <button
                                            type="button"
                                            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-full text-slate-500 hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                            @click="setCopies(doc.id, cart[doc.id].copies + 1)"
                                        >
                                            <PlusCircleIcon class="h-7 w-7" />
                                        </button>
                                    </div>
                                </article>
                            </div>
                        </div>
                        <p v-if="form.errors.items" class="text-sm text-rose-600">{{ form.errors.items }}</p>
                    </section>

                    <section
                        v-else-if="step === 2"
                        class="grid gap-5 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 md:grid-cols-2"
                    >
                        <div>
                            <label class="text-sm font-semibold text-slate-700" for="requester_name">Full name *</label>
                            <input
                                id="requester_name"
                                v-model="form.requester_name"
                                class="mt-2 min-h-11 w-full rounded-xl border-slate-300"
                                required
                            />
                            <p v-if="form.errors.requester_name" class="mt-1 text-sm text-rose-600">
                                {{ form.errors.requester_name }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700" for="requester_email">Email</label>
                            <input
                                id="requester_email"
                                v-model="form.requester_email"
                                type="email"
                                class="mt-2 min-h-11 w-full rounded-xl border-slate-300"
                            />
                            <p v-if="form.errors.requester_email" class="mt-1 text-sm text-rose-600">
                                {{ form.errors.requester_email }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700" for="requester_contact_number">
                                Contact number *
                            </label>
                            <input
                                id="requester_contact_number"
                                v-model="form.requester_contact_number"
                                class="mt-2 min-h-11 w-full rounded-xl border-slate-300"
                                required
                            />
                            <p v-if="form.errors.requester_contact_number" class="mt-1 text-sm text-rose-600">
                                {{ form.errors.requester_contact_number }}
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm font-semibold text-slate-700" for="purpose">Purpose *</label>
                            <textarea
                                id="purpose"
                                v-model="form.purpose"
                                rows="4"
                                maxlength="500"
                                class="mt-2 w-full rounded-xl border-slate-300"
                                required
                            />
                            <p v-if="form.errors.purpose" class="mt-1 text-sm text-rose-600">
                                {{ form.errors.purpose }}
                            </p>
                        </div>
                    </section>

                    <section v-else-if="step === 3" class="space-y-4">
                        <div
                            class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-slate-600"
                        >
                            <ClipboardDocumentCheckIcon class="h-5 w-5 text-brand-700" /> Required attachments
                        </div>
                        <FileUploadField
                            v-for="requirement in requirementList"
                            :id="`requirement-${requirement.key}`"
                            :key="requirement.key"
                            :label="requirement.label"
                            :hint="
                                requirement.hint || 'Upload a clear scanned copy or photo. JPG, PNG, or PDF up to 5 MB.'
                            "
                            :error="form.errors[`requirements.${requirement.key}`]"
                            :missing="requirementMissing(requirement)"
                            required
                            @change="(file) => (form.requirements[requirement.key] = file)"
                        />
                        <p
                            v-if="!requirementList.length"
                            class="rounded-2xl bg-white p-4 text-sm text-slate-600 shadow-sm ring-1 ring-slate-200"
                        >
                            No extra requirement files are needed for the selected documents.
                        </p>
                    </section>

                    <section v-else-if="step === 4" class="grid gap-6 lg:grid-cols-[1fr_0.8fr]">
                        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                            <div class="flex items-center gap-2 font-semibold text-slate-950">
                                <BanknotesIcon class="h-5 w-5 text-brand-700" /> Payment instructions
                            </div>
                            <div v-if="paymentProfile" class="mt-4 space-y-2 text-sm text-slate-600">
                                <p>
                                    <strong>{{ paymentProfile.bank_name }}</strong>
                                </p>
                                <p>{{ paymentProfile.account_name }} · {{ paymentProfile.account_number }}</p>
                                <p v-if="paymentProfile.instructions">{{ paymentProfile.instructions }}</p>
                                <img
                                    v-if="paymentProfile.qr_url"
                                    :src="paymentProfile.qr_url"
                                    alt="Payment QR code"
                                    class="mt-3 max-h-48 rounded-2xl border border-slate-200"
                                />
                            </div>
                            <p v-else class="mt-3 text-sm text-slate-600">
                                Payment profile is not configured. Ask the registrar for payment instructions before
                                submitting.
                            </p>
                        </div>
                        <div class="space-y-4">
                            <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                                <label class="text-sm font-semibold text-slate-700" for="payment_method">
                                    Payment method *
                                </label>
                                <input
                                    id="payment_method"
                                    v-model="form.payment_method"
                                    placeholder="GCash, bank transfer, cash deposit"
                                    class="mt-2 min-h-11 w-full rounded-xl border-slate-300"
                                    required
                                />
                                <p v-if="form.errors.payment_method" class="mt-1 text-sm text-rose-600">
                                    {{ form.errors.payment_method }}
                                </p>
                                <label
                                    class="mt-4 block text-sm font-semibold text-slate-700"
                                    for="payment_reference_number"
                                >
                                    Payment reference
                                </label>
                                <input
                                    id="payment_reference_number"
                                    v-model="form.payment_reference_number"
                                    placeholder="Optional transaction/reference number"
                                    class="mt-2 min-h-11 w-full rounded-xl border-slate-300"
                                />
                            </div>
                            <FileUploadField
                                id="payment-receipt"
                                label="Payment receipt"
                                hint="Upload the receipt image or PDF. JPG, PNG, or PDF up to 5 MB."
                                :error="form.errors.receipt"
                                :missing="!hasReceipt"
                                required
                                @change="(file) => (form.receipt = file)"
                            />
                        </div>
                    </section>

                    <section v-else class="grid gap-6 lg:grid-cols-[1fr_0.8fr]">
                        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="font-display text-2xl font-bold text-slate-950">Review before submitting</h2>
                            <ul class="mt-5 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                                <li
                                    v-for="item in cartItems"
                                    :key="item.type.id"
                                    class="flex justify-between gap-4 p-4 text-sm"
                                >
                                    <span>{{ item.type.name }} x {{ item.copies }}</span>
                                    <strong>{{ formatPeso(item.lineTotal) }}</strong>
                                </li>
                            </ul>
                            <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt class="text-slate-500">Requestor</dt>
                                    <dd class="font-semibold text-slate-900">{{ form.requester_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-slate-500">Requirements</dt>
                                    <dd class="font-semibold text-slate-900">
                                        {{ selectedRequirementFilesCount }} of {{ requirementList.length }} selected
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-slate-500">Payment method</dt>
                                    <dd class="font-semibold text-slate-900">{{ form.payment_method }}</dd>
                                </div>
                                <div>
                                    <dt class="text-slate-500">Receipt</dt>
                                    <dd class="font-semibold text-slate-900">
                                        {{ hasReceipt ? 'Selected' : 'Missing' }}
                                    </dd>
                                </div>
                            </dl>
                            <p class="mt-4 text-sm text-slate-600">
                                Reference tracking starts after submission. Keep the generated reference number.
                            </p>
                        </div>
                        <div class="rounded-3xl bg-slate-950 p-6 text-white shadow-xl">
                            <ShieldCheckIcon class="h-9 w-9 text-brand-200" />
                            <p class="mt-4 text-sm text-slate-300">Total to verify</p>
                            <p class="mt-1 font-display text-4xl font-bold">{{ formatPeso(grandTotal) }}</p>
                            <button
                                type="submit"
                                class="mt-6 inline-flex w-full min-h-12 items-center justify-center gap-2 rounded-xl bg-accent-500 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-accent-600 disabled:opacity-60"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Submitting request...' : 'Submit public request' }}
                                <CheckCircleIcon class="h-5 w-5" />
                            </button>
                            <p v-if="form.errors.items" class="mt-3 text-sm text-rose-200">{{ form.errors.items }}</p>
                        </div>
                    </section>

                    <div class="flex items-center justify-between gap-3">
                        <button
                            v-if="step > 1"
                            type="button"
                            class="inline-flex min-h-12 items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700"
                            @click="step -= 1"
                        >
                            <ArrowLeftIcon class="h-5 w-5" /> Back
                        </button>
                        <span v-else></span>
                        <button
                            v-if="step < steps.length"
                            type="button"
                            class="inline-flex min-h-12 items-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 disabled:opacity-50"
                            :disabled="!canContinue()"
                            @click="step += 1"
                        >
                            Continue <ArrowRightIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <aside class="hidden lg:block">
                    <div class="sticky top-24 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        <h2 class="font-display text-lg font-bold text-slate-950">Request summary</h2>
                        <ul v-if="cartItems.length" class="mt-4 divide-y divide-slate-100">
                            <li v-for="item in cartItems" :key="item.type.id" class="py-3 text-sm">
                                <div class="flex justify-between gap-3">
                                    <span class="font-medium text-slate-900">{{ item.type.name }}</span>
                                    <span>{{ item.copies }}x</span>
                                </div>
                                <p class="mt-1 text-right font-semibold text-slate-950">
                                    {{ formatPeso(item.lineTotal) }}
                                </p>
                            </li>
                        </ul>
                        <p v-else class="mt-4 rounded-2xl bg-slate-50 p-3 text-sm text-slate-500">
                            Select at least one document to start.
                        </p>
                        <dl class="mt-4 space-y-3 border-t border-slate-100 pt-4 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Missing requirements</dt>
                                <dd class="font-semibold text-slate-900">{{ missingRequirementCount }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Receipt</dt>
                                <dd class="font-semibold text-slate-900">{{ hasReceipt ? 'Selected' : 'Missing' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3 border-t border-slate-100 pt-3">
                                <dt class="font-semibold text-slate-900">Total</dt>
                                <dd class="font-display text-lg font-bold text-slate-950">
                                    {{ formatPeso(grandTotal) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </aside>
            </div>
        </form>
    </main>
</template>
