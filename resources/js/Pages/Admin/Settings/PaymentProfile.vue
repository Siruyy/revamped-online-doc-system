<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import FormField from '@/Components/UI/FormField.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    BanknotesIcon,
    CheckCircleIcon,
    PencilSquareIcon,
    PlusIcon,
    QrCodeIcon,
    TrashIcon,
    XCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    profiles: { type: Array, default: () => [] },
});

// ─── Create form ─────────────────────────────────────────────────────────────
const showCreate = ref(false);

const createForm = useForm({
    bank_name: '',
    account_name: '',
    account_number: '',
    instructions: '',
    is_active: true,
    qr_image: null,
});

const createQrPreview = ref(null);

function onCreateQrChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    createForm.qr_image = file;
    const reader = new FileReader();
    reader.onload = (ev) => {
        createQrPreview.value = ev.target.result;
    };
    reader.readAsDataURL(file);
}

function submitCreate() {
    createForm.post(route('admin.settings.payment-profile.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showCreate.value = false;
            createForm.reset();
            createQrPreview.value = null;
        },
    });
}

// ─── Per-profile edit ─────────────────────────────────────────────────────────
const editingId = ref(null);

const editForms = {};

function getEditForm(profile) {
    if (!editForms[profile.id]) {
        editForms[profile.id] = useForm({
            bank_name: profile.bank_name ?? '',
            account_name: profile.account_name ?? '',
            account_number: profile.account_number ?? '',
            instructions: profile.instructions ?? '',
            is_active: profile.is_active,
            qr_image: null,
        });
    }
    return editForms[profile.id];
}

function openEdit(profile) {
    editingId.value = profile.id;
    const form = getEditForm(profile);
    form.bank_name = profile.bank_name ?? '';
    form.account_name = profile.account_name ?? '';
    form.account_number = profile.account_number ?? '';
    form.instructions = profile.instructions ?? '';
    form.is_active = profile.is_active;
    form.qr_image = null;
}

const editQrPreviews = ref({});

function onEditQrChange(profileId, e) {
    const file = e.target.files?.[0];
    if (!file) return;
    editForms[profileId].qr_image = file;
    const reader = new FileReader();
    reader.onload = (ev) => {
        editQrPreviews.value[profileId] = ev.target.result;
    };
    reader.readAsDataURL(file);
}

function submitEdit(profileId) {
    const form = editForms[profileId];
    form.patch(route('admin.settings.payment-profile.update', profileId), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            delete editQrPreviews.value[profileId];
        },
    });
}

function toggleActive(profile) {
    router.patch(route('admin.settings.payment-profile.toggle', profile.id), {}, { preserveScroll: true });
}

function deleteProfile(profile) {
    if (!window.confirm(`Delete "${profile.bank_name}" profile? This cannot be undone.`)) return;
    router.delete(route('admin.settings.payment-profile.destroy', profile.id), { preserveScroll: true });
}

function removeQr(profile) {
    if (!window.confirm('Remove the QR code image?')) return;
    router.delete(route('admin.settings.payment-profile.remove-qr', profile.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Payment Profile Settings" />

    <StaffLayout>
        <template #header>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Settings</p>
                <h2 class="mt-1 text-2xl font-display font-bold text-slate-900">School Payment Profiles</h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    Configure one or more payment channels. All active profiles are shown to students when they pay.
                </p>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-6 px-4 pb-12 sm:px-6 lg:px-8">
            <!-- Existing Profiles -->
            <div v-if="profiles.length" class="space-y-4">
                <div
                    v-for="profile in profiles"
                    :key="profile.id"
                    class="rounded-2xl bg-white shadow-sm ring-1"
                    :class="profile.is_active ? 'ring-brand-300' : 'ring-slate-200'"
                >
                    <!-- Profile header bar -->
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b"
                        :class="profile.is_active ? 'border-brand-100 bg-brand-50/40' : 'border-slate-100'"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                :class="profile.is_active ? 'bg-brand-600' : 'bg-slate-400'"
                                class="flex h-9 w-9 items-center justify-center rounded-xl text-white shadow-sm"
                            >
                                <BanknotesIcon class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">
                                    {{ profile.bank_name || 'Unnamed Profile' }}
                                </p>
                                <p class="text-xs text-slate-500">{{ profile.account_name }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Active badge / toggle -->
                            <button
                                type="button"
                                :title="profile.is_active ? 'Deactivate' : 'Activate'"
                                :class="
                                    profile.is_active
                                        ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                "
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition-colors"
                                :aria-label="
                                    profile.is_active
                                        ? `Deactivate ${profile.bank_name}`
                                        : `Activate ${profile.bank_name}`
                                "
                                @click="toggleActive(profile)"
                            >
                                <CheckCircleIcon v-if="profile.is_active" class="h-3.5 w-3.5" />
                                <XCircleIcon v-else class="h-3.5 w-3.5" />
                                {{ profile.is_active ? 'Active' : 'Inactive' }}
                            </button>
                            <!-- Edit button -->
                            <button
                                type="button"
                                :class="
                                    editingId === profile.id
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : 'text-slate-500 hover:bg-slate-100'
                                "
                                class="min-h-11 min-w-11 rounded-lg p-2 transition-colors"
                                :title="editingId === profile.id ? 'Cancel edit' : 'Edit'"
                                :aria-label="
                                    editingId === profile.id
                                        ? `Cancel editing ${profile.bank_name}`
                                        : `Edit ${profile.bank_name}`
                                "
                                @click="editingId === profile.id ? (editingId = null) : openEdit(profile)"
                            >
                                <PencilSquareIcon v-if="editingId !== profile.id" class="h-4 w-4" />
                                <XMarkIcon v-else class="h-4 w-4" />
                            </button>
                            <!-- Delete button -->
                            <button
                                type="button"
                                title="Delete profile"
                                :aria-label="`Delete ${profile.bank_name}`"
                                class="min-h-11 min-w-11 rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-colors"
                                @click="deleteProfile(profile)"
                            >
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Preview row -->
                    <div v-if="editingId !== profile.id" class="grid gap-4 px-5 py-4 sm:grid-cols-3">
                        <dl class="space-y-2 text-sm sm:col-span-2">
                            <div class="flex gap-3">
                                <dt class="w-32 shrink-0 text-xs text-slate-500">Account Number</dt>
                                <dd class="font-mono font-bold text-brand-700 select-all">
                                    {{ profile.account_number || '—' }}
                                </dd>
                            </div>
                            <div v-if="profile.instructions" class="flex gap-3">
                                <dt class="w-32 shrink-0 text-xs text-slate-500">Instructions</dt>
                                <dd class="text-slate-700 text-xs whitespace-pre-line leading-relaxed line-clamp-3">
                                    {{ profile.instructions }}
                                </dd>
                            </div>
                        </dl>
                        <!-- QR thumbnail -->
                        <div class="flex flex-col items-center gap-2">
                            <div
                                class="text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-1.5"
                            >
                                <QrCodeIcon class="h-4 w-4" />
                                QR Code
                            </div>
                            <div v-if="profile.qr_url" class="relative group">
                                <img
                                    :src="profile.qr_url"
                                    alt="QR"
                                    class="h-24 w-24 rounded-lg object-contain ring-1 ring-brand-200"
                                />
                                <button
                                    type="button"
                                    class="absolute -top-3 -right-3 flex min-h-11 min-w-11 items-center justify-center rounded-full bg-rose-600 text-white shadow opacity-0 transition-opacity group-hover:opacity-100 focus:opacity-100"
                                    title="Remove QR"
                                    :aria-label="`Remove QR code for ${profile.bank_name}`"
                                    @click="removeQr(profile)"
                                >
                                    <XMarkIcon class="h-3 w-3" />
                                </button>
                            </div>
                            <div
                                v-else
                                class="flex h-24 w-24 flex-col items-center justify-center rounded-lg bg-slate-100 text-slate-400 gap-1"
                            >
                                <QrCodeIcon class="h-7 w-7" />
                                <span class="text-xs">Not set</span>
                            </div>
                        </div>
                    </div>

                    <!-- Inline edit form -->
                    <div v-if="editingId === profile.id" class="px-5 py-5">
                        <form class="space-y-5" @submit.prevent="submitEdit(profile.id)">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <FormField
                                    :id="`edit-bank-name-${profile.id}`"
                                    label="Bank Name"
                                    :error="getEditForm(profile).errors.bank_name"
                                    required
                                >
                                    <template #default="{ id, describedBy, invalid }">
                                        <input
                                            :id="id"
                                            v-model="getEditForm(profile).bank_name"
                                            type="text"
                                            required
                                            maxlength="120"
                                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm sm:text-sm"
                                            :aria-describedby="describedBy"
                                            :aria-invalid="invalid ? 'true' : undefined"
                                        />
                                    </template>
                                </FormField>
                                <FormField
                                    :id="`edit-account-name-${profile.id}`"
                                    label="Account Name"
                                    :error="getEditForm(profile).errors.account_name"
                                    required
                                >
                                    <template #default="{ id, describedBy, invalid }">
                                        <input
                                            :id="id"
                                            v-model="getEditForm(profile).account_name"
                                            type="text"
                                            required
                                            maxlength="180"
                                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm sm:text-sm"
                                            :aria-describedby="describedBy"
                                            :aria-invalid="invalid ? 'true' : undefined"
                                        />
                                    </template>
                                </FormField>
                                <FormField
                                    :id="`edit-account-number-${profile.id}`"
                                    label="Account Number"
                                    :error="getEditForm(profile).errors.account_number"
                                    required
                                >
                                    <template #default="{ id, describedBy, invalid }">
                                        <input
                                            :id="id"
                                            v-model="getEditForm(profile).account_number"
                                            type="text"
                                            required
                                            maxlength="60"
                                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm font-mono tracking-wide sm:text-sm"
                                            :aria-describedby="describedBy"
                                            :aria-invalid="invalid ? 'true' : undefined"
                                        />
                                    </template>
                                </FormField>
                                <FormField
                                    :id="`edit-qr-image-${profile.id}`"
                                    label="QR Code"
                                    :error="getEditForm(profile).errors.qr_image"
                                    help="JPG/PNG/WebP - max 4 MB."
                                >
                                    <template #default="{ id, describedBy, invalid }">
                                        <input
                                            :id="id"
                                            type="file"
                                            accept="image/jpeg,image/png,image/gif,image/webp"
                                            class="mt-1 block w-full rounded-lg border border-slate-300 text-sm text-slate-700 file:mr-4 file:min-h-11 file:border-0 file:bg-brand-600 file:px-4 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                                            :aria-describedby="describedBy"
                                            :aria-invalid="invalid ? 'true' : undefined"
                                            @change="onEditQrChange(profile.id, $event)"
                                        />
                                        <p v-if="getEditForm(profile).qr_image" class="mt-2 text-xs text-brand-700">
                                            Selected: {{ getEditForm(profile).qr_image.name }}
                                        </p>
                                    </template>
                                </FormField>
                                <div v-if="editQrPreviews[profile.id]" class="mt-2 flex items-center gap-2">
                                    <img
                                        :src="editQrPreviews[profile.id]"
                                        class="h-14 w-14 rounded object-contain ring-1 ring-slate-200"
                                    />
                                    <span class="text-xs text-slate-500">New QR preview</span>
                                </div>
                            </div>
                            <div>
                                <label
                                    :for="`edit-instructions-${profile.id}`"
                                    class="block text-sm font-medium text-slate-700"
                                    >Payment Instructions
                                    <span class="text-slate-400 font-normal text-xs">(shown to students)</span></label
                                >
                                <textarea
                                    :id="`edit-instructions-${profile.id}`"
                                    v-model="getEditForm(profile).instructions"
                                    rows="4"
                                    maxlength="2000"
                                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm sm:text-sm"
                                ></textarea>
                                <p class="mt-1 text-xs text-slate-400">
                                    {{ (getEditForm(profile).instructions || '').length }} / 2000
                                </p>
                            </div>
                            <div class="flex items-center justify-between">
                                <label
                                    class="inline-flex items-center gap-2 text-sm text-slate-700"
                                    :for="`edit-is-active-${profile.id}`"
                                >
                                    <input
                                        :id="`edit-is-active-${profile.id}`"
                                        v-model="getEditForm(profile).is_active"
                                        type="checkbox"
                                        class="rounded border-slate-300 text-brand-600 shadow-sm"
                                    />
                                    Show to students (active)
                                </label>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="text-sm text-slate-500 hover:text-slate-700"
                                        @click="editingId = null"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="getEditForm(profile).processing"
                                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-brand-500 disabled:opacity-60"
                                        :aria-busy="getEditForm(profile).processing ? 'true' : undefined"
                                    >
                                        <CheckCircleIcon class="h-4 w-4" />
                                        {{ getEditForm(profile).processing ? 'Saving…' : 'Save Changes' }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                <p class="font-semibold">No payment profiles configured yet.</p>
                <p>Students won't see payment details until you add a profile below.</p>
            </div>

            <!-- ── Add New Profile ─────────────────────────── -->
            <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <button
                    type="button"
                    class="flex w-full items-center justify-between px-5 py-4 text-left"
                    @click="showCreate = !showCreate"
                >
                    <span class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <PlusIcon class="h-4 w-4 text-brand-600" />
                        Add New Payment Profile
                    </span>
                    <span :class="showCreate ? 'rotate-180' : ''" class="transition-transform text-slate-400 text-xs"
                        >▼</span
                    >
                </button>

                <div v-if="showCreate" class="border-t border-slate-100 px-5 py-5">
                    <form class="space-y-5" @submit.prevent="submitCreate">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormField
                                id="create-bank-name"
                                label="Bank Name"
                                :error="createForm.errors.bank_name"
                                required
                            >
                                <template #default="{ id, describedBy, invalid }">
                                    <input
                                        :id="id"
                                        v-model="createForm.bank_name"
                                        type="text"
                                        required
                                        maxlength="120"
                                        placeholder="e.g. BDO Unibank"
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm sm:text-sm"
                                        :class="{ 'border-rose-400': createForm.errors.bank_name }"
                                        :aria-describedby="describedBy"
                                        :aria-invalid="invalid ? 'true' : undefined"
                                    />
                                </template>
                            </FormField>
                            <FormField
                                id="create-account-name"
                                label="Account Name"
                                :error="createForm.errors.account_name"
                                required
                            >
                                <template #default="{ id, describedBy, invalid }">
                                    <input
                                        :id="id"
                                        v-model="createForm.account_name"
                                        type="text"
                                        required
                                        maxlength="180"
                                        placeholder="e.g. St. Vincent College"
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm sm:text-sm"
                                        :class="{ 'border-rose-400': createForm.errors.account_name }"
                                        :aria-describedby="describedBy"
                                        :aria-invalid="invalid ? 'true' : undefined"
                                    />
                                </template>
                            </FormField>
                            <FormField
                                id="create-account-number"
                                label="Account Number"
                                :error="createForm.errors.account_number"
                                required
                            >
                                <template #default="{ id, describedBy, invalid }">
                                    <input
                                        :id="id"
                                        v-model="createForm.account_number"
                                        type="text"
                                        required
                                        maxlength="60"
                                        placeholder="001-234-567-890"
                                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm font-mono tracking-wide sm:text-sm"
                                        :class="{ 'border-rose-400': createForm.errors.account_number }"
                                        :aria-describedby="describedBy"
                                        :aria-invalid="invalid ? 'true' : undefined"
                                    />
                                </template>
                            </FormField>
                            <FormField
                                id="create-qr-image"
                                label="QR Code"
                                :error="createForm.errors.qr_image"
                                help="Optional - JPG/PNG/WebP, max 4 MB."
                            >
                                <template #default="{ id, describedBy, invalid }">
                                    <input
                                        :id="id"
                                        type="file"
                                        accept="image/jpeg,image/png,image/gif,image/webp"
                                        class="mt-1 block w-full rounded-lg border border-slate-300 text-sm text-slate-700 file:mr-4 file:min-h-11 file:border-0 file:bg-brand-600 file:px-4 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                                        :aria-describedby="describedBy"
                                        :aria-invalid="invalid ? 'true' : undefined"
                                        @change="onCreateQrChange"
                                    />
                                    <p v-if="createForm.qr_image" class="mt-2 text-xs text-brand-700">
                                        Selected: {{ createForm.qr_image.name }}
                                    </p>
                                </template>
                            </FormField>
                            <div v-if="createQrPreview" class="mt-2 flex items-center gap-2">
                                <img
                                    :src="createQrPreview"
                                    class="h-14 w-14 rounded object-contain ring-1 ring-slate-200"
                                />
                                <span class="text-xs text-slate-500">QR preview</span>
                            </div>
                        </div>
                        <div>
                            <label for="create-instructions" class="block text-sm font-medium text-slate-700"
                                >Payment Instructions
                                <span class="text-slate-400 font-normal text-xs">(shown to students)</span></label
                            >
                            <textarea
                                id="create-instructions"
                                v-model="createForm.instructions"
                                rows="4"
                                maxlength="2000"
                                placeholder="Step-by-step instructions on how students should pay and upload their receipt…"
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm sm:text-sm"
                            ></textarea>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ (createForm.instructions || '').length }} / 2000
                            </p>
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700" for="create-is-active">
                                <input
                                    id="create-is-active"
                                    v-model="createForm.is_active"
                                    type="checkbox"
                                    class="rounded border-slate-300 text-brand-600 shadow-sm"
                                />
                                Active (visible to students immediately)
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
                                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-brand-500 disabled:opacity-60"
                                    :aria-busy="createForm.processing ? 'true' : undefined"
                                >
                                    <PlusIcon class="h-4 w-4" />
                                    {{ createForm.processing ? 'Creating…' : 'Create Profile' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </StaffLayout>
</template>
