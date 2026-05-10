<script setup>
defineProps({
    minWidth: { type: String, default: 'min-w-[48rem]' },
    label: { type: String, default: 'Data table' },
});
</script>

<template>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto" tabindex="0" :aria-label="`${label}. Scroll horizontally if needed.`">
            <div :class="minWidth">
                <slot />
            </div>
        </div>
    </div>
</template>
