<script setup>
defineProps({
    lines: { type: Number, default: 3 },
    title: { type: Boolean, default: false },
    rows: { type: Number, default: 0 },
});
</script>

<template>
    <div
        class="animate-pulse rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
        role="status"
        aria-label="Loading content"
    >
        <div v-if="title" class="mb-5 h-5 w-1/3 rounded-full bg-slate-200"></div>
        <div v-if="rows > 0" class="space-y-3">
            <div v-for="row in rows" :key="row" class="grid gap-3 sm:grid-cols-4">
                <div class="h-4 rounded-full bg-slate-200"></div>
                <div class="h-4 rounded-full bg-slate-200"></div>
                <div class="h-4 rounded-full bg-slate-200"></div>
                <div class="h-4 rounded-full bg-slate-200"></div>
            </div>
        </div>
        <div v-else class="space-y-3">
            <div
                v-for="line in lines"
                :key="line"
                class="h-4 rounded-full bg-slate-200"
                :class="line === lines ? 'w-2/3' : 'w-full'"
            ></div>
        </div>
    </div>
</template>
