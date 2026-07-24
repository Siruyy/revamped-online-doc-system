<script setup>
import FileUploadField from '@/Components/Public/FileUploadField.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ArrowLeftIcon,
    ArrowRightIcon,
    CheckCircleIcon,
    ChevronDownIcon,
    MinusIcon,
    PlusIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    documentTypeGroups: { type: Object, required: true },
    programs: { type: Array, required: true },
    uploadLimits: { type: Object, default: () => ({}) },
});

const steps = ['Documents', 'Personal data', 'Education & release', 'Requirements', 'Review'];
const step = ref(1);
const selected = ref({});
const openCategories = ref(new Set(['Academic']));
const form = useForm({
    requester_name: '',
    requester_email: '',
    requester_contact_number: '',
    requester_student_id: '',
    academic_program_id: '',
    requester_year_level: '',
    requester_graduation_or_last_sem: '',
    birth_date: '',
    birth_place: '',
    sex: '',
    civil_status: '',
    citizenship: 'Filipino',
    home_address: '',
    father_name: '',
    mother_maiden_name: '',
    parents_address: '',
    guardian_name: '',
    guardian_address: '',
    education: {
        elementary: { school: '', address: '', year: '' },
        junior_high: { school: '', address: '', year: '' },
        senior_high: { school: '', address: '', year: '' },
    },
    employment_status: 'not_employed',
    company_name: '',
    company_address: '',
    purpose: '',
    fulfillment_method: 'pickup',
    delivery_address: '',
    is_proxy_request: false,
    items: [],
    requirements: {},
});

const allTypes = computed(() => Object.values(props.documentTypeGroups).flat());
const cart = computed(() =>
    Object.entries(selected.value)
        .map(([id, values]) => {
            const type = allTypes.value.find((item) => item.id === Number(id));
            return type ? { type, ...values } : null;
        })
        .filter(Boolean),
);
const requirementList = computed(() => {
    const map = new Map([
        ['photo_2x2', { key: 'photo_2x2', label: 'Recent 2×2 ID photo', hint: 'White background and collared shirt.' }],
        [
            'psa_birth_certificate',
            { key: 'psa_birth_certificate', label: 'PSA birth certificate', hint: 'Clear JPG, PNG, or PDF.' },
        ],
    ]);
    cart.value.forEach((item) =>
        item.type.requirements.forEach((requirement) => map.set(requirement.key, requirement)),
    );
    if (form.civil_status === 'Married')
        map.set('marriage_certificate', {
            key: 'marriage_certificate',
            label: 'Marriage certificate',
            hint: 'Required for married requestors.',
        });
    if (form.is_proxy_request) {
        map.set('authorization_letter', {
            key: 'authorization_letter',
            label: 'Authorization letter',
            hint: 'Signed by the document owner.',
        });
        map.set('spa', {
            key: 'spa',
            label: 'Special Power of Attorney',
            hint: 'Required for an authorized representative.',
        });
    }
    return [...map.values()];
});
const selectedProgram = computed(() =>
    props.programs.find((program) => program.id === Number(form.academic_program_id)),
);
const errorSummary = computed(() => Object.values(form.errors)[0]);

function toggleCategory(category) {
    const next = new Set(openCategories.value);
    next.has(category) ? next.delete(category) : next.add(category);
    openCategories.value = next;
}

function toggleDocument(type) {
    if (selected.value[type.id]) {
        delete selected.value[type.id];
    } else {
        selected.value[type.id] = {
            copies: 1,
            authentication_requested: false,
            documentary_stamp_requested: false,
            semester_requested: '',
        };
    }
}

function changeCopies(id, amount) {
    selected.value[id].copies = Math.max(1, Math.min(20, selected.value[id].copies + amount));
}

function canContinue() {
    if (step.value === 1) return cart.value.length > 0;
    if (step.value === 2) {
        return (
            form.requester_name &&
            form.requester_email &&
            form.requester_contact_number &&
            form.academic_program_id &&
            form.requester_year_level &&
            form.requester_graduation_or_last_sem &&
            form.birth_date &&
            form.birth_place &&
            form.sex &&
            form.civil_status &&
            form.citizenship &&
            form.home_address
        );
    }
    if (step.value === 3) {
        const educationComplete = Object.values(form.education).every(
            (entry) => entry.school && entry.address && entry.year,
        );
        const employmentComplete =
            form.employment_status === 'not_employed' || (form.company_name && form.company_address);
        const deliveryComplete = form.fulfillment_method === 'pickup' || form.delivery_address;
        return educationComplete && employmentComplete && deliveryComplete && form.purpose.length >= 5;
    }
    if (step.value === 4) return requirementList.value.every((requirement) => form.requirements[requirement.key]);
    return true;
}

function next() {
    if (canContinue() && step.value < steps.length) step.value += 1;
}

function submit() {
    form.items = cart.value.map((item) => ({
        document_type_id: item.type.id,
        copies: item.copies,
        authentication_requested: item.authentication_requested,
        documentary_stamp_requested: item.documentary_stamp_requested,
        semester_requested: item.semester_requested || null,
    }));
    form.post(route('public.requests.store'), {
        forceFormData: true,
        preserveScroll: true,
        onError: () => {
            const field = Object.keys(form.errors)[0] || '';
            if (field.startsWith('items')) step.value = 1;
            else if (
                field.startsWith('education') ||
                ['purpose', 'fulfillment_method', 'delivery_address'].includes(field)
            )
                step.value = 3;
            else if (field.startsWith('requirements')) step.value = 4;
            else step.value = 2;
        },
    });
}

function feeNote(type) {
    if (['diploma', 'special_order'].includes(type.code)) return 'Registrar will provide the official amount.';
    if (type.fee_formula === 'per_page')
        return `Starts at ₱${Number(type.fee).toFixed(2)} per page; final pages are evaluated by the registrar.`;
    return `Reference rate ₱${Number(type.fee).toFixed(2)}; final quote follows review.`;
}
</script>

<template>
    <Head title="Request Documents" />
    <main class="min-h-screen bg-slate-50 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-6xl px-4 py-7 sm:px-6 lg:px-8">
                <Link href="/" class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-brand-700">
                    <ArrowLeftIcon class="h-4 w-4" /> Back to home
                </Link>
                <div class="mt-5 max-w-3xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-700">
                        Office of the Registrar
                    </p>
                    <h1 class="mt-2 font-display text-3xl font-bold tracking-tight sm:text-5xl">
                        Request your academic records
                    </h1>
                    <p class="mt-3 text-base leading-7 text-slate-600">
                        Submit the request first. The registrar will verify page counts, authentication, documentary
                        stamps, and delivery before giving you a locked amount. Payment comes after clearance.
                    </p>
                </div>
            </div>
        </header>

        <form class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8" @submit.prevent="submit">
            <div
                v-if="errorSummary"
                role="alert"
                class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"
            >
                <strong>Please review the highlighted information.</strong> {{ errorSummary }}
            </div>

            <ol class="mb-8 grid grid-cols-5 gap-1" aria-label="Request progress">
                <li v-for="(label, index) in steps" :key="label" class="min-w-0">
                    <div :class="['h-1.5 rounded-full', index + 1 <= step ? 'bg-brand-700' : 'bg-slate-200']" />
                    <p
                        :class="[
                            'mt-2 truncate text-xs font-semibold',
                            index + 1 === step ? 'text-brand-800' : 'text-slate-500',
                        ]"
                    >
                        {{ index + 1 }}. {{ label }}
                    </p>
                </li>
            </ol>

            <section v-if="step === 1" class="space-y-5">
                <div>
                    <h2 class="font-display text-2xl font-bold">Choose documents</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Certification options are grouped so the list stays easy to scan.
                    </p>
                </div>
                <div
                    v-for="(types, category) in documentTypeGroups"
                    :key="category"
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
                >
                    <button
                        type="button"
                        class="flex min-h-14 w-full items-center justify-between px-5 text-left font-semibold"
                        :aria-expanded="openCategories.has(category)"
                        @click="toggleCategory(category)"
                    >
                        <span
                            >{{ category }}
                            <span class="ml-1 text-sm font-normal text-slate-500">({{ types.length }})</span></span
                        >
                        <ChevronDownIcon
                            :class="['h-5 w-5 transition-transform', openCategories.has(category) && 'rotate-180']"
                        />
                    </button>
                    <div
                        v-if="openCategories.has(category)"
                        class="grid gap-3 border-t border-slate-100 p-4 md:grid-cols-2"
                    >
                        <article
                            v-for="type in types"
                            :key="type.id"
                            :class="[
                                'rounded-xl border p-4',
                                selected[type.id] ? 'border-brand-500 bg-brand-50' : 'border-slate-200',
                            ]"
                        >
                            <label class="flex cursor-pointer items-start gap-3">
                                <input
                                    type="checkbox"
                                    :checked="Boolean(selected[type.id])"
                                    class="mt-1 rounded border-slate-300 text-brand-700 focus:ring-brand-600"
                                    @change="toggleDocument(type)"
                                />
                                <span>
                                    <span class="block font-semibold">{{ type.name }}</span>
                                    <span class="mt-1 block text-xs leading-5 text-slate-600">{{ feeNote(type) }}</span>
                                    <span class="block text-xs text-slate-500"
                                        >{{ type.sla_days }} working day{{ type.sla_days === 1 ? '' : 's' }} after
                                        official filing</span
                                    >
                                </span>
                            </label>
                            <div v-if="selected[type.id]" class="mt-4 space-y-3 border-t border-brand-200 pt-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Copies</span>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="grid h-11 w-11 place-items-center rounded-lg border border-slate-300 bg-white"
                                            aria-label="Decrease copies"
                                            @click="changeCopies(type.id, -1)"
                                        >
                                            <MinusIcon class="h-4 w-4" />
                                        </button>
                                        <span class="w-6 text-center font-semibold">{{
                                            selected[type.id].copies
                                        }}</span>
                                        <button
                                            type="button"
                                            class="grid h-11 w-11 place-items-center rounded-lg border border-slate-300 bg-white"
                                            aria-label="Increase copies"
                                            @click="changeCopies(type.id, 1)"
                                        >
                                            <PlusIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                                <label class="flex min-h-11 items-center gap-2 text-sm"
                                    ><input
                                        v-model="selected[type.id].authentication_requested"
                                        type="checkbox"
                                        class="rounded text-brand-700"
                                    />
                                    Request authentication</label
                                >
                                <label class="flex min-h-11 items-center gap-2 text-sm"
                                    ><input
                                        v-model="selected[type.id].documentary_stamp_requested"
                                        type="checkbox"
                                        class="rounded text-brand-700"
                                    />
                                    Include documentary stamp</label
                                >
                                <label v-if="type.code === 'cert_enrollment'" class="block text-sm font-medium">
                                    Semester requested
                                    <input
                                        v-model="selected[type.id].semester_requested"
                                        class="mt-1 min-h-11 w-full rounded-lg border-slate-300"
                                        placeholder="e.g. First Semester 2025–2026"
                                    />
                                </label>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section v-else-if="step === 2" class="space-y-6">
                <div>
                    <h2 class="font-display text-2xl font-bold">Personal data</h2>
                    <p class="mt-1 text-sm text-slate-600">Use details that match your school records.</p>
                </div>
                <div class="grid gap-5 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-2">
                    <label class="sm:col-span-2"
                        >Full name<input v-model="form.requester_name" autocomplete="name" class="field"
                    /></label>
                    <label
                        >Email address<input
                            v-model="form.requester_email"
                            type="email"
                            autocomplete="email"
                            class="field"
                    /></label>
                    <label
                        >Contact number<input
                            v-model="form.requester_contact_number"
                            type="tel"
                            autocomplete="tel"
                            class="field"
                    /></label>
                    <label>Student ID (if available)<input v-model="form.requester_student_id" class="field" /></label>
                    <label
                        >Program / course<select v-model="form.academic_program_id" class="field">
                            <option value="" disabled>Select program</option>
                            <option v-for="program in programs" :key="program.id" :value="program.id">
                                {{ program.code }} — {{ program.name }}
                            </option>
                        </select></label
                    >
                    <label
                        >Year level<select v-model="form.requester_year_level" class="field">
                            <option value="" disabled>Select year</option>
                            <option v-for="year in 8" :key="year" :value="year">{{ year }}</option>
                        </select></label
                    >
                    <label
                        >Graduation year / last term attended<input
                            v-model="form.requester_graduation_or_last_sem"
                            class="field"
                    /></label>
                    <label>Birth date<input v-model="form.birth_date" type="date" class="field" /></label>
                    <label>Birth place<input v-model="form.birth_place" class="field" /></label>
                    <label
                        >Sex<select v-model="form.sex" class="field">
                            <option value="" disabled>Select</option>
                            <option>Female</option>
                            <option>Male</option>
                            <option>Prefer not to say</option>
                        </select></label
                    >
                    <label
                        >Civil status<select v-model="form.civil_status" class="field">
                            <option value="" disabled>Select</option>
                            <option>Single</option>
                            <option>Married</option>
                            <option>Widowed</option>
                            <option>Separated</option>
                        </select></label
                    >
                    <label>Citizenship<input v-model="form.citizenship" class="field" /></label>
                    <label class="sm:col-span-2"
                        >Home address<textarea v-model="form.home_address" rows="2" class="field" />
                    </label>
                    <label>Father’s name<input v-model="form.father_name" class="field" /></label>
                    <label>Mother’s maiden name<input v-model="form.mother_maiden_name" class="field" /></label>
                    <label class="sm:col-span-2"
                        >Parents’ address<input v-model="form.parents_address" class="field"
                    /></label>
                    <label>Guardian<input v-model="form.guardian_name" class="field" /></label>
                    <label>Guardian’s address<input v-model="form.guardian_address" class="field" /></label>
                </div>
            </section>

            <section v-else-if="step === 3" class="space-y-6">
                <div>
                    <h2 class="font-display text-2xl font-bold">Education and release details</h2>
                    <p class="mt-1 text-sm text-slate-600">This mirrors the registrar’s records request form.</p>
                </div>
                <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5">
                    <fieldset
                        v-for="(label, key) in {
                            elementary: 'Elementary',
                            junior_high: 'Junior high school',
                            senior_high: 'Senior high school',
                        }"
                        :key="key"
                        class="grid gap-3 border-b border-slate-100 pb-4 sm:grid-cols-[1fr_1fr_8rem]"
                    >
                        <legend class="mb-2 font-semibold sm:col-span-3">{{ label }}</legend>
                        <label>School<input v-model="form.education[key].school" class="field" /></label>
                        <label>Address<input v-model="form.education[key].address" class="field" /></label>
                        <label
                            >Year<input v-model="form.education[key].year" inputmode="numeric" class="field"
                        /></label>
                    </fieldset>
                    <label
                        >Employment status<select v-model="form.employment_status" class="field">
                            <option value="employed">Employed</option>
                            <option value="not_employed">Not employed</option>
                            <option value="self_employed">Self-employed</option>
                        </select></label
                    >
                    <div v-if="form.employment_status !== 'not_employed'" class="grid gap-4 sm:grid-cols-2">
                        <label>Company / business name<input v-model="form.company_name" class="field" /></label>
                        <label>Company / business address<input v-model="form.company_address" class="field" /></label>
                    </div>
                    <label
                        >Purpose<select v-model="form.purpose" class="field">
                            <option value="" disabled>Select purpose</option>
                            <option>Employment</option>
                            <option>Board examination</option>
                            <option>Further studies</option>
                            <option>Transfer</option>
                            <option>Passport or visa application</option>
                            <option>Record evaluation</option>
                            <option>Personal copy</option>
                            <option>Other official purpose</option>
                        </select></label
                    >
                    <fieldset>
                        <legend class="font-semibold">How should the documents be released?</legend>
                        <div class="mt-2 flex flex-wrap gap-4">
                            <label class="flex min-h-11 items-center gap-2"
                                ><input v-model="form.fulfillment_method" type="radio" value="pickup" /> Pickup</label
                            ><label class="flex min-h-11 items-center gap-2"
                                ><input v-model="form.fulfillment_method" type="radio" value="delivery" />
                                Delivery</label
                            >
                        </div>
                    </fieldset>
                    <label v-if="form.fulfillment_method === 'delivery'"
                        >Delivery address<textarea v-model="form.delivery_address" rows="2" class="field" />
                    </label>
                    <label class="flex min-h-11 items-center gap-2"
                        ><input v-model="form.is_proxy_request" type="checkbox" class="rounded text-brand-700" /> A
                        representative will process or claim this request</label
                    >
                </div>
            </section>

            <section v-else-if="step === 4" class="space-y-6">
                <div>
                    <h2 class="font-display text-2xl font-bold">Upload requirements</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Files remain private and are available only to authorized staff.
                    </p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <FileUploadField
                        v-for="requirement in requirementList"
                        :id="`requirement-${requirement.key}`"
                        :key="requirement.key"
                        :label="requirement.label"
                        :hint="requirement.hint"
                        :error="form.errors[`requirements.${requirement.key}`]"
                        required
                        @change="form.requirements[requirement.key] = $event"
                    />
                </div>
            </section>

            <section v-else class="space-y-6">
                <div>
                    <h2 class="font-display text-2xl font-bold">Review and submit</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        No payment is due yet. The registrar will evaluate and lock your quote first.
                    </p>
                </div>
                <div class="grid gap-5 md:grid-cols-2">
                    <article class="rounded-2xl border border-slate-200 bg-white p-5">
                        <h3 class="font-semibold">Requestor</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            {{ form.requester_name }}<br />{{ form.requester_email }}<br />{{ selectedProgram?.code }} —
                            {{ selectedProgram?.name }}
                        </p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-5">
                        <h3 class="font-semibold">Documents</h3>
                        <ul class="mt-2 space-y-2 text-sm text-slate-600">
                            <li v-for="item in cart" :key="item.type.id">{{ item.type.name }} × {{ item.copies }}</li>
                        </ul>
                    </article>
                </div>
                <div class="rounded-2xl border border-brand-200 bg-brand-50 p-5 text-sm leading-6 text-brand-950">
                    <strong>What happens next:</strong> registrar review → sequential clearance → payment receipt →
                    accounting validation → processing → ready for release. Working days exclude weekends, holidays, and
                    official work suspensions.
                </div>
            </section>

            <div class="mt-8 flex items-center justify-between border-t border-slate-200 pt-6">
                <button
                    v-if="step > 1"
                    type="button"
                    class="min-h-11 rounded-lg border border-slate-300 bg-white px-5 font-semibold"
                    @click="step--"
                >
                    Back</button
                ><span v-else />
                <button
                    v-if="step < steps.length"
                    type="button"
                    :disabled="!canContinue()"
                    class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-brand-700 px-5 font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40"
                    @click="next"
                >
                    Continue <ArrowRightIcon class="h-4 w-4" />
                </button>
                <button
                    v-else
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-brand-700 px-5 font-semibold text-white disabled:opacity-50"
                >
                    <CheckCircleIcon class="h-5 w-5" />{{ form.processing ? 'Submitting…' : 'Submit request' }}
                </button>
            </div>
        </form>
    </main>
</template>

<style scoped>
label {
    @apply text-sm font-medium text-slate-700;
}
.field {
    @apply mt-1 min-h-11 w-full rounded-lg border-slate-300 bg-white text-base shadow-sm focus:border-brand-600 focus:ring-brand-600;
}
</style>
