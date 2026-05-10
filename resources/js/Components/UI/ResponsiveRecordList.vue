<script setup>
defineProps({
    empty: { type: Boolean, default: false },
});
</script>

<template>
    <div v-if="empty">
        <slot name="empty" />
    </div>

    <div v-else>
        <div class="space-y-3 md:hidden">
            <slot name="cards" />
        </div>

        <div class="hidden md:block">
            <slot name="table" />
        </div>
    </div>
</template>
