<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    course: '',
    year_level: '',
    student_id: '',
    contact_number: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Full Name" />

                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="course" value="Course" />

                <TextInput id="course" v-model="form.course" type="text" class="mt-1 block w-full" required />

                <InputError class="mt-2" :message="form.errors.course" />
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="year_level" value="Year Level" />

                    <TextInput
                        id="year_level"
                        v-model="form.year_level"
                        type="number"
                        min="1"
                        max="8"
                        class="mt-1 block w-full"
                        required
                    />

                    <InputError class="mt-2" :message="form.errors.year_level" />
                </div>

                <div>
                    <InputLabel for="student_id" value="Student ID" />

                    <TextInput
                        id="student_id"
                        v-model="form.student_id"
                        type="text"
                        class="mt-1 block w-full"
                        required
                    />

                    <InputError class="mt-2" :message="form.errors.student_id" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="contact_number" value="Contact Number" />

                <TextInput
                    id="contact_number"
                    v-model="form.contact_number"
                    type="text"
                    class="mt-1 block w-full"
                    required
                />

                <InputError class="mt-2" :message="form.errors.contact_number" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirm Password" />

                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Already registered?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Register
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
