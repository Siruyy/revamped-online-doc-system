<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowDownRightIcon,
    ArrowRightIcon,
    ArrowUpRightIcon,
    BanknotesIcon,
    BuildingLibraryIcon,
    CheckCircleIcon,
    DocumentArrowUpIcon,
    DocumentCheckIcon,
    DocumentTextIcon,
    IdentificationIcon,
    ShieldCheckIcon,
    TicketIcon,
} from '@heroicons/vue/24/outline';
import { onBeforeUnmount, onMounted } from 'vue';

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
    paymentInstructions: { type: String, default: null },
});

const serviceFacts = [
    {
        title: 'No account required',
        description: 'Begin with your requestor details.',
        icon: IdentificationIcon,
    },
    {
        title: 'Reference-number tracking',
        description: 'Check progress whenever you need to.',
        icon: TicketIcon,
    },
    {
        title: 'Payment after review',
        description: 'Pay only when instructions are ready.',
        icon: BanknotesIcon,
    },
];

const processSteps = [
    {
        title: 'Send your request',
        description: 'Choose the documents, add your details, and select how you would like them released.',
        icon: DocumentTextIcon,
        cardClass: 'bg-brand-700 text-white lg:col-span-7',
        tabClass: 'bg-brand-700',
        iconClass: 'border-white/20 bg-white/10 text-white',
        bodyClass: 'text-brand-100',
    },
    {
        title: 'Receive a Registrar review',
        description: 'The Registrar confirms the request, fees, and any supporting requirements before payment.',
        icon: DocumentCheckIcon,
        cardClass: 'border border-slate-200 bg-white text-slate-950 lg:col-span-5 lg:translate-y-8',
        tabClass: 'border border-b-0 border-slate-200 bg-white',
        iconClass: 'border-brand-200 bg-brand-50 text-brand-700',
        bodyClass: 'text-slate-600',
    },
    {
        title: 'Complete clearance and payment',
        description: 'The right offices complete clearance first. Then you can upload the requested payment receipt.',
        icon: DocumentArrowUpIcon,
        cardClass: 'bg-brand-100 text-slate-950 lg:col-span-5',
        tabClass: 'bg-brand-100',
        iconClass: 'border-brand-200 bg-white/70 text-brand-800',
        bodyClass: 'text-slate-700',
    },
    {
        title: 'Track and claim',
        description: 'Use your reference number to see each update and know when your documents are ready.',
        icon: ShieldCheckIcon,
        cardClass: 'bg-slate-900 text-white lg:col-span-7 lg:translate-y-8',
        tabClass: 'bg-slate-900',
        iconClass: 'border-white/15 bg-white/10 text-brand-200',
        bodyClass: 'text-slate-300',
    },
];

let revealObserver;

onMounted(() => {
    const revealItems = [...document.querySelectorAll('[data-landing-reveal]')];
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        revealItems.forEach((item) => item.classList.add('is-visible'));
        return;
    }

    revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            });
        },
        {
            threshold: 0.16,
            rootMargin: '0px 0px -48px',
        },
    );

    revealItems.forEach((item) => revealObserver.observe(item));
});

onBeforeUnmount(() => revealObserver?.disconnect());
</script>

<template>
    <Head title="SVCI Online Document System" />

    <div
        class="min-h-[100dvh] overflow-x-clip bg-slate-50 font-sans text-slate-900 selection:bg-brand-700 selection:text-white"
    >
        <a
            href="#main-content"
            class="sr-only left-4 top-4 z-50 rounded-xl bg-brand-700 px-4 py-3 text-sm font-bold text-white focus:not-sr-only focus:absolute focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
        >
            Skip to main content
        </a>

        <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-slate-50/90 backdrop-blur-xl">
            <div class="mx-auto flex h-[72px] max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link
                    href="/"
                    class="group flex items-center gap-3 rounded-xl active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2"
                >
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-700 text-white shadow-[0_8px_22px_rgba(29,78,216,0.2)] transition duration-200 group-hover:-translate-y-0.5 group-hover:bg-brand-600 motion-reduce:group-hover:translate-y-0"
                    >
                        <BuildingLibraryIcon class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <span>
                        <span class="block font-display text-lg font-bold tracking-tight text-slate-950"
                            >SVCI Docs</span
                        >
                        <span
                            class="hidden text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500 sm:block"
                        >
                            Document services
                        </span>
                    </span>
                </Link>

                <nav v-if="canLogin" class="flex items-center gap-3" aria-label="Public navigation">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-4 py-2 text-sm font-bold text-white transition duration-200 hover:bg-brand-600 active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl px-3 py-2 text-sm font-bold text-slate-700 transition duration-200 hover:bg-slate-200/70 hover:text-brand-800 active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2"
                    >
                        Staff login
                    </Link>
                </nav>
            </div>
        </header>

        <main id="main-content">
            <section class="landing-hero relative isolate overflow-hidden bg-[#f7f9fc]">
                <div class="landing-hero-orbit" aria-hidden="true"></div>
                <div
                    class="mx-auto grid min-h-[calc(100dvh-72px)] max-w-7xl gap-10 px-4 pb-20 pt-12 sm:px-6 sm:pb-24 sm:pt-16 lg:grid-cols-12 lg:items-center lg:gap-8 lg:px-8 lg:pb-20 lg:pt-20"
                >
                    <div class="landing-hero-copy relative z-10 lg:col-span-5">
                        <p class="text-sm font-bold text-brand-800">St. Vincent College Incorporated</p>
                        <h1
                            class="mt-5 max-w-[12ch] font-display text-5xl font-bold leading-[1.02] tracking-[-0.045em] text-slate-950 sm:text-6xl lg:text-[4.15rem]"
                        >
                            <span class="block">Official records.</span>
                            <span class="block text-brand-700">Clearly on their way.</span>
                        </h1>
                        <p class="mt-6 max-w-lg text-lg leading-8 text-slate-600">
                            Request official school documents online and follow every review step through payment,
                            processing, and release.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <Link
                                v-if="!$page.props.auth.user"
                                :href="route('public.requests.create')"
                                class="group inline-flex min-h-12 items-center justify-center gap-3 rounded-xl bg-brand-700 px-5 py-3 text-base font-bold text-white shadow-[0_12px_30px_rgba(29,78,216,0.22)] transition duration-200 hover:-translate-y-0.5 hover:bg-brand-600 active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 motion-reduce:hover:translate-y-0"
                            >
                                Request document
                                <ArrowRightIcon
                                    class="h-5 w-5 transition-transform duration-200 group-hover:translate-x-0.5 motion-reduce:group-hover:translate-x-0"
                                    aria-hidden="true"
                                />
                            </Link>
                            <Link
                                v-else
                                :href="route('dashboard')"
                                class="group inline-flex min-h-12 items-center justify-center gap-3 rounded-xl bg-brand-700 px-5 py-3 text-base font-bold text-white shadow-[0_12px_30px_rgba(29,78,216,0.22)] transition duration-200 hover:-translate-y-0.5 hover:bg-brand-600 active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 motion-reduce:hover:translate-y-0"
                            >
                                Open dashboard
                                <ArrowRightIcon
                                    class="h-5 w-5 transition-transform duration-200 group-hover:translate-x-0.5 motion-reduce:group-hover:translate-x-0"
                                    aria-hidden="true"
                                />
                            </Link>
                            <Link
                                :href="route('track-document')"
                                class="group inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-base font-bold text-slate-800 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-brand-300 hover:text-brand-800 active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 motion-reduce:hover:translate-y-0"
                            >
                                Track request
                                <ArrowUpRightIcon
                                    class="h-4 w-4 transition-transform duration-200 group-hover:-translate-y-0.5 group-hover:translate-x-0.5 motion-reduce:group-hover:translate-x-0 motion-reduce:group-hover:translate-y-0"
                                    aria-hidden="true"
                                />
                            </Link>
                        </div>
                    </div>

                    <div class="landing-hero-media relative lg:col-span-7 lg:pl-8">
                        <div
                            class="absolute -bottom-7 left-2 h-36 w-40 rounded-[24px] bg-brand-700 sm:h-44 sm:w-52 lg:-left-1 lg:bottom-8"
                            aria-hidden="true"
                        ></div>
                        <div
                            class="absolute -right-8 -top-8 h-28 w-44 rounded-[24px] border border-brand-200 bg-brand-100 sm:h-36 sm:w-56 lg:-right-10 lg:top-10"
                            aria-hidden="true"
                        ></div>
                        <div
                            class="relative aspect-[4/3] overflow-hidden rounded-[24px] bg-slate-200 shadow-[0_28px_70px_rgba(15,23,42,0.2)] ring-1 ring-slate-900/10"
                        >
                            <img
                                class="h-full w-full object-cover object-[54%_center] transition duration-700 hover:scale-[1.025] motion-reduce:hover:scale-100"
                                src="/images/landing/registrar-service.png"
                                alt="A student submitting a document folder at a school registrar service counter"
                                width="1448"
                                height="1086"
                                fetchpriority="high"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section class="relative z-10 -mt-10 px-4 sm:px-6 lg:px-8" aria-label="Public request highlights">
                <dl
                    class="mx-auto grid max-w-6xl overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.11)] md:grid-cols-3"
                >
                    <div
                        v-for="(fact, index) in serviceFacts"
                        :key="fact.title"
                        class="flex gap-4 px-6 py-6 md:px-7"
                        :class="index > 0 ? 'border-t border-slate-200 md:border-l md:border-t-0' : ''"
                    >
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"
                        >
                            <component :is="fact.icon" class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div>
                            <dt class="font-display text-base font-bold text-slate-950">{{ fact.title }}</dt>
                            <dd class="mt-1 text-sm leading-5 text-slate-600">{{ fact.description }}</dd>
                        </div>
                    </div>
                </dl>
            </section>

            <section class="bg-brand-50 pb-28 pt-28 sm:pb-32 sm:pt-32">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div data-landing-reveal class="landing-reveal max-w-2xl">
                        <h2
                            class="font-display text-4xl font-bold leading-tight tracking-[-0.035em] text-slate-950 sm:text-5xl"
                        >
                            One request. A visible path.
                        </h2>
                        <p class="mt-5 max-w-xl text-lg leading-8 text-slate-600">
                            Every office sees the work it needs to complete, while you keep one reference number from
                            submission to release.
                        </p>
                    </div>

                    <ol class="mt-16 grid gap-x-6 gap-y-10 lg:grid-cols-12" aria-label="Document request process">
                        <li
                            v-for="(step, index) in processSteps"
                            :key="step.title"
                            data-landing-reveal
                            class="landing-process-card landing-reveal relative flex min-h-[17rem] flex-col overflow-visible rounded-[24px] p-7 shadow-[0_18px_44px_rgba(15,23,42,0.09)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_24px_54px_rgba(15,23,42,0.14)] motion-reduce:hover:translate-y-0 sm:p-8"
                            :class="step.cardClass"
                            :style="{ '--landing-reveal-delay': `${index * 70}ms` }"
                        >
                            <span
                                class="absolute left-7 top-0 h-3 w-24 -translate-y-full rounded-t-xl"
                                :class="step.tabClass"
                                aria-hidden="true"
                            ></span>
                            <div class="flex items-start justify-between gap-6">
                                <span
                                    class="flex h-12 w-12 items-center justify-center rounded-xl border"
                                    :class="step.iconClass"
                                >
                                    <component :is="step.icon" class="h-6 w-6" aria-hidden="true" />
                                </span>
                                <ArrowDownRightIcon
                                    v-if="index < processSteps.length - 1"
                                    class="h-6 w-6 opacity-50"
                                    aria-hidden="true"
                                />
                            </div>
                            <div class="mt-auto pt-10">
                                <h3 class="font-display text-2xl font-bold tracking-tight">{{ step.title }}</h3>
                                <p class="mt-3 max-w-xl text-sm leading-6" :class="step.bodyClass">
                                    {{ step.description }}
                                </p>
                            </div>
                        </li>
                    </ol>
                </div>
            </section>

            <section class="bg-white py-24 sm:py-28 lg:py-32">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div data-landing-reveal class="landing-reveal relative lg:pb-24">
                        <div
                            class="aspect-[4/3] overflow-hidden rounded-[24px] bg-slate-200 shadow-[0_24px_64px_rgba(15,23,42,0.13)] sm:aspect-[16/9] lg:aspect-[16/8]"
                        >
                            <img
                                class="h-full w-full object-cover object-center transition duration-700 hover:scale-[1.025] motion-reduce:hover:scale-100"
                                src="/images/landing/study-materials.jpg"
                                alt="Open books and study materials arranged on a table"
                                width="1100"
                                height="733"
                                loading="lazy"
                            />
                        </div>

                        <div
                            class="relative mx-4 -mt-12 rounded-[24px] border border-slate-200 bg-white p-7 shadow-[0_22px_58px_rgba(15,23,42,0.14)] sm:mx-8 sm:p-10 lg:absolute lg:bottom-0 lg:right-8 lg:mx-0 lg:mt-0 lg:w-[58%] lg:p-12"
                        >
                            <h2
                                class="max-w-xl font-display text-3xl font-bold leading-tight tracking-[-0.03em] text-slate-950 sm:text-4xl"
                            >
                                A better review starts with the right details.
                            </h2>
                            <p class="mt-5 max-w-xl text-base leading-7 text-slate-600">
                                Prepare the information and files that help the Registrar confirm your request without
                                unnecessary follow-up.
                            </p>

                            <div class="mt-8 grid gap-6 sm:grid-cols-2">
                                <div class="border-t-2 border-brand-600 pt-4">
                                    <h3 class="font-display text-lg font-bold text-slate-950">Document details</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        Select the document, copies, purpose, and preferred release method.
                                    </p>
                                </div>
                                <div class="border-t-2 border-brand-200 pt-4">
                                    <h3 class="font-display text-lg font-bold text-slate-950">
                                        Supporting requirements
                                    </h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        Have identification and any supporting files ready for upload when requested.
                                    </p>
                                </div>
                            </div>

                            <p
                                class="mt-7 flex gap-3 rounded-xl bg-brand-50 px-4 py-3 text-sm leading-6 text-brand-900"
                            >
                                <CheckCircleIcon class="mt-0.5 h-5 w-5 shrink-0 text-brand-700" aria-hidden="true" />
                                Payment is requested only after review and required clearance are complete.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="announcements.length || faqs.length || paymentInstructions"
                class="border-t border-slate-200 bg-slate-50 py-20 sm:py-24"
            >
                <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                    <div v-if="announcements.length" data-landing-reveal class="landing-reveal">
                        <p class="text-sm font-bold uppercase tracking-[0.2em] text-brand-700">Registrar updates</p>
                        <h2 class="mt-3 font-display text-3xl font-bold tracking-tight text-slate-950">
                            What you should know
                        </h2>
                        <div class="mt-6 space-y-4">
                            <article
                                v-for="announcement in announcements"
                                :key="announcement.id"
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                            >
                                <h3 class="font-display text-lg font-bold text-slate-950">{{ announcement.title }}</h3>
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">
                                    {{ announcement.body }}
                                </p>
                            </article>
                        </div>
                    </div>
                    <div v-if="faqs.length || paymentInstructions" data-landing-reveal class="landing-reveal">
                        <p class="text-sm font-bold uppercase tracking-[0.2em] text-brand-700">Helpful answers</p>
                        <h2 class="mt-3 font-display text-3xl font-bold tracking-tight text-slate-950">
                            Before you submit
                        </h2>
                        <div
                            v-if="paymentInstructions"
                            class="mt-6 rounded-2xl border border-brand-200 bg-brand-50 p-5 text-sm leading-6 text-brand-950"
                        >
                            <p class="font-bold">Payment instructions</p>
                            <p class="mt-2 whitespace-pre-line">{{ paymentInstructions }}</p>
                            <p class="mt-2 text-xs text-brand-800">
                                A final amount and payment reference will appear only after registrar review.
                            </p>
                        </div>
                        <div
                            v-if="faqs.length"
                            class="mt-4 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white px-5"
                        >
                            <details v-for="faq in faqs" :key="faq.id" class="py-4">
                                <summary class="cursor-pointer font-semibold text-slate-900">
                                    {{ faq.question }}
                                </summary>
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">
                                    {{ faq.answer }}
                                </p>
                            </details>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-slate-200 bg-slate-50">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-9 text-sm sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"
            >
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-700 text-white">
                        <BuildingLibraryIcon class="h-4 w-4" aria-hidden="true" />
                    </span>
                    <p class="font-semibold text-slate-700">
                        &copy; {{ new Date().getFullYear() }} St. Vincent College Incorporated.
                    </p>
                </div>
                <p class="text-slate-500">Official online document request portal</p>
            </div>
        </footer>
    </div>
</template>
