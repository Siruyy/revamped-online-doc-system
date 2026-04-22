<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    faqs: { type: Array, required: true },
});

const createForm = useForm({
    role: 'all',
    question: '',
    answer: '',
    sort_order: 0,
});

const forms = reactive({});
props.faqs.forEach((item) => {
    forms[item.id] = {
        role: item.role,
        question: item.question,
        answer: item.answer,
        sort_order: item.sort_order,
    };
});

const createFaq = () => createForm.post(route('admin.faqs.store'));
const updateFaq = (id) => router.patch(route('admin.faqs.update', id), forms[id]);
const deleteFaq = (id) => router.delete(route('admin.faqs.destroy', id));
</script>

<template>
    <Head title="FAQs" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">FAQs</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Create FAQ</h3>
                <form class="mt-3 grid gap-3 md:grid-cols-2" @submit.prevent="createFaq">
                    <select v-model="createForm.role" class="rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="all">All</option>
                        <option value="student">Student</option>
                        <option value="staff">Staff</option>
                    </select>
                    <input v-model="createForm.sort_order" type="number" min="0" class="rounded-md border-slate-300 text-sm shadow-sm" />
                    <input v-model="createForm.question" type="text" placeholder="Question" class="rounded-md border-slate-300 text-sm shadow-sm md:col-span-2" />
                    <textarea v-model="createForm.answer" rows="3" placeholder="Answer" class="rounded-md border-slate-300 text-sm shadow-sm md:col-span-2" />
                    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 md:col-span-2">Create</button>
                </form>
            </section>

            <section class="space-y-3">
                <article v-for="item in faqs" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <form class="grid gap-3 md:grid-cols-2" @submit.prevent="updateFaq(item.id)">
                        <select v-model="forms[item.id].role" class="rounded-md border-slate-300 text-sm shadow-sm">
                            <option value="all">All</option>
                            <option value="student">Student</option>
                            <option value="staff">Staff</option>
                        </select>
                        <input v-model="forms[item.id].sort_order" type="number" min="0" class="rounded-md border-slate-300 text-sm shadow-sm" />
                        <input v-model="forms[item.id].question" type="text" class="rounded-md border-slate-300 text-sm shadow-sm md:col-span-2" />
                        <textarea v-model="forms[item.id].answer" rows="3" class="rounded-md border-slate-300 text-sm shadow-sm md:col-span-2" />
                        <div class="flex items-center justify-between md:col-span-2">
                            <p class="text-xs text-slate-500">Author: {{ item.author?.fullname }}</p>
                            <div class="flex gap-2">
                                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500">Save</button>
                                <button type="button" class="rounded-md bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500" @click="deleteFaq(item.id)">Delete</button>
                            </div>
                        </div>
                    </form>
                </article>
            </section>
        </div>
    </StaffLayout>
</template>
