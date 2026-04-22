<script setup>
defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <ol class="space-y-4">
        <li v-for="item in items" :key="item.key" class="flex gap-3">
            <div
                class="mt-1 h-2.5 w-2.5 rounded-full"
                :class="item.completed ? 'bg-emerald-500' : item.active ? 'bg-amber-500' : 'bg-slate-300'"
            />
            <div>
                <p class="text-sm font-semibold text-slate-800">{{ item.label }}</p>
                <p v-if="item.timestamp" class="text-xs text-slate-500">{{ item.timestamp }}</p>
            </div>
        </li>
    </ol>
</template>
