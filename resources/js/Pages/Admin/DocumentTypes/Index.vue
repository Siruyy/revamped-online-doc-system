<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormField from '@/Components/UI/FormField.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import {
    CheckIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    DocumentTextIcon,
    MagnifyingGlassIcon,
    PencilIcon,
    PlusIcon,
    TrashIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    documentTypes: { type: Array, required: true },
});

// ─── Create ──────────────────────────────────────────────────────────────────
const showCreate = ref(false);

const createForm = useForm({
    name: '',
    description: '',
    category: '',
    fee: '',
    default_page_count: 1,
    processing_days: 3,
    is_active: true,
});

const save = () =>
    createForm.post(route('admin.document-types.store'), {
        onSuccess: () => {
            showCreate.value = false;
            createForm.reset();
        },
    });

// ─── Edit ─────────────────────────────────────────────────────────────────────
const expandedId = ref(null);

const updateForms = reactive({});
props.documentTypes.forEach((type) => {
    updateForms[type.id] = {
        name: type.name,
        description: type.description ?? '',
        category: type.category,
        fee: type.fee,
        default_page_count: type.default_page_count || 1,
        processing_days: type.processing_days,
        is_active: Boolean(type.is_active),
        processing: false,
    };
});

function toggleExpand(id) {
    expandedId.value = expandedId.value === id ? null : id;
}

const update = (typeId) => {
    const form = updateForms[typeId];
    form.processing = true;
    router.patch(route('admin.document-types.update', typeId), form, {
        onFinish: () => {
            form.processing = false;
        },
        onSuccess: () => {
            expandedId.value = null;
        },
    });
};

const destroyType = (typeId, name) => {
    if (!window.confirm(`Delete or disable "${name}"?`)) return;
    router.delete(route('admin.document-types.destroy', typeId));
};

// ─── Search / filter ─────────────────────────────────────────────────────────
const search = ref('');
const filterActive = ref('all');

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.documentTypes.filter((t) => {
        const matchSearch = !q || t.name.toLowerCase().includes(q) || t.category.toLowerCase().includes(q);
        const matchActive =
            filterActive.value === 'all' ||
            (filterActive.value === 'active' && t.is_active) ||
            (filterActive.value === 'inactive' && !t.is_active);
        return matchSearch && matchActive;
    });
});

function formatPeso(n) {
    return `₱${Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
}
</script>

<template>
    <Head title="Document Types" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Document Types</h2>
        </template>

        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <!-- ── Add New Type ─────────────────────────────── -->
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <button
                    type="button"
                    class="flex w-full items-center justify-between px-5 py-4 text-left"
                    @click="showCreate = !showCreate"
                >
                    <span class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <PlusIcon class="h-4 w-4 text-indigo-600" />
                        Add New Document Type
                    </span>
                    <ChevronUpIcon v-if="showCreate" class="h-4 w-4 text-slate-400" />
                    <ChevronDownIcon v-else class="h-4 w-4 text-slate-400" />
                </button>

                <div v-if="showCreate" class="border-t border-slate-100 px-5 py-5">
                    <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="save">
                        <FormField id="document-type-name" label="Name" :error="createForm.errors.name" required>
                            <template #default="{ id, describedBy, invalid }">
                                <input
                                    :id="id"
                                    v-model="createForm.name"
                                    type="text"
                                    placeholder="e.g. Transcript of Records"
                                    class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-300"
                                    :aria-describedby="describedBy"
                                    :aria-invalid="invalid ? 'true' : undefined"
                                />
                            </template>
                        </FormField>

                        <FormField
                            id="document-type-category"
                            label="Category"
                            :error="createForm.errors.category"
                            required
                        >
                            <template #default="{ id, describedBy, invalid }">
                                <input
                                    :id="id"
                                    v-model="createForm.category"
                                    type="text"
                                    placeholder="e.g. Academic"
                                    class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-300"
                                    :aria-describedby="describedBy"
                                    :aria-invalid="invalid ? 'true' : undefined"
                                />
                            </template>
                        </FormField>

                        <FormField
                            id="document-type-fee"
                            label="Fee per Page (₱)"
                            :error="createForm.errors.fee"
                            required
                        >
                            <template #default="{ id, describedBy, invalid }">
                                <input
                                    :id="id"
                                    v-model="createForm.fee"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    class="rounded-md border-slate-300 text-sm shadow-sm"
                                    :aria-describedby="describedBy"
                                    :aria-invalid="invalid ? 'true' : undefined"
                                />
                            </template>
                        </FormField>

                        <FormField
                            id="document-type-default-page-count"
                            label="Pages per Document"
                            :error="createForm.errors.default_page_count"
                            help="Total = fee × pages × copies."
                            required
                        >
                            <template #default="{ id, describedBy, invalid }">
                                <input
                                    :id="id"
                                    v-model="createForm.default_page_count"
                                    type="number"
                                    min="1"
                                    max="500"
                                    placeholder="1"
                                    class="rounded-md border-slate-300 text-sm shadow-sm"
                                    :aria-describedby="describedBy"
                                    :aria-invalid="invalid ? 'true' : undefined"
                                />
                            </template>
                        </FormField>

                        <FormField
                            id="document-type-processing-days"
                            label="Processing Days"
                            :error="createForm.errors.processing_days"
                            required
                        >
                            <template #default="{ id, describedBy, invalid }">
                                <input
                                    :id="id"
                                    v-model="createForm.processing_days"
                                    type="number"
                                    min="1"
                                    max="365"
                                    class="rounded-md border-slate-300 text-sm shadow-sm"
                                    :aria-describedby="describedBy"
                                    :aria-invalid="invalid ? 'true' : undefined"
                                />
                            </template>
                        </FormField>

                        <FormField
                            id="document-type-description"
                            label="Description"
                            :error="createForm.errors.description"
                        >
                            <template #default="{ id, describedBy, invalid }">
                                <input
                                    :id="id"
                                    v-model="createForm.description"
                                    type="text"
                                    placeholder="Optional note for students"
                                    class="rounded-md border-slate-300 text-sm shadow-sm"
                                    :aria-describedby="describedBy"
                                    :aria-invalid="invalid ? 'true' : undefined"
                                />
                            </template>
                        </FormField>

                        <div class="lg:col-span-3 flex items-center justify-between">
                            <label
                                class="inline-flex items-center gap-2 text-sm text-slate-700"
                                for="document-type-active"
                            >
                                <input
                                    id="document-type-active"
                                    v-model="createForm.is_active"
                                    type="checkbox"
                                    class="rounded border-slate-300 text-indigo-600 shadow-sm"
                                />
                                Active
                            </label>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="text-sm text-slate-500 hover:text-slate-700"
                                    @click="
                                        showCreate = false;
                                        createForm.reset();
                                    "
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="createForm.processing"
                                    class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                                    :aria-busy="createForm.processing ? 'true' : undefined"
                                >
                                    <PlusIcon class="h-3.5 w-3.5" />
                                    {{ createForm.processing ? 'Creating…' : 'Create' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>

            <!-- ── Existing Types ──────────────────────────── -->
            <section>
                <!-- Toolbar -->
                <div class="mb-3 flex flex-wrap items-center gap-3">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mr-auto">
                        Existing Types
                        <span class="ml-1 rounded-full bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-600">{{
                            filtered.length
                        }}</span>
                    </h3>
                    <!-- Search -->
                    <div class="relative">
                        <label for="document-type-search" class="sr-only">Search document types</label>
                        <MagnifyingGlassIcon
                            class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        />
                        <input
                            id="document-type-search"
                            v-model="search"
                            type="text"
                            placeholder="Search name or category…"
                            class="w-56 rounded-md border-slate-300 pl-8 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-300"
                        />
                        <button
                            v-if="search"
                            type="button"
                            class="absolute right-1 top-1/2 flex min-h-11 min-w-11 -translate-y-1/2 items-center justify-center text-slate-400 hover:text-slate-600"
                            aria-label="Clear document type search"
                            @click="search = ''"
                        >
                            <XMarkIcon class="h-4 w-4" />
                        </button>
                    </div>
                    <!-- Active filter -->
                    <label for="document-type-active-filter" class="sr-only"
                        >Filter document types by active state</label
                    >
                    <select
                        id="document-type-active-filter"
                        v-model="filterActive"
                        class="min-h-11 rounded-md border-slate-300 text-sm shadow-sm"
                    >
                        <option value="all">All</option>
                        <option value="active">Active only</option>
                        <option value="inactive">Inactive only</option>
                    </select>
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <!-- Header row -->
                    <div
                        class="hidden sm:grid grid-cols-[1fr_120px_110px_80px_70px_100px] gap-3 border-b border-slate-100 bg-slate-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500"
                    >
                        <span>Name / Category</span>
                        <span class="text-right">Fee / Page</span>
                        <span class="text-center">Pages</span>
                        <span class="text-center">SLA</span>
                        <span class="text-center">Status</span>
                        <span class="text-right">Actions</span>
                    </div>

                    <EmptyState
                        v-if="!filtered.length"
                        :title="documentTypes.length ? 'No document types match your filter' : 'No document types yet'"
                        :description="
                            documentTypes.length
                                ? 'Adjust search or status filters to find an existing document type.'
                                : 'Use the form above to add the first requestable document type.'
                        "
                        :icon="DocumentTextIcon"
                        compact
                        class="m-4"
                    />

                    <template v-for="type in filtered" :key="type.id">
                        <!-- Compact summary row -->
                        <div
                            class="grid grid-cols-1 sm:grid-cols-[1fr_120px_110px_80px_70px_100px] items-center gap-3 px-4 py-3 transition-colors hover:bg-slate-50/80"
                            :class="
                                expandedId === type.id
                                    ? 'bg-indigo-50/40 border-b border-indigo-100'
                                    : 'border-b border-slate-100 last:border-0'
                            "
                        >
                            <!-- Name + Category -->
                            <div>
                                <div class="flex items-center gap-2">
                                    <DocumentTextIcon class="h-4 w-4 shrink-0 text-indigo-400" />
                                    <span class="font-medium text-slate-900 text-sm">{{ type.name }}</span>
                                </div>
                                <span class="ml-6 text-xs text-slate-400">{{ type.category }}</span>
                            </div>
                            <!-- Fee per page -->
                            <span class="sm:text-right text-sm font-mono font-semibold text-indigo-700">
                                {{ formatPeso(type.fee) }}
                            </span>
                            <!-- Pages -->
                            <span class="sm:text-center text-sm text-slate-600">
                                {{ type.default_page_count || 1 }} page{{
                                    (type.default_page_count || 1) !== 1 ? 's' : ''
                                }}
                            </span>
                            <!-- SLA -->
                            <span class="sm:text-center text-sm text-slate-600"> {{ type.processing_days }}d </span>
                            <!-- Status -->
                            <div class="sm:flex sm:justify-center">
                                <span
                                    :class="
                                        type.is_active
                                            ? 'bg-emerald-100 text-emerald-800'
                                            : 'bg-slate-100 text-slate-500'
                                    "
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold"
                                >
                                    {{ type.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-1">
                                <button
                                    type="button"
                                    :title="expandedId === type.id ? 'Close editor' : 'Edit'"
                                    :aria-label="
                                        expandedId === type.id ? `Close editor for ${type.name}` : `Edit ${type.name}`
                                    "
                                    :class="
                                        expandedId === type.id
                                            ? 'bg-indigo-100 text-indigo-700'
                                            : 'text-slate-400 hover:text-indigo-600 hover:bg-indigo-50'
                                    "
                                    class="min-h-11 min-w-11 rounded-md p-1.5 transition-colors"
                                    @click="toggleExpand(type.id)"
                                >
                                    <PencilIcon class="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    title="Delete / Disable"
                                    :aria-label="`Delete or disable ${type.name}`"
                                    class="min-h-11 min-w-11 rounded-md p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                    @click="destroyType(type.id, type.name)"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Inline edit panel -->
                        <div v-if="expandedId === type.id" class="border-b border-indigo-100 bg-indigo-50/30 px-4 py-5">
                            <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="update(type.id)">
                                <div class="flex flex-col gap-1">
                                    <label
                                        :for="`edit-document-type-name-${type.id}`"
                                        class="text-xs font-medium text-slate-600"
                                        >Name</label
                                    >
                                    <input
                                        :id="`edit-document-type-name-${type.id}`"
                                        v-model="updateForms[type.id].name"
                                        type="text"
                                        class="rounded-md border-slate-300 text-sm shadow-sm"
                                    />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label
                                        :for="`edit-document-type-category-${type.id}`"
                                        class="text-xs font-medium text-slate-600"
                                        >Category</label
                                    >
                                    <input
                                        :id="`edit-document-type-category-${type.id}`"
                                        v-model="updateForms[type.id].category"
                                        type="text"
                                        class="rounded-md border-slate-300 text-sm shadow-sm"
                                    />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label
                                        :for="`edit-document-type-fee-${type.id}`"
                                        class="text-xs font-medium text-slate-600"
                                        >Fee per Page (₱)</label
                                    >
                                    <input
                                        :id="`edit-document-type-fee-${type.id}`"
                                        v-model="updateForms[type.id].fee"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="rounded-md border-slate-300 text-sm shadow-sm"
                                    />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label
                                        :for="`edit-document-type-pages-${type.id}`"
                                        class="text-xs font-medium text-slate-600"
                                        >Pages per Document</label
                                    >
                                    <input
                                        :id="`edit-document-type-pages-${type.id}`"
                                        v-model="updateForms[type.id].default_page_count"
                                        type="number"
                                        min="1"
                                        max="500"
                                        class="rounded-md border-slate-300 text-sm shadow-sm"
                                    />
                                    <p class="text-xs text-slate-400">Total = fee × pages × copies.</p>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label
                                        :for="`edit-document-type-processing-days-${type.id}`"
                                        class="text-xs font-medium text-slate-600"
                                        >Processing Days</label
                                    >
                                    <input
                                        :id="`edit-document-type-processing-days-${type.id}`"
                                        v-model="updateForms[type.id].processing_days"
                                        type="number"
                                        min="1"
                                        max="365"
                                        class="rounded-md border-slate-300 text-sm shadow-sm"
                                    />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label
                                        :for="`edit-document-type-description-${type.id}`"
                                        class="text-xs font-medium text-slate-600"
                                        >Description</label
                                    >
                                    <input
                                        :id="`edit-document-type-description-${type.id}`"
                                        v-model="updateForms[type.id].description"
                                        type="text"
                                        class="rounded-md border-slate-300 text-sm shadow-sm"
                                    />
                                </div>

                                <div class="lg:col-span-3 flex items-center justify-between pt-1">
                                    <div class="flex items-center gap-4">
                                        <label
                                            class="inline-flex items-center gap-2 text-sm text-slate-700"
                                            :for="`edit-document-type-active-${type.id}`"
                                        >
                                            <input
                                                :id="`edit-document-type-active-${type.id}`"
                                                v-model="updateForms[type.id].is_active"
                                                type="checkbox"
                                                class="rounded border-slate-300 text-indigo-600 shadow-sm"
                                            />
                                            Active
                                        </label>
                                        <span class="text-xs text-slate-400"
                                            >{{ type.document_requests_count }} linked request(s)</span
                                        >
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="text-sm text-slate-500 hover:text-slate-700"
                                            @click="expandedId = null"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            type="submit"
                                            :disabled="updateForms[type.id].processing"
                                            class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                                            :aria-busy="updateForms[type.id].processing ? 'true' : undefined"
                                        >
                                            <CheckIcon class="h-3.5 w-3.5" />
                                            {{ updateForms[type.id].processing ? 'Saving…' : 'Save Changes' }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </template>
                </div>
            </section>
        </div>
    </StaffLayout>
</template>
