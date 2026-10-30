<script setup>
import DocumentRate from '@/Components/DocumentRate.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRightIcon, CheckIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
    announcements: { type: Array, default: () => [] },
    faqs: { type: Array, default: () => [] },
    commonDocuments: { type: Array, default: () => [] },
    paymentInstructions: { type: String, default: null },
    paymentProfile: { type: Object, default: null },
});

const schoolName = 'St. Vincent’s College Incorporated';
const page = usePage();
const logo = computed(() => page.props.branding?.logo || '/images/svci-seal.jpg');

const steps = [
    {
        title: 'Submit online',
        description:
            'Choose your documents, enter details that match your school records, and upload any required files.',
    },
    {
        title: 'Registrar review',
        description:
            'The Registrar checks your request and confirms the final amount. Nothing is paid before this step.',
    },
    {
        title: 'Clearance',
        description: 'The offices your request needs sign off on your clearance.',
    },
    {
        title: 'Payment',
        description: 'Pay using the official instructions, then upload your receipt for Accounting to validate.',
    },
    {
        title: 'Release',
        description:
            'Claim your documents at the Registrar’s releasing window with your claim slip, or receive them by delivery.',
    },
];

const checklist = [
    'Your name, program or grade level, and years attended, exactly as they appear in your school records.',
    'A working email address and contact number, so the Registrar can reach you.',
    'Supporting files for the documents you choose. The form lists what each one needs.',
    'Your representative’s details, if someone else will claim the documents for you.',
];

// The tracking page only pre-fills uppercase REQ-YYYY-NNNNNN references.
const normalizeReference = (event) => {
    const field = event.target.elements.reference_no;
    field.value = field.value.trim().toUpperCase();
};

const focusRing = 'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';
const delay = (index, step = 70, base = 0) => ({ '--delay': `${base + index * step}ms` });

const scrolled = ref(false);
let revealObserver;

const onScroll = () => {
    scrolled.value = window.scrollY > 8;
};

// Content is hidden for reveal only after this runs, so it stays visible without JS or with reduced motion.
onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    const root = document.documentElement;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reducedMotion || !('IntersectionObserver' in window)) {
        return;
    }

    revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    revealObserver.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.15 },
    );

    document.querySelectorAll('[data-reveal]').forEach((element) => revealObserver.observe(element));
    root.classList.add('reveal-ready');
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    revealObserver?.disconnect();
    document.documentElement.classList.remove('reveal-ready');
});
</script>

<template>
    <Head title="Office of the Registrar" />

    <div class="min-h-[100dvh] bg-stone-50 font-sans text-slate-900 selection:bg-school-700 selection:text-white">
        <a
            href="#main-content"
            class="sr-only left-4 top-4 z-50 rounded-md bg-school-700 px-4 py-3 text-sm font-bold text-white focus:not-sr-only focus:absolute focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
        >
            Skip to main content
        </a>

        <header
            class="sticky top-0 z-30 bg-school-900 text-white transition-shadow duration-300"
            :class="scrolled ? 'shadow-[0_1px_0_rgba(255,255,255,0.08),0_8px_24px_rgba(5,20,55,0.35)]' : ''"
        >
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <Link
                    href="/"
                    class="group flex min-w-0 items-center gap-3 rounded-md focus-visible:ring-white focus-visible:ring-offset-school-900"
                    :class="focusRing"
                >
                    <img
                        :src="logo"
                        alt=""
                        width="44"
                        height="44"
                        class="h-11 w-11 shrink-0 rounded-full bg-white object-contain p-0.5 transition-transform duration-500 ease-out group-hover:rotate-[-8deg] group-hover:scale-105 motion-reduce:transform-none"
                    />
                    <span class="min-w-0 leading-tight">
                        <span class="block text-sm font-bold sm:text-base">{{ schoolName }}</span>
                        <span class="block text-xs text-school-200"
                            >Office of the Registrar<span class="hidden sm:inline"> · Dipolog City</span></span
                        >
                    </span>
                </Link>

                <nav v-if="canLogin" class="flex shrink-0 items-center gap-1 text-sm" aria-label="Public navigation">
                    <Link
                        :href="route('public.pricelist')"
                        class="nav-link hidden min-h-11 items-center rounded-md px-3 font-semibold text-school-100 transition-colors hover:text-white focus-visible:ring-white focus-visible:ring-offset-school-900 sm:inline-flex"
                        :class="focusRing"
                    >
                        Price list
                    </Link>
                    <Link
                        :href="route('track-document')"
                        class="nav-link hidden min-h-11 items-center rounded-md px-3 font-semibold text-school-100 transition-colors hover:text-white focus-visible:ring-white focus-visible:ring-offset-school-900 md:inline-flex"
                        :class="focusRing"
                    >
                        Track a request
                    </Link>
                    <Link
                        :href="$page.props.auth.user ? route('dashboard') : route('login')"
                        class="inline-flex min-h-11 items-center rounded-md border border-white/30 px-3 font-semibold transition-colors duration-200 hover:border-white/60 hover:bg-white/10 active:bg-white/20 focus-visible:ring-white focus-visible:ring-offset-school-900"
                        :class="focusRing"
                    >
                        {{ $page.props.auth.user ? 'Dashboard' : 'Staff login' }}
                    </Link>
                </nav>
            </div>
        </header>

        <main id="main-content">
            <section class="relative isolate overflow-hidden bg-school-800 text-white" aria-labelledby="hero-heading">
                <img
                    src="/images/svci-seal.jpg"
                    alt=""
                    aria-hidden="true"
                    width="316"
                    height="316"
                    class="hero-seal pointer-events-none absolute -right-24 -top-16 -z-10 w-[26rem] max-w-none sm:-right-16 sm:w-[34rem] lg:right-[-6rem] lg:top-[-5rem] lg:w-[40rem]"
                />
                <div
                    class="mx-auto grid max-w-6xl gap-10 px-4 pb-14 pt-12 sm:px-6 sm:pb-16 sm:pt-16 lg:grid-cols-12 lg:items-end lg:gap-12 lg:px-8 lg:pb-20 lg:pt-20"
                >
                    <div class="lg:col-span-7">
                        <p class="enter text-sm font-semibold text-school-200" :style="delay(0)">
                            Online document requests
                        </p>
                        <h1
                            id="hero-heading"
                            class="enter mt-3 max-w-[18ch] font-serif text-4xl font-semibold leading-tight sm:text-5xl lg:text-[3.5rem]"
                            :style="delay(1)"
                        >
                            Request your school records online.
                        </h1>
                        <p
                            class="enter mt-5 max-w-xl text-base leading-7 text-school-100 sm:text-lg sm:leading-8"
                            :style="delay(2)"
                        >
                            Transcripts, diplomas, certifications, and Form 137 for College, Graduate School, and Basic
                            Education. No account needed, and you pay only after the Registrar reviews your request.
                        </p>

                        <div class="enter mt-8 flex flex-col gap-3 sm:flex-row" :style="delay(3)">
                            <Link
                                :href="$page.props.auth.user ? route('dashboard') : route('public.requests.create')"
                                class="group inline-flex min-h-12 items-center justify-center gap-2 rounded-md bg-white px-5 py-3 font-bold text-school-900 shadow-sm transition duration-200 hover:-translate-y-px hover:bg-school-50 hover:shadow-md active:translate-y-0 active:scale-[0.98] focus-visible:ring-white focus-visible:ring-offset-school-800 motion-reduce:transform-none"
                                :class="focusRing"
                            >
                                {{ $page.props.auth.user ? 'Open dashboard' : 'Request a document' }}
                                <ArrowRightIcon
                                    class="h-5 w-5 transition-transform duration-200 group-hover:translate-x-1 motion-reduce:transform-none"
                                    aria-hidden="true"
                                />
                            </Link>
                            <Link
                                :href="route('public.pricelist')"
                                class="inline-flex min-h-12 items-center justify-center rounded-md border border-white/40 px-5 py-3 font-bold text-white transition duration-200 hover:border-white/70 hover:bg-white/10 active:scale-[0.98] focus-visible:ring-white focus-visible:ring-offset-school-800 motion-reduce:transform-none"
                                :class="focusRing"
                            >
                                View price list
                            </Link>
                        </div>
                    </div>

                    <form
                        :action="route('track-document')"
                        method="get"
                        class="enter rounded-md bg-white p-5 text-slate-900 shadow-[0_24px_60px_rgba(5,18,50,0.35)] sm:p-6 lg:col-span-5"
                        :style="delay(4, 70, 80)"
                        aria-labelledby="track-heading"
                        @submit="normalizeReference"
                    >
                        <h2 id="track-heading" class="text-lg font-bold">Already sent a request?</h2>
                        <label for="landing-reference" class="mt-1 block text-sm text-slate-600">
                            Enter the reference number from your confirmation.
                        </label>
                        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                            <input
                                id="landing-reference"
                                name="reference_no"
                                type="text"
                                inputmode="text"
                                autocomplete="off"
                                maxlength="20"
                                placeholder="REQ-2026-000123"
                                class="min-h-12 w-full rounded-md border-slate-300 font-mono text-base uppercase transition-shadow duration-200 placeholder:normal-case placeholder:text-slate-400 focus:border-school-700 focus:ring-school-700"
                            />
                            <button
                                type="submit"
                                class="inline-flex min-h-12 shrink-0 items-center justify-center rounded-md bg-school-700 px-5 font-bold text-white transition duration-200 hover:bg-school-800 active:scale-[0.97] focus-visible:ring-school-700 motion-reduce:transform-none"
                                :class="focusRing"
                            >
                                Track
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <section
                v-if="commonDocuments.length"
                class="border-b border-stone-200 bg-white py-14 sm:py-20"
                aria-labelledby="documents-heading"
            >
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div data-reveal class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 id="documents-heading" class="font-serif text-3xl font-semibold text-slate-950">
                                Common documents
                            </h2>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                Reference rates. The Registrar confirms the final amount after checking page counts,
                                authentication, and documentary stamps.
                            </p>
                        </div>
                        <Link
                            :href="route('public.pricelist')"
                            class="group inline-flex min-h-11 shrink-0 items-center gap-1 rounded-md font-bold text-school-700 underline decoration-school-200 decoration-2 underline-offset-4 transition-colors hover:text-school-900 hover:decoration-school-700 focus-visible:ring-school-700"
                            :class="focusRing"
                        >
                            See all documents and fees
                            <ArrowRightIcon
                                class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-1 motion-reduce:transform-none"
                                aria-hidden="true"
                            />
                        </Link>
                    </div>

                    <table class="mt-8 w-full text-left text-sm">
                        <thead
                            class="hidden border-b border-slate-300 text-xs uppercase text-slate-500 sm:table-header-group"
                        >
                            <tr>
                                <th scope="col" class="py-3 pl-3 pr-4 font-semibold">Document</th>
                                <th scope="col" class="py-3 pr-4 font-semibold">Fee</th>
                                <th scope="col" class="py-3 pr-3 font-semibold sm:text-right">Ready in</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 border-y border-slate-200 sm:border-t-0">
                            <tr
                                v-for="(document, index) in commonDocuments"
                                :key="document.code"
                                data-reveal
                                :style="delay(index, 50)"
                                class="grid grid-cols-2 gap-x-4 gap-y-1 py-4 transition-colors duration-200 hover:bg-school-50/70 sm:table-row sm:py-0"
                            >
                                <th scope="row" class="col-span-2 font-semibold text-slate-900 sm:py-4 sm:pl-3 sm:pr-4">
                                    {{ document.name }}
                                </th>
                                <td class="text-slate-900 sm:py-4 sm:pr-4">
                                    <DocumentRate :fee="document.fee" :formula="document.fee_formula" />
                                </td>
                                <td class="text-right text-slate-600 sm:py-4 sm:pr-3">
                                    {{ document.processing_days }} working
                                    {{ Number(document.processing_days) === 1 ? 'day' : 'days' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="py-14 sm:py-20" aria-labelledby="steps-heading">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div data-reveal>
                        <h2 id="steps-heading" class="font-serif text-3xl font-semibold text-slate-950">
                            How a request works
                        </h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                            Keep your reference number. It shows which step your request is on.
                        </p>
                    </div>

                    <ol class="mt-10 grid gap-8 md:grid-cols-5 md:gap-6">
                        <li
                            v-for="(step, index) in steps"
                            :key="step.title"
                            data-reveal
                            :style="delay(index, 140)"
                            class="relative flex gap-4 border-t-2 border-stone-300 pt-4 md:block"
                        >
                            <span
                                class="step-bar absolute -top-0.5 left-0 h-0.5 w-full bg-school-700"
                                aria-hidden="true"
                            ></span>
                            <span class="w-6 shrink-0 font-serif text-2xl font-semibold leading-none text-school-700">{{
                                index + 1
                            }}</span>
                            <div>
                                <h3 class="font-bold text-slate-950 md:mt-2">{{ step.title }}</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ step.description }}</p>
                            </div>
                        </li>
                    </ol>
                </div>
            </section>

            <section class="border-t border-stone-200 bg-white py-14 sm:py-20" aria-labelledby="prepare-heading">
                <div class="mx-auto grid max-w-6xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                    <div>
                        <div data-reveal>
                            <h2 id="prepare-heading" class="font-serif text-3xl font-semibold text-slate-950">
                                Before you start
                            </h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Having these ready helps the Registrar review your request without follow-up.
                            </p>
                        </div>
                        <ul class="mt-6 space-y-4">
                            <li
                                v-for="(item, index) in checklist"
                                :key="item"
                                data-reveal
                                :style="delay(index, 90)"
                                class="flex gap-3 text-sm leading-6"
                            >
                                <span
                                    class="check-mark flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-school-50 text-school-700"
                                >
                                    <CheckIcon class="h-4 w-4" aria-hidden="true" />
                                </span>
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </div>

                    <div v-if="announcements.length" id="announcements" data-reveal :style="delay(1, 120)">
                        <h2 class="font-serif text-3xl font-semibold text-slate-950">Announcements</h2>
                        <div class="mt-6 divide-y divide-stone-200 border-y border-stone-200">
                            <article v-for="announcement in announcements" :key="announcement.id" class="py-5">
                                <h3 class="font-bold text-slate-950">{{ announcement.title }}</h3>
                                <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-600">
                                    {{ announcement.body }}
                                </p>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="faqs.length || paymentProfile || paymentInstructions"
                class="border-t border-stone-200 py-14 sm:py-20"
                aria-label="Payment details and questions"
            >
                <div class="mx-auto grid max-w-6xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                    <div v-if="faqs.length" id="faqs" data-reveal>
                        <h2 class="font-serif text-3xl font-semibold text-slate-950">Common questions</h2>
                        <div class="mt-6 divide-y divide-stone-200 border-y border-stone-200">
                            <details v-for="faq in faqs" :key="faq.id" class="group py-4">
                                <summary
                                    class="flex cursor-pointer list-none items-center justify-between gap-4 rounded-md font-semibold text-slate-900 transition-colors hover:text-school-700 focus-visible:ring-school-700 [&::-webkit-details-marker]:hidden"
                                    :class="focusRing"
                                >
                                    {{ faq.question }}
                                    <ChevronDownIcon
                                        class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-300 group-open:rotate-180 motion-reduce:transition-none"
                                        aria-hidden="true"
                                    />
                                </summary>
                                <p class="faq-answer mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">
                                    {{ faq.answer }}
                                </p>
                            </details>
                        </div>
                    </div>

                    <div
                        v-if="paymentProfile || paymentInstructions"
                        id="payment"
                        data-reveal
                        :style="delay(faqs.length ? 1 : 0, 120)"
                    >
                        <h2 class="font-serif text-3xl font-semibold text-slate-950">Payment details</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Pay only after the Registrar confirms your amount and reference.
                        </p>
                        <div class="mt-6 rounded-md border border-stone-300 bg-white p-5 text-sm leading-6">
                            <dl v-if="paymentProfile" class="grid gap-x-4 gap-y-2 sm:grid-cols-[auto_1fr]">
                                <dt class="text-slate-500">Bank</dt>
                                <dd class="font-semibold">{{ paymentProfile.bank_name }}</dd>
                                <dt class="text-slate-500">Account name</dt>
                                <dd class="font-semibold">{{ paymentProfile.account_name }}</dd>
                                <dt class="text-slate-500">Account number</dt>
                                <dd class="font-mono font-semibold">{{ paymentProfile.account_number }}</dd>
                            </dl>
                            <p
                                v-if="paymentProfile?.instructions || paymentInstructions"
                                class="whitespace-pre-line text-slate-700"
                                :class="paymentProfile ? 'mt-4 border-t border-stone-200 pt-4' : ''"
                            >
                                {{ paymentProfile?.instructions || paymentInstructions }}
                            </p>
                            <img
                                v-if="paymentProfile?.qr_url"
                                :src="paymentProfile.qr_url"
                                alt="Official payment QR code"
                                width="176"
                                height="176"
                                loading="lazy"
                                class="mt-4 h-44 w-44 rounded-md border border-stone-200 bg-white object-contain p-2"
                            />
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="bg-school-900 text-sm text-school-100">
            <div
                class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-10 sm:flex-row sm:items-start sm:justify-between sm:px-6 lg:px-8"
            >
                <div class="flex items-start gap-3">
                    <img
                        :src="logo"
                        alt=""
                        width="40"
                        height="40"
                        class="h-10 w-10 shrink-0 rounded-full bg-white object-contain p-0.5"
                    />
                    <address class="not-italic leading-6">
                        <p class="font-semibold text-white">{{ schoolName }} · Office of the Registrar</p>
                        <p>Padre Ramon Street, Estaka, Dipolog City 7100</p>
                        <p>
                            <a
                                href="mailto:registrarsoffice@svc.edu.ph"
                                class="nav-link rounded-md transition-colors hover:text-white focus-visible:ring-white focus-visible:ring-offset-school-900"
                                :class="focusRing"
                                >registrarsoffice@svc.edu.ph</a
                            >
                            ·
                            <a
                                href="tel:+639515388282"
                                class="nav-link rounded-md transition-colors hover:text-white focus-visible:ring-white focus-visible:ring-offset-school-900"
                                :class="focusRing"
                                >0951 538 8282</a
                            >
                        </p>
                        <p class="mt-2 text-xs text-school-200">&copy; {{ new Date().getFullYear() }}</p>
                    </address>
                </div>
                <nav class="flex flex-wrap gap-x-5 gap-y-2" aria-label="Footer">
                    <Link
                        :href="route('public.requests.create')"
                        class="nav-link rounded-md py-1 transition-colors hover:text-white focus-visible:ring-white focus-visible:ring-offset-school-900"
                        :class="focusRing"
                    >
                        Request a document
                    </Link>
                    <Link
                        :href="route('track-document')"
                        class="nav-link rounded-md py-1 transition-colors hover:text-white focus-visible:ring-white focus-visible:ring-offset-school-900"
                        :class="focusRing"
                    >
                        Track a request
                    </Link>
                    <Link
                        :href="route('public.pricelist')"
                        class="nav-link rounded-md py-1 transition-colors hover:text-white focus-visible:ring-white focus-visible:ring-offset-school-900"
                        :class="focusRing"
                    >
                        Price list
                    </Link>
                </nav>
            </div>
        </footer>
    </div>
</template>
