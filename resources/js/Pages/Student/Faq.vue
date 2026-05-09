<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    faqs: {
        type: Array,
        required: true,
    },
});

const search = ref('');
const filteredFaqs = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) return props.faqs;

    return props.faqs.filter(
        (faq) => faq.question.toLowerCase().includes(query) || faq.answer.toLowerCase().includes(query),
    );
});
</script>

<template>
    <Head title="FAQ" />

    <StudentLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Frequently Asked Questions</h2>
        </template>

        <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
            <input
                v-model="search"
                type="text"
                placeholder="Search questions..."
                class="block w-full rounded-md border-slate-300 text-sm shadow-sm"
            />

            <div
                v-if="filteredFaqs.length === 0"
                class="rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm"
            >
                No FAQ entries match your search.
            </div>

            <details
                v-for="faq in filteredFaqs"
                :key="faq.id"
                class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
            >
                <summary class="cursor-pointer text-sm font-semibold text-slate-800">{{ faq.question }}</summary>
                <p class="mt-3 text-sm text-slate-600">{{ faq.answer }}</p>
            </details>
        </div>
    </StudentLayout>
</template>
