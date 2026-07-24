<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    profileUpdateRoute: {
        type: String,
        default: 'profile.update',
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    username: user.username,
    email: user.email,
    contact_number: user.contact_number ?? '',
    course: user.course ?? '',
    year_level: user.year_level ?? '',
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>

            <p class="mt-1 text-sm text-gray-600">
                Update the personal and login details associated with your account.
            </p>
        </header>

        <form class="mt-6 space-y-6" @submit.prevent="form.patch(route(props.profileUpdateRoute))">
            <div>
                <InputLabel for="name" value="Name" />

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

            <div>
                <InputLabel for="username" value="Username" />

                <TextInput
                    id="username"
                    v-model="form.username"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autocomplete="username"
                    aria-describedby="username-help"
                />

                <p id="username-help" class="mt-1 text-xs leading-5 text-slate-500">
                    Use 3–30 lowercase letters, numbers, dots, hyphens, or underscores.
                </p>
                <InputError class="mt-2" :message="form.errors.username" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="email"
                    aria-describedby="email-help"
                />

                <p id="email-help" class="mt-1 text-xs leading-5 text-slate-500">
                    Changing your email requires verification of the new address.
                </p>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="contact_number" value="Contact number" />

                <TextInput
                    id="contact_number"
                    v-model="form.contact_number"
                    type="tel"
                    class="mt-1 block w-full"
                    autocomplete="tel"
                />

                <InputError class="mt-2" :message="form.errors.contact_number" />
            </div>

            <div v-if="user.role === 'student'" class="grid gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel for="course" value="Course" />
                    <TextInput id="course" v-model="form.course" type="text" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.course" />
                </div>

                <div>
                    <InputLabel for="year_level" value="Year level" />
                    <TextInput
                        id="year_level"
                        v-model="form.year_level"
                        type="number"
                        min="1"
                        max="8"
                        class="mt-1 block w-full"
                    />
                    <InputError class="mt-2" :message="form.errors.year_level" />
                </div>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save changes' }}
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
