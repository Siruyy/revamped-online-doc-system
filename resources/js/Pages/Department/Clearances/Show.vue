<script setup>
import InputError from '@/Components/InputError.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    clearance: { type: Object, required: true },
    department: { type: String, required: true },
});

const page = usePage();
const banner = computed(() => page.props.flash?.banner ?? null);
const deptStatusKey = computed(() => `${props.department}_status`);
const deptRemarksKey = computed(() => `${props.department}_remarks`);
const canAct = computed(() => props.clearance[deptStatusKey.value] === 'pending');

const signForm = useForm({ remarks: '' });
const denyForm = useForm({ remarks: '' });

const submitSign = () => signForm.post(route('department.clearances.sign', props.clearance.id));
const submitDeny = () => denyForm.post(route('department.clearances.deny', props.clearance.id));

const statusTone = (status) => {
    if (['cleared', 'completed', 'approved'].includes(status)) return 'success';
    if (['denied', 'rejected'].includes(status)) return 'danger';
    if (['pending', 'in_progress'].includes(status)) return 'warning';

    return 'neutral';
};

const statusLabel = (status) => status?.replaceAll('_', ' ') || 'N/A';
</script>

<template>
    <Head title="Clearance detail" />

    <StaffLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">
                Clearance {{ clearance.document_request?.reference_no }}
            </h2>
        </template>

        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div
                v-if="banner"
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
            >
                {{ banner }}
            </div>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Student</h3>
                <p class="mt-2 text-sm text-slate-700">{{ clearance.user?.fullname }} ({{ clearance.user?.email }})</p>
                <p class="text-sm text-slate-700">
                    Course: {{ clearance.user?.course }} · Year {{ clearance.user?.year_level }}
                </p>
                <p class="text-sm text-slate-700">Student ID: {{ clearance.user?.student_id }}</p>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Request</h3>
                <p class="mt-2 text-sm text-slate-700">Reference: {{ clearance.document_request?.reference_no }}</p>
                <p class="text-sm text-slate-700">Purpose: {{ clearance.document_request?.purpose || '—' }}</p>
                <p v-if="clearance.uploaded_file_path" class="mt-2 text-sm">
                    <a
                        :href="route('files.clearance-supporting', clearance.id)"
                        class="font-semibold text-indigo-600 hover:text-indigo-500"
                    >
                        Download supporting file
                    </a>
                </p>
            </section>

            <section class="grid gap-4 md:grid-cols-2">
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-500">Teacher</p>
                    <StatusBadge
                        class="mt-2"
                        :tone="statusTone(clearance.teacher_status)"
                        :label="statusLabel(clearance.teacher_status)"
                    />
                    <p v-if="clearance.teacher_remarks" class="text-xs text-rose-600">
                        {{ clearance.teacher_remarks }}
                    </p>
                </article>
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-500">Dean</p>
                    <StatusBadge
                        class="mt-2"
                        :tone="statusTone(clearance.dean_status)"
                        :label="statusLabel(clearance.dean_status)"
                    />
                    <p v-if="clearance.dean_remarks" class="text-xs text-rose-600">{{ clearance.dean_remarks }}</p>
                </article>
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-500">Accounting</p>
                    <StatusBadge
                        class="mt-2"
                        :tone="statusTone(clearance.accounting_status)"
                        :label="statusLabel(clearance.accounting_status)"
                    />
                    <p v-if="clearance.accounting_remarks" class="text-xs text-rose-600">
                        {{ clearance.accounting_remarks }}
                    </p>
                </article>
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-500">SAO</p>
                    <StatusBadge
                        class="mt-2"
                        :tone="statusTone(clearance.sao_status)"
                        :label="statusLabel(clearance.sao_status)"
                    />
                    <p v-if="clearance.sao_remarks" class="text-xs text-rose-600">{{ clearance.sao_remarks }}</p>
                </article>
            </section>

            <section v-if="canAct" class="space-y-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">
                    Your department ({{ department }})
                </h3>
                <p class="text-sm text-slate-600">
                    Current status:
                    <StatusBadge
                        :tone="statusTone(clearance[deptStatusKey])"
                        :label="statusLabel(clearance[deptStatusKey])"
                    />
                </p>
                <p v-if="clearance[deptRemarksKey]" class="text-xs text-slate-500">
                    Remarks: {{ clearance[deptRemarksKey] }}
                </p>

                <form class="space-y-2 border-t border-slate-100 pt-4" @submit.prevent="submitSign">
                    <label class="text-xs font-semibold uppercase text-slate-500"
                        >Mark cleared (optional remarks)</label
                    >
                    <textarea
                        v-model="signForm.remarks"
                        rows="2"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm"
                    />
                    <InputError :message="signForm.errors.sign" />
                    <InputError :message="signForm.errors.remarks" />
                    <button
                        type="submit"
                        class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50"
                        :disabled="signForm.processing"
                    >
                        Mark as cleared
                    </button>
                </form>

                <form class="space-y-2 border-t border-slate-100 pt-4" @submit.prevent="submitDeny">
                    <label class="text-xs font-semibold uppercase text-slate-500"
                        >Deny (remarks required, min 10 characters)</label
                    >
                    <textarea
                        v-model="denyForm.remarks"
                        rows="2"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm"
                    />
                    <InputError :message="denyForm.errors.deny" />
                    <InputError :message="denyForm.errors.remarks" />
                    <button
                        type="submit"
                        class="rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500 disabled:opacity-50"
                        :disabled="denyForm.processing"
                    >
                        Deny with remarks
                    </button>
                </form>
            </section>

            <section v-else class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                You cannot act on this clearance for your department in its current state.
            </section>
        </div>
    </StaffLayout>
</template>
