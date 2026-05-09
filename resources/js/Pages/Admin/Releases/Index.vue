<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import {
    BuildingOffice2Icon,
    CheckCircleIcon,
    DocumentMagnifyingGlassIcon,
    ExclamationTriangleIcon,
    ShieldCheckIcon,
    TicketIcon,
    UserGroupIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    slips: { type: Object, required: true },
    filters: { type: Object, required: true },
    releaseChannels: { type: Object, required: true },
});

const form = reactive({
    status: props.filters.status || 'ready',
    search: props.filters.search || '',
});

const apply = () => router.get(route('admin.releases.index'), form, { preserveState: true, replace: true });

const releaseSlipId = ref(null);
const voidSlipId = ref(null);

const releaseForm = useForm({
    claimant_name: '',
    claimant_id_reference: '',
    is_proxy_release: false,
    authorization_type: '',
    notes: '',
});

const voidForm = useForm({ reason: '' });

function startRelease(slip) {
    releaseSlipId.value = slip.id;
    releaseForm.reset();
    releaseForm.claimant_name = slip.user?.fullname || '';
}

function submitRelease(slip) {
    releaseForm.post(route('admin.releases.release', slip.id), {
        preserveScroll: true,
        onSuccess: () => {
            releaseSlipId.value = null;
            releaseForm.reset();
        },
    });
}

function startVoid(slip) {
    voidSlipId.value = slip.id;
    voidForm.reset();
}

function submitVoid(slip) {
    voidForm.post(route('admin.releases.void', slip.id), {
        preserveScroll: true,
        onSuccess: () => {
            voidSlipId.value = null;
            voidForm.reset();
        },
    });
}

function badge(state) {
    return (
        {
            ready: 'bg-amber-100 text-amber-800',
            released: 'bg-emerald-100 text-emerald-800',
            void: 'bg-slate-200 text-slate-600',
        }[state] ?? 'bg-slate-100 text-slate-600'
    );
}

function paginationLabel(label) {
    return label.replace('&laquo;', '‹').replace('&raquo;', '›');
}
</script>

<template>
    <Head title="Releases" />

    <StaffLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-display font-bold text-slate-900">Document Releases</h2>
                <p class="text-sm text-slate-500">
                    Verify the claimant, record release details, and close out claim slips.
                </p>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-5 px-4 pb-12 sm:px-6 lg:px-8">
            <!-- Tabs + search -->
            <div
                class="flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 md:flex-row md:items-center md:justify-between"
            >
                <div class="flex flex-wrap gap-1">
                    <button
                        v-for="tab in [
                            { key: 'ready', label: 'Ready for pickup' },
                            { key: 'released', label: 'Released' },
                            { key: 'void', label: 'Voided' },
                        ]"
                        :key="tab.key"
                        type="button"
                        :class="[
                            'rounded-full px-4 py-1.5 text-xs font-semibold transition',
                            form.status === tab.key
                                ? 'bg-brand-600 text-white shadow-sm'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
                        ]"
                        @click="
                            form.status = tab.key;
                            apply();
                        "
                    >
                        {{ tab.label }}
                    </button>
                </div>
                <div class="relative w-full md:w-80">
                    <DocumentMagnifyingGlassIcon
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    />
                    <input
                        v-model="form.search"
                        type="search"
                        placeholder="Search claim #, student, ref…"
                        class="block w-full rounded-lg border-slate-300 pl-9 text-sm"
                        @keyup.enter="apply"
                    />
                </div>
            </div>

            <!-- List -->
            <div class="space-y-4">
                <article
                    v-for="slip in slips.data"
                    :key="slip.id"
                    class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md"
                >
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="rounded-xl bg-brand-100 p-3 text-brand-700">
                                <TicketIcon class="h-6 w-6" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-mono text-sm font-bold text-slate-900">{{ slip.claim_number }}</p>
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                            badge(slip.state),
                                        ]"
                                    >
                                        {{ slip.state }}
                                    </span>
                                </div>
                                <p class="mt-1 font-display font-semibold text-slate-900">{{ slip.user?.fullname }}</p>
                                <p class="text-xs text-slate-500">
                                    <span class="font-mono">{{ slip.document_request?.reference_no }}</span> ·
                                    {{ slip.document_request?.document_type?.name }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    <BuildingOffice2Icon class="inline h-3.5 w-3.5" />
                                    {{ releaseChannels[slip.release_channel] ?? slip.release_channel }} · Claim
                                    {{
                                        new Date(slip.claim_date).toLocaleDateString('en-US', {
                                            month: 'short',
                                            day: 'numeric',
                                            year: 'numeric',
                                        })
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <Link
                                :href="route('admin.requests.show', slip.document_request_id)"
                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                View request
                            </Link>
                            <button
                                v-if="slip.state === 'ready'"
                                type="button"
                                class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500"
                                @click="startRelease(slip)"
                            >
                                <CheckCircleIcon class="h-4 w-4" /> Record release
                            </button>
                            <button
                                v-if="slip.state === 'ready'"
                                type="button"
                                class="inline-flex items-center gap-1 rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                                @click="startVoid(slip)"
                            >
                                <XCircleIcon class="h-4 w-4" /> Void slip
                            </button>
                        </div>
                    </div>

                    <!-- Released info -->
                    <div
                        v-if="slip.state === 'released'"
                        class="mt-4 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900 ring-1 ring-emerald-200"
                    >
                        <p>
                            Released to <strong>{{ slip.claimant_name }}</strong> ({{ slip.claimant_id_reference }})
                            <span v-if="slip.is_proxy_release">via proxy ({{ slip.authorization_type }})</span>
                            on
                            {{
                                new Date(slip.released_at).toLocaleString('en-US', {
                                    dateStyle: 'medium',
                                    timeStyle: 'short',
                                })
                            }}.
                        </p>
                        <p v-if="slip.notes" class="mt-1 text-xs italic">Notes: {{ slip.notes }}</p>
                    </div>

                    <!-- Release form -->
                    <form
                        v-if="releaseSlipId === slip.id"
                        class="mt-4 space-y-3 rounded-xl border border-emerald-200 bg-emerald-50/40 p-4"
                        @submit.prevent="submitRelease(slip)"
                    >
                        <div
                            class="flex items-start gap-2 rounded-lg bg-white p-3 text-xs text-slate-600 ring-1 ring-emerald-200"
                        >
                            <ShieldCheckIcon class="mt-0.5 h-4 w-4 flex-none text-emerald-700" />
                            <p>
                                Verify a valid government / school ID before recording. For proxy claimants, an SPA or
                                notarized authorization letter is required.
                            </p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-slate-700">Claimant full name</label>
                                <input
                                    v-model="releaseForm.claimant_name"
                                    type="text"
                                    required
                                    maxlength="120"
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700"
                                    >ID presented (type + number)</label
                                >
                                <input
                                    v-model="releaseForm.claimant_id_reference"
                                    type="text"
                                    required
                                    maxlength="120"
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm"
                                />
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input
                                v-model="releaseForm.is_proxy_release"
                                type="checkbox"
                                class="rounded border-slate-300"
                            />
                            Released via proxy / authorized representative
                        </label>
                        <div v-if="releaseForm.is_proxy_release">
                            <label class="block text-xs font-medium text-slate-700">Authorization type</label>
                            <select
                                v-model="releaseForm.authorization_type"
                                required
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm"
                            >
                                <option value="">Select…</option>
                                <option value="spa">Special Power of Attorney (SPA)</option>
                                <option value="authorization_letter">Notarized Authorization Letter</option>
                                <option value="parent">Parent / Guardian (with proof)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700">Notes (optional)</label>
                            <textarea
                                v-model="releaseForm.notes"
                                rows="2"
                                maxlength="500"
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm"
                            />
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="submit"
                                :disabled="releaseForm.processing"
                                class="rounded-md bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500 disabled:opacity-60"
                            >
                                Confirm release
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-slate-200 px-4 py-1.5 text-xs text-slate-700 hover:bg-slate-50"
                                @click="releaseSlipId = null"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>

                    <!-- Void form -->
                    <form
                        v-if="voidSlipId === slip.id"
                        class="mt-4 space-y-2 rounded-xl border border-rose-200 bg-rose-50/40 p-4"
                        @submit.prevent="submitVoid(slip)"
                    >
                        <div
                            class="flex items-start gap-2 rounded-lg bg-white p-3 text-xs text-slate-600 ring-1 ring-rose-200"
                        >
                            <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 flex-none text-rose-700" />
                            <p>
                                Voiding a claim slip prevents release but keeps the underlying request open. Use this
                                for lost slips or replacement.
                            </p>
                        </div>
                        <label class="block text-xs font-medium text-slate-700">Reason</label>
                        <textarea
                            v-model="voidForm.reason"
                            rows="2"
                            required
                            maxlength="200"
                            class="block w-full rounded-md border-slate-300 text-sm"
                        />
                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="rounded-md bg-rose-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-rose-500"
                            >
                                Void slip
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-slate-200 px-4 py-1.5 text-xs text-slate-700 hover:bg-slate-50"
                                @click="voidSlipId = null"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </article>

                <div
                    v-if="slips.data.length === 0"
                    class="rounded-2xl bg-white p-12 text-center shadow-sm ring-1 ring-slate-200"
                >
                    <UserGroupIcon class="mx-auto h-12 w-12 text-slate-300" />
                    <p class="mt-3 font-display font-semibold text-slate-700">No claim slips for this filter</p>
                    <p class="mt-1 text-xs text-slate-500">
                        Slips appear here once a request is marked ready for pickup.
                    </p>
                </div>
            </div>

            <nav
                v-if="slips.last_page > 1"
                class="flex items-center justify-between rounded-xl bg-white px-5 py-3 text-xs text-slate-600 shadow-sm ring-1 ring-slate-200"
            >
                <span>Showing {{ slips.from || 0 }}–{{ slips.to || 0 }} of {{ slips.total }}</span>
                <div class="flex flex-wrap gap-1">
                    <Link
                        v-for="link in slips.links"
                        :key="link.label"
                        :href="link.url || ''"
                        :class="[
                            'rounded-lg px-3 py-1.5 transition',
                            link.active ? 'bg-brand-600 text-white' : 'border border-slate-200 hover:bg-slate-50',
                            !link.url ? 'opacity-40 pointer-events-none' : '',
                        ]"
                        >{{ paginationLabel(link.label) }}</Link
                    >
                </div>
            </nav>
        </div>
    </StaffLayout>
</template>
