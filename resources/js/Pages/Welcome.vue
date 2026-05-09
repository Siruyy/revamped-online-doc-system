<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    AcademicCapIcon,
    ArrowRightIcon,
    BanknotesIcon,
    BuildingOffice2Icon,
    CheckBadgeIcon,
    ClockIcon,
    DocumentCheckIcon,
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

const pathways = [
    {
        title: 'Students and alumni',
        description: 'Request documents, upload receipts, and track approval through release.',
        icon: AcademicCapIcon,
    },
    {
        title: 'Registrar and admin',
        description: 'Review requests, verify payments, manage releases, and monitor service queues.',
        icon: DocumentCheckIcon,
    },
    {
        title: 'Department signatories',
        description: 'Clear teacher, dean, accounting, and SAO requirements from one secure queue.',
        icon: BuildingOffice2Icon,
    },
];

const processSteps = [
    { label: 'Request', text: 'Choose the document type and submit purpose details.', icon: DocumentTextIcon },
    { label: 'Pay', text: 'Upload an offline payment receipt for verification.', icon: BanknotesIcon },
    { label: 'Clear', text: 'Departments sign required clearances digitally.', icon: CheckBadgeIcon },
    { label: 'Release', text: 'Track pickup readiness and final release status.', icon: ShieldCheckIcon },
];
</script>

<template>
    <Head title="SVCI Online Document System" />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-brand-600 selection:text-white">
        <header class="sticky top-0 z-50 border-b border-white/70 bg-white/90 backdrop-blur">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link
                    href="/"
                    class="group flex items-center gap-3 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                >
                    <div
                        class="rounded-2xl bg-brand-600 p-2.5 shadow-sm transition duration-200 group-hover:bg-brand-500"
                    >
                        <DocumentTextIcon class="h-7 w-7 text-white" />
                    </div>
                    <div>
                        <p class="font-display text-xl font-bold tracking-tight text-slate-950">SVCI Docs</p>
                        <p class="hidden text-xs font-semibold uppercase tracking-[0.25em] text-slate-500 sm:block">
                            Online Document System
                        </p>
                    </div>
                </Link>

                <nav v-if="canLogin" class="flex items-center gap-2 sm:gap-3" aria-label="Public navigation">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-brand-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 motion-reduce:hover:translate-y-0"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold text-slate-700 transition duration-200 hover:bg-slate-100 hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="hidden min-h-11 items-center justify-center rounded-xl bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-brand-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 motion-reduce:hover:translate-y-0 sm:inline-flex"
                        >
                            Create account
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main>
            <section class="relative isolate overflow-hidden bg-white">
                <div
                    class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,#dbeafe,transparent_28rem),radial-gradient(circle_at_bottom_right,#fed7aa,transparent_24rem)]"
                ></div>
                <div
                    class="absolute left-1/2 top-12 -z-10 h-72 w-72 -translate-x-1/2 rounded-full bg-brand-200/40 blur-3xl motion-safe:animate-slow-float"
                ></div>
                <div
                    class="mx-auto grid max-w-7xl gap-12 px-4 py-20 sm:px-6 sm:py-28 lg:grid-cols-[1.05fr_0.95fr] lg:px-8"
                >
                    <div class="flex flex-col justify-center">
                        <div
                            class="inline-flex w-fit items-center gap-2 rounded-full bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-800 ring-1 ring-brand-100"
                        >
                            <ClockIcon class="h-4 w-4" />
                            Official SVCI document request portal
                        </div>
                        <h1
                            class="mt-7 max-w-3xl font-display text-4xl font-bold tracking-tight text-slate-950 sm:text-6xl"
                        >
                            Academic documents without the office guesswork.
                        </h1>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
                            Request records, upload payment receipts, monitor clearances, and follow every release
                            milestone from a secure digital gateway.
                        </p>

                        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                            <Link
                                v-if="!$page.props.auth.user && canRegister"
                                :href="route('register')"
                                class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-accent-500 px-6 py-3 text-base font-semibold text-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-accent-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 motion-reduce:hover:translate-y-0"
                            >
                                Start a request
                                <ArrowRightIcon class="h-5 w-5" />
                            </Link>
                            <Link
                                v-else-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-brand-600 px-6 py-3 text-base font-semibold text-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-brand-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 motion-reduce:hover:translate-y-0"
                            >
                                Go to dashboard
                                <ArrowRightIcon class="h-5 w-5" />
                            </Link>
                            <a
                                href="#process"
                                class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 bg-white px-6 py-3 text-base font-semibold text-slate-700 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 motion-reduce:hover:translate-y-0"
                            >
                                See how it works
                            </a>
                        </div>
                    </div>

                    <div class="relative">
                        <div
                            class="absolute -inset-4 -z-10 rounded-[2rem] bg-gradient-to-br from-brand-100 via-white to-orange-100 blur-2xl"
                        ></div>
                        <div
                            class="rounded-[2rem] bg-slate-950 p-4 shadow-2xl ring-1 ring-slate-900/10 motion-safe:animate-fade-in-up"
                        >
                            <div class="rounded-[1.5rem] bg-white p-5 shadow-sm">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-950">Request tracker</p>
                                        <p class="text-xs text-slate-500">Transcript of Records</p>
                                    </div>
                                    <span
                                        class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700"
                                    >
                                        In progress
                                    </span>
                                </div>

                                <div class="mt-5 space-y-4">
                                    <div v-for="(step, index) in processSteps" :key="step.label" class="flex gap-3">
                                        <div class="flex flex-col items-center">
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-xl ring-1 transition duration-200"
                                                :class="
                                                    index < 2
                                                        ? 'bg-brand-600 text-white ring-brand-600'
                                                        : 'bg-slate-50 text-slate-500 ring-slate-200'
                                                "
                                            >
                                                <component :is="step.icon" class="h-5 w-5" />
                                            </div>
                                            <div
                                                v-if="index < processSteps.length - 1"
                                                class="h-8 w-px bg-slate-200"
                                            ></div>
                                        </div>
                                        <div class="pb-2">
                                            <p class="text-sm font-semibold text-slate-900">{{ step.label }}</p>
                                            <p class="text-sm leading-6 text-slate-500">{{ step.text }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-y border-slate-200 bg-slate-50 py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl">
                        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-brand-700">Role paths</p>
                        <h2 class="mt-3 font-display text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                            One portal, clear next steps for every role.
                        </h2>
                    </div>
                    <div class="mt-10 grid gap-5 md:grid-cols-3">
                        <article
                            v-for="pathway in pathways"
                            :key="pathway.title"
                            class="group rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition duration-200 hover:-translate-y-1 hover:shadow-md motion-reduce:hover:translate-y-0"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-700 ring-1 ring-brand-100 transition duration-200 group-hover:bg-brand-600 group-hover:text-white"
                            >
                                <component :is="pathway.icon" class="h-6 w-6" />
                            </div>
                            <h3 class="mt-5 font-display text-lg font-semibold text-slate-950">{{ pathway.title }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ pathway.description }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="process" class="bg-white py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-accent-600">
                                Service flow
                            </p>
                            <h2 class="mt-3 font-display text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                                Built for transparent document processing.
                            </h2>
                            <p class="mt-4 text-base leading-7 text-slate-600">
                                Students see meaningful progress while offices work from focused queues. Each status
                                update gives users feedback instead of a dead end.
                            </p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <article
                                v-for="step in processSteps"
                                :key="step.label"
                                class="rounded-2xl border border-slate-200 bg-slate-50 p-5 transition duration-200 hover:border-brand-200 hover:bg-brand-50/40"
                            >
                                <component :is="step.icon" class="h-7 w-7 text-brand-700" />
                                <h3 class="mt-4 font-display text-lg font-semibold text-slate-950">{{ step.label }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ step.text }}</p>
                            </article>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"
            >
                <p>&copy; {{ new Date().getFullYear() }} St. Vincent College Incorporated. All rights reserved.</p>
                <p class="text-slate-400">Laravel v{{ laravelVersion }} / PHP v{{ phpVersion }}</p>
            </div>
        </footer>
    </div>
</template>
