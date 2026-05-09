<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} | SVCI Online Document System</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased">
        <main class="min-h-[100dvh] overflow-hidden bg-[radial-gradient(circle_at_top_left,#dbeafe,transparent_32rem)] px-4 py-10 sm:px-6 lg:px-8">
            <div class="mx-auto flex min-h-[calc(100dvh-5rem)] max-w-5xl items-center justify-center">
                <section class="w-full overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="grid lg:grid-cols-[0.9fr_1.1fr]">
                        <div class="relative hidden bg-slate-950 p-10 text-white lg:block">
                            <div class="absolute inset-0 opacity-30 [background-image:radial-gradient(#60a5fa_1px,transparent_1px)] [background-size:18px_18px]"></div>
                            <div class="relative flex h-full flex-col justify-between">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-brand-200">SVCI Docs</p>
                                    <h1 class="mt-8 font-display text-5xl font-bold tracking-tight">{{ $code }}</h1>
                                </div>
                                <p class="text-sm leading-6 text-slate-300">
                                    The document request portal is still available. Use the recovery actions to return to a safe page.
                                </p>
                            </div>
                        </div>

                        <div class="p-8 sm:p-12">
                            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-brand-700">{{ $code }}</p>
                            <h2 class="mt-4 font-display text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{{ $title }}</h2>
                            <p class="mt-4 max-w-xl text-base leading-7 text-slate-600">{{ $message }}</p>
                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <a
                                    href="{{ url('/') }}"
                                    class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2"
                                >
                                    Return home
                                </a>
                                <a
                                    href="{{ route('login') }}"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2"
                                >
                                    Go to login
                                </a>
                            </div>
                            <p class="mt-8 text-xs text-slate-500">
                                If this keeps happening, contact the registrar or system administrator with code {{ $code }}.
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
