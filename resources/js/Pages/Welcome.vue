<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    DocumentTextIcon,
    ArrowRightIcon,
    ShieldCheckIcon,
    ClockIcon,
    DocumentCheckIcon,
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
</script>

<template>
    <Head title="SVCI Online Document System" />

    <div class="min-h-screen bg-slate-50 text-slate-900 font-sans selection:bg-brand-600 selection:text-white">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <div class="flex items-center gap-3">
                        <div class="bg-brand-600 p-2.5 rounded-lg shadow-sm">
                            <DocumentTextIcon class="w-7 h-7 text-white" />
                        </div>
                        <div>
                            <span class="font-display font-bold text-xl text-slate-900 tracking-tight">SVCI</span>
                            <span class="font-display font-medium text-xl text-slate-600 tracking-tight"> Docs</span>
                        </div>
                    </div>

                    <nav v-if="canLogin" class="flex items-center gap-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="text-sm font-semibold text-slate-600 hover:text-brand-600 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-md px-3 py-2"
                        >
                            Go to Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-sm font-semibold text-slate-600 hover:text-brand-600 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-md px-3 py-2"
                            >
                                Log in
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="inline-flex items-center justify-center rounded-md bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 transition-all active:scale-[0.98]"
                            >
                                Create Account
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <main>
            <div class="relative overflow-hidden bg-white">
                <div
                    class="absolute inset-y-0 w-full h-full bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:16px_16px] [mask-image:radial-gradient(ellipse_50%_50%_at_50%_50%,#000_70%,transparent_100%)] opacity-40"
                ></div>
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32">
                    <div class="text-center max-w-3xl mx-auto">
                        <h1
                            class="font-display text-4xl sm:text-6xl font-bold tracking-tight text-slate-900 mb-8 leading-tight"
                        >
                            Request Academic Documents <span class="text-brand-600">Online</span>
                        </h1>
                        <p class="text-lg sm:text-xl text-slate-600 mb-10 leading-relaxed max-w-2xl mx-auto">
                            The official portal for St. Vincent College Incorporated students and alumni to request
                            transcripts, certificates, and clearances. Secure, fast, and fully digital.
                        </p>

                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                            <Link
                                v-if="!$page.props.auth.user && canRegister"
                                :href="route('register')"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-accent-500 px-8 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-accent-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-500 transition-all active:scale-[0.98]"
                            >
                                Start a Request
                                <ArrowRightIcon class="w-5 h-5" />
                            </Link>
                            <Link
                                v-else-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-8 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 transition-all active:scale-[0.98]"
                            >
                                Go to Dashboard
                                <ArrowRightIcon class="w-5 h-5" />
                            </Link>

                            <a
                                href="#features"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-white px-8 py-3.5 text-base font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 transition-all"
                            >
                                Learn More
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div id="features" class="py-24 sm:py-32 bg-slate-50 border-t border-slate-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-brand-600 font-semibold tracking-wide uppercase text-sm mb-3">
                            System Features
                        </h2>
                        <p class="font-display text-3xl font-bold text-slate-900 sm:text-4xl">
                            Everything you need in one place
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 sm:gap-12">
                        <!-- Feature 1 -->
                        <div
                            class="bg-white rounded-2xl p-8 shadow-sm ring-1 ring-slate-200 transition-shadow hover:shadow-md"
                        >
                            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center mb-6">
                                <ClockIcon class="w-6 h-6 text-brand-600" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-3">Real-time Tracking</h3>
                            <p class="text-slate-600 leading-relaxed">
                                Monitor the exact status of your requests and departmental clearances instantly. No more
                                guessing or calling the registrar.
                            </p>
                        </div>

                        <!-- Feature 2 -->
                        <div
                            class="bg-white rounded-2xl p-8 shadow-sm ring-1 ring-slate-200 transition-shadow hover:shadow-md"
                        >
                            <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center mb-6">
                                <DocumentCheckIcon class="w-6 h-6 text-accent-500" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-3">Digital Clearance</h3>
                            <p class="text-slate-600 leading-relaxed">
                                Departments sign off on your requests digitally. Download your final clearance
                                certificate as a secure PDF once completed.
                            </p>
                        </div>

                        <!-- Feature 3 -->
                        <div
                            class="bg-white rounded-2xl p-8 shadow-sm ring-1 ring-slate-200 transition-shadow hover:shadow-md"
                        >
                            <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center mb-6">
                                <ShieldCheckIcon class="w-6 h-6 text-green-600" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-3">Secure Uploads</h3>
                            <p class="text-slate-600 leading-relaxed">
                                Upload payment receipts and supporting documents securely. Your personal and academic
                                data is protected by enterprise-grade security.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 mt-auto">
            <div
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col md:flex-row items-center justify-between gap-4"
            >
                <p class="text-sm text-slate-500">
                    &copy; {{ new Date().getFullYear() }} St. Vincent College Incorporated. All rights reserved.
                </p>
                <div class="text-sm text-slate-400">Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})</div>
            </div>
        </footer>
    </div>
</template>
