<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import FormField from '@/Components/UI/FormField.vue';
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

        <form :aria-busy="form.processing ? 'true' : undefined" @submit.prevent="submit">
            <FormField id="name" label="Full Name" :error="form.errors.name" required>
                <template #default="{ id, describedBy, invalid }">
                    <TextInput
                        :id="id"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                        autocomplete="name"
                        :described-by="describedBy"
                        :invalid="invalid"
                    />
                </template>
            </FormField>

            <FormField id="email" class="mt-4" label="Email" :error="form.errors.email" required>
                <template #default="{ id, describedBy, invalid }">
                    <TextInput
                        :id="id"
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full"
                        required
                        autocomplete="username"
                        :described-by="describedBy"
                        :invalid="invalid"
                    />
                </template>
            </FormField>

            <FormField id="course" class="mt-4" label="Course" :error="form.errors.course" required>
                <template #default="{ id, describedBy, invalid }">
                    <TextInput
                        :id="id"
                        v-model="form.course"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        :described-by="describedBy"
                        :invalid="invalid"
                    />
                </template>
            </FormField>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <FormField id="year_level" label="Year Level" :error="form.errors.year_level" required>
                    <template #default="{ id, describedBy, invalid }">
                        <TextInput
                            :id="id"
                            v-model="form.year_level"
                            type="number"
                            min="1"
                            max="8"
                            class="mt-1 block w-full"
                            required
                            :described-by="describedBy"
                            :invalid="invalid"
                        />
                    </template>
                </FormField>

                <FormField id="student_id" label="Student ID" :error="form.errors.student_id" required>
                    <template #default="{ id, describedBy, invalid }">
                        <TextInput
                            :id="id"
                            v-model="form.student_id"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            :described-by="describedBy"
                            :invalid="invalid"
                        />
                    </template>
                </FormField>
            </div>

            <FormField
                id="contact_number"
                class="mt-4"
                label="Contact Number"
                :error="form.errors.contact_number"
                required
            >
                <template #default="{ id, describedBy, invalid }">
                    <TextInput
                        :id="id"
                        v-model="form.contact_number"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        :described-by="describedBy"
                        :invalid="invalid"
                    />
                </template>
            </FormField>

            <FormField id="password" class="mt-4" label="Password" :error="form.errors.password" required>
                <template #default="{ id, describedBy, invalid }">
                    <TextInput
                        :id="id"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="new-password"
                        :described-by="describedBy"
                        :invalid="invalid"
                    />
                </template>
            </FormField>

            <FormField
                id="password_confirmation"
                class="mt-4"
                label="Confirm Password"
                :error="form.errors.password_confirmation"
                required
            >
                <template #default="{ id, describedBy, invalid }">
                    <TextInput
                        :id="id"
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="new-password"
                        :described-by="describedBy"
                        :invalid="invalid"
                    />
                </template>
            </FormField>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Already registered?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    :aria-busy="form.processing ? 'true' : undefined"
                >
                    {{ form.processing ? 'Registering...' : 'Register' }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
