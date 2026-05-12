<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    meta: { type: Object, required: true },
    label: { type: String, default: 'Pagination' },
    preserveScroll: { type: Boolean, default: true },
});

function displayLabel(label) {
    return label.replace('&laquo;', '‹').replace('&raquo;', '›').trim();
}
</script>

<template>
    <nav
        v-if="meta.last_page > 1"
        class="flex flex-col gap-3 rounded-xl bg-white px-5 py-3 text-xs text-slate-600 shadow-sm ring-1 ring-slate-200 sm:flex-row sm:items-center sm:justify-between"
        :aria-label="label"
    >
        <span>Showing {{ meta.from || 0 }}-{{ meta.to || 0 }} of {{ meta.total }}</span>

        <div class="flex flex-wrap gap-1">
            <template v-for="link in meta.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    :preserve-scroll="preserveScroll"
                    :aria-current="link.active ? 'page' : undefined"
                    :class="[
                        'rounded-lg px-3 py-1.5 transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600',
                        link.active
                            ? 'bg-brand-600 text-white'
                            : 'border border-slate-200 text-slate-600 hover:bg-slate-50',
                    ]"
                >
                    {{ displayLabel(link.label) }}
                </Link>
                <span
                    v-else
                    class="cursor-not-allowed rounded-lg border border-slate-200 px-3 py-1.5 text-slate-400"
                    aria-disabled="true"
                >
                    {{ displayLabel(link.label) }}
                </span>
            </template>
        </div>
    </nav>
</template>
