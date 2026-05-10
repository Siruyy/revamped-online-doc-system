<script setup>
defineProps({
    message: { type: String, default: '' },
    id: { type: String, default: undefined },
});
</script>

<template>
    <p v-show="message" :id="id" class="text-sm text-rose-600" role="alert" aria-live="polite">
        {{ message }}
    </p>
</template>
