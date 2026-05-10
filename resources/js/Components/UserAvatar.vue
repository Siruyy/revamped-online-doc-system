<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const initials = computed(() => {
    const fullName = page.props.auth?.user?.fullname || page.props.auth?.user?.name || 'User';

    return fullName
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
});
</script>

<template>
    <Dropdown align="right" width="48">
        <template #trigger>
            <button
                type="button"
                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-full bg-slate-800 text-sm font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                aria-label="Open user menu"
            >
                {{ initials }}
            </button>
        </template>

        <template #content>
            <DropdownLink :href="route('student.profile.edit')">Profile</DropdownLink>
            <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
        </template>
    </Dropdown>
</template>
