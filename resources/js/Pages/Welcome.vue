<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRightIcon,
    ArrowUpRightIcon,
    BuildingLibraryIcon,
    DocumentArrowUpIcon,
    DocumentCheckIcon,
    DocumentMagnifyingGlassIcon,
    DocumentTextIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';

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
});

const processSteps = [
    {
        title: 'Send your request',
        description: 'Choose the documents you need, add your details, and select how you would like them released.',
        icon: DocumentTextIcon,
    },
    {
        title: 'Receive a Registrar review',
        description: 'The Registrar confirms the request, fees, and any supporting requirements before payment.',
        icon: DocumentCheckIcon,
    },
    {
        title: 'Complete clearance and payment',
        description: 'When clearance is required, the right offices review it. Then you can upload your receipt.',
        icon: DocumentArrowUpIcon,
    },
    {
        title: 'Track and claim',
        description: 'Use your reference number to see each update and know when your documents are ready.',
        icon: ShieldCheckIcon,
    },
];
</script>

<template>
    <Head title="SVCI Online Document System" />

    <div
        class="min-h-[100dvh] overflow-x-clip bg-slate-50 font-sans text-slate-900 selection:bg-brand-700 selection:text-white dark:bg-slate-950 dark:text-slate-100"
    >
        <a
            href="#main-content"
            class="sr-only left-4 top-4 z-50 rounded-lg bg-brand-700 px-4 py-3 text-sm font-bold text-white focus:not-sr-only focus:absolute focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
        >
            Skip to main content
        </a>

        <header
            class="border-b border-slate-200/90 bg-slate-50/90 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-950/90"
        >
            <div class="mx-auto flex h-[72px] max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link
                    href="/"
                    class="group flex items-center gap-3 rounded-lg active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950"
                >
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-700 text-white shadow-[0_8px_20px_rgba(30,58,95,0.18)] transition duration-200 group-hover:bg-brand-600"
                    >
                        <BuildingLibraryIcon class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <span>
                        <span
                            class="block font-display text-lg font-bold tracking-tight text-slate-950 dark:text-white"
                        >
                            SVCI Docs
                        </span>
                        <span
                            class="hidden text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400 sm:block"
                        >
                            Document services
                        </span>
                    </span>
                </Link>

                <nav v-if="canLogin" class="flex items-center gap-3" aria-label="Public navigation">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg bg-brand-700 px-4 py-2 text-sm font-bold text-white transition duration-200 hover:bg-brand-600 active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg px-3 py-2 text-sm font-bold text-slate-700 transition duration-200 hover:bg-slate-200/70 hover:text-brand-800 active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-white dark:focus-visible:ring-offset-slate-950"
                    >
                        Staff login
                    </Link>
                </nav>
            </div>
        </header>

        <main id="main-content">
            <section class="relative isolate overflow-hidden">
                <div
                    class="absolute inset-x-0 top-0 -z-10 h-[30rem] bg-[radial-gradient(circle_at_88%_10%,rgba(219,234,254,0.9),transparent_35%),radial-gradient(circle_at_8%_0%,rgba(255,255,255,0.96),transparent_42%)] dark:bg-[radial-gradient(circle_at_88%_10%,rgba(30,64,175,0.18),transparent_35%),radial-gradient(circle_at_8%_0%,rgba(15,23,42,0.4),transparent_42%)]"
                ></div>
                <div
                    class="mx-auto grid max-w-7xl gap-10 px-4 pb-14 pt-12 sm:px-6 sm:pb-20 sm:pt-16 lg:grid-cols-12 lg:items-center lg:gap-8 lg:px-8 lg:pb-24 lg:pt-20"
                >
                    <div class="landing-hero-copy lg:col-span-6 lg:pr-12">
                        <p class="text-sm font-bold text-brand-800 dark:text-brand-300">
                            St. Vincent College Incorporated
                        </p>
                        <h1
                            class="mt-5 max-w-xl font-display text-4xl font-bold leading-[1.07] tracking-tight text-slate-950 dark:text-white sm:text-5xl lg:text-[3.5rem]"
                        >
                            Official records. Clear next steps.
                        </h1>
                        <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                            Request official school documents online, then follow every review step through payment and
                            release.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <Link
                                v-if="!$page.props.auth.user"
                                :href="route('public.requests.create')"
                                class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 py-3 text-base font-bold text-white shadow-[0_10px_24px_rgba(30,58,95,0.2)] transition duration-200 hover:-translate-y-0.5 hover:bg-brand-600 active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 motion-reduce:hover:translate-y-0 dark:focus-visible:ring-offset-slate-950"
                            >
                                Request document
                                <ArrowRightIcon class="h-5 w-5" aria-hidden="true" />
                            </Link>
                            <Link
                                v-else
                                :href="route('dashboard')"
                                class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 py-3 text-base font-bold text-white shadow-[0_10px_24px_rgba(30,58,95,0.2)] transition duration-200 hover:-translate-y-0.5 hover:bg-brand-600 active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 motion-reduce:hover:translate-y-0 dark:focus-visible:ring-offset-slate-950"
                            >
                                Open dashboard
                                <ArrowRightIcon class="h-5 w-5" aria-hidden="true" />
                            </Link>
                            <Link
                                :href="route('track-document')"
                                class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white/80 px-5 py-3 text-base font-bold text-slate-800 transition duration-200 hover:-translate-y-0.5 hover:border-brand-300 hover:bg-white active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 motion-reduce:hover:translate-y-0 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-100 dark:hover:border-brand-500 dark:hover:bg-slate-900 dark:focus-visible:ring-offset-slate-950"
                            >
                                Track request
                                <ArrowUpRightIcon class="h-4 w-4" aria-hidden="true" />
                            </Link>
                        </div>
                    </div>

                    <div class="landing-hero-media relative lg:col-span-6">
                        <div
                            class="absolute -bottom-5 -left-4 hidden h-32 w-32 rounded-2xl border border-brand-200 bg-brand-100/70 lg:block dark:border-brand-800 dark:bg-brand-950/40"
                        ></div>
                        <div
                            class="relative aspect-[4/3] overflow-hidden rounded-[20px] bg-slate-200 shadow-[0_24px_65px_rgba(15,23,42,0.18)] ring-1 ring-slate-900/10 dark:bg-slate-800 dark:shadow-black/30 dark:ring-white/10"
                        >
                            <img
                                class="h-full w-full object-cover object-center"
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

            <section class="border-y border-brand-900 bg-brand-900 text-white">
                <div class="mx-auto flex max-w-7xl items-start gap-4 px-4 py-6 sm:items-center sm:px-6 lg:px-8">
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-white/20 bg-white/10 text-brand-100"
                    >
                        <DocumentMagnifyingGlassIcon class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <p class="max-w-3xl text-sm font-semibold leading-6 sm:text-base">
                        No requestor account needed. Keep your reference number to follow the Registrar review,
                        clearance, payment, and release stages.
                    </p>
                </div>
            </section>

            <section class="bg-white py-16 dark:bg-slate-900 sm:py-20 lg:py-24">
                <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-12 lg:gap-16 lg:px-8">
                    <div class="lg:col-span-4">
                        <h2
                            class="max-w-md font-display text-3xl font-bold leading-tight tracking-tight text-slate-950 dark:text-white sm:text-4xl"
                        >
                            A clear route to release.
                        </h2>
                        <p class="mt-5 max-w-md text-base leading-7 text-slate-600 dark:text-slate-300">
                            Each stage has one job, so you always know what the Registrar or another office needs next.
                        </p>
                    </div>

                    <ol class="landing-journey relative space-y-2 lg:col-span-8">
                        <li
                            v-for="step in processSteps"
                            :key="step.title"
                            class="landing-journey-item relative grid gap-4 rounded-xl px-3 py-4 transition duration-200 hover:bg-brand-50/70 sm:grid-cols-[2.75rem_1fr] sm:items-start sm:px-5 dark:hover:bg-slate-800/80"
                        >
                            <span
                                class="relative z-10 flex h-11 w-11 items-center justify-center rounded-xl border border-brand-200 bg-white text-brand-800 shadow-sm transition duration-200 dark:border-brand-800 dark:bg-slate-900 dark:text-brand-300"
                            >
                                <component :is="step.icon" class="h-5 w-5" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <h3
                                    class="font-display text-xl font-bold tracking-tight text-slate-950 dark:text-white"
                                >
                                    {{ step.title }}
                                </h3>
                                <p class="mt-1 max-w-xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                    {{ step.description }}
                                </p>
                            </div>
                        </li>
                    </ol>
                </div>
            </section>

            <section class="bg-slate-100 py-16 dark:bg-slate-950 sm:py-20 lg:py-24">
                <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-12 lg:items-stretch lg:px-8">
                    <div class="overflow-hidden rounded-[20px] bg-slate-900 lg:col-span-5">
                        <img
                            class="h-full min-h-[19rem] w-full object-cover opacity-90 transition duration-500 hover:scale-[1.03] motion-reduce:hover:scale-100"
                            src="/images/landing/study-materials.jpg"
                            alt="Open books and study materials arranged on a table"
                            width="1100"
                            height="733"
                            loading="lazy"
                        />
                    </div>
                    <div
                        class="flex flex-col justify-center rounded-[20px] border border-slate-200 bg-white p-7 shadow-[0_16px_40px_rgba(15,23,42,0.06)] dark:border-slate-800 dark:bg-slate-900 sm:p-10 lg:col-span-7"
                    >
                        <h2
                            class="max-w-xl font-display text-3xl font-bold leading-tight tracking-tight text-slate-950 dark:text-white sm:text-4xl"
                        >
                            Bring the details that make review simple.
                        </h2>
                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 p-5 dark:bg-slate-800">
                                <h3 class="font-display text-lg font-bold text-slate-950 dark:text-white">
                                    Document details
                                </h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                    Select the document, copies, purpose, and preferred release method.
                                </p>
                            </div>
                            <div class="rounded-xl bg-brand-50 p-5 dark:bg-brand-950/30">
                                <h3 class="font-display text-lg font-bold text-slate-950 dark:text-white">
                                    Supporting requirements
                                </h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                    Have identification and any supporting files ready for upload when requested.
                                </p>
                            </div>
                        </div>
                        <p class="mt-7 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Payment is requested only after the Registrar has reviewed your request and clearance
                            requirements are complete.
                        </p>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-8 text-sm sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"
            >
                <p class="text-slate-600 dark:text-slate-300">
                    &copy; {{ new Date().getFullYear() }} St. Vincent College Incorporated.
                </p>
                <p class="text-slate-500 dark:text-slate-400">Official online document request portal</p>
            </div>
        </footer>
    </div>
</template>
