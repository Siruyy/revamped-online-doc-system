<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    announcements: { type: Array, required: true },
});

const createForm = useForm({
    title: '',
    body: '',
    audience: 'all',
    pinned: false,
    is_published: true,
});

const forms = reactive({});
props.announcements.forEach((item) => {
    forms[item.id] = {
        title: item.title,
        body: item.body,
        audience: item.audience,
        pinned: Boolean(item.pinned),
        is_published: Boolean(item.published_at),
    };
});

const createAnnouncement = () => createForm.post(route('admin.announcements.store'));
const updateAnnouncement = (id) => router.patch(route('admin.announcements.update', id), forms[id]);
const deleteAnnouncement = (id) => router.delete(route('admin.announcements.destroy', id));
</script>

<template>
    <Head title="Announcements" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Announcements</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Create Announcement</h3>
                <form class="mt-3 grid gap-3 md:grid-cols-2" @submit.prevent="createAnnouncement">
                    <input v-model="createForm.title" type="text" placeholder="Title" class="rounded-md border-slate-300 text-sm shadow-sm md:col-span-2" />
                    <textarea v-model="createForm.body" rows="3" placeholder="Body" class="rounded-md border-slate-300 text-sm shadow-sm md:col-span-2" />
                    <select v-model="createForm.audience" class="rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="all">All</option>
                        <option value="student">Student</option>
                        <option value="staff">Staff</option>
                    </select>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input v-model="createForm.pinned" type="checkbox" class="rounded border-slate-300 text-indigo-600 shadow-sm" />
                        Pinned
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 md:col-span-2">
                        <input v-model="createForm.is_published" type="checkbox" class="rounded border-slate-300 text-indigo-600 shadow-sm" />
                        Publish now
                    </label>
                    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 md:col-span-2">Create</button>
                </form>
            </section>

            <section class="space-y-3">
                <article v-for="item in announcements" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <form class="grid gap-3 md:grid-cols-2" @submit.prevent="updateAnnouncement(item.id)">
                        <input v-model="forms[item.id].title" type="text" class="rounded-md border-slate-300 text-sm shadow-sm md:col-span-2" />
                        <textarea v-model="forms[item.id].body" rows="3" class="rounded-md border-slate-300 text-sm shadow-sm md:col-span-2" />
                        <select v-model="forms[item.id].audience" class="rounded-md border-slate-300 text-sm shadow-sm">
                            <option value="all">All</option>
                            <option value="student">Student</option>
                            <option value="staff">Staff</option>
                        </select>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input v-model="forms[item.id].pinned" type="checkbox" class="rounded border-slate-300 text-indigo-600 shadow-sm" />
                            Pinned
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 md:col-span-2">
                            <input v-model="forms[item.id].is_published" type="checkbox" class="rounded border-slate-300 text-indigo-600 shadow-sm" />
                            Published
                        </label>
                        <div class="flex items-center justify-between md:col-span-2">
                            <p class="text-xs text-slate-500">Author: {{ item.author?.fullname }}</p>
                            <div class="flex gap-2">
                                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500">Save</button>
                                <button type="button" class="rounded-md bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500" @click="deleteAnnouncement(item.id)">Delete</button>
                            </div>
                        </div>
                    </form>
                </article>
            </section>
        </div>
    </StaffLayout>
</template>
