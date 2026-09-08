<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import FormField from '@/Components/UI/FormField.vue';

const props = defineProps({ logoUrl: { type: String, default: null } });
const page = usePage();
const prefix = computed(() => (page.props.auth.user.role === 'superadmin' ? 'superadmin' : 'admin'));
const form = useForm({ logo: null });
const removal = useForm({});
const preview = ref(null);
const input = ref(null);
let previewReader;

function clearSelection() {
    previewReader?.abort();
    preview.value = null;
    form.reset();
    if (input.value) input.value.value = '';
}

function selectLogo(event) {
    const file = event.target.files?.[0];
    previewReader?.abort();
    preview.value = null;
    form.logo = file ?? null;
    if (file && file.size <= 2 * 1024 * 1024 && ['image/png', 'image/jpeg'].includes(file.type)) {
        previewReader = new FileReader();
        previewReader.onload = () => {
            preview.value = previewReader.result;
        };
        previewReader.readAsDataURL(file);
    }
}

function save() {
    form.post(route(`${prefix.value}.settings.branding.update`), {
        preserveScroll: true,
        onSuccess: clearSelection,
    });
}

function remove() {
    if (!window.confirm('Remove the uploaded logo? Existing claim-slip PDFs will keep their current logo.')) return;
    removal.delete(route(`${prefix.value}.settings.branding.destroy`), {
        preserveScroll: true,
        onSuccess: clearSelection,
    });
}

onBeforeUnmount(() => {
    previewReader?.abort();
});
</script>

<template>
    <Head title="School Branding" />
    <StaffLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <PageHeader
                title="School Branding"
                subtitle="Choose the logo used across the portal, new claim slips, and their email attachments."
            />
            <section class="grid gap-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 md:grid-cols-2">
                <form class="space-y-5" @submit.prevent="save">
                    <FormField
                        id="school-logo"
                        label="School logo"
                        :error="form.errors.logo"
                        help="PNG or JPG, up to 2 MB and 3000 × 3000 pixels. A transparent PNG works best."
                        required
                    >
                        <template #default="{ id, describedBy, invalid }">
                            <input
                                :id="id"
                                ref="input"
                                type="file"
                                accept="image/png,image/jpeg"
                                required
                                :aria-describedby="describedBy"
                                :aria-invalid="invalid ? 'true' : undefined"
                                class="block w-full text-sm file:mr-3 file:min-h-11 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:text-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600"
                                @change="selectLogo"
                            />
                        </template>
                    </FormField>
                    <p class="text-sm leading-6 text-slate-600">
                        The logo appears on the public page and staff layouts immediately after saving. Previously
                        issued PDFs and sent attachments stay unchanged.
                    </p>
                    <p v-if="form.progress" role="status" class="text-sm text-brand-700">
                        Uploading: {{ form.progress.percentage }}%
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <button
                            type="submit"
                            :disabled="!form.logo || form.processing || removal.processing"
                            class="min-h-11 rounded-xl bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-500 disabled:opacity-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                        >
                            {{ form.processing ? 'Saving…' : 'Save logo' }}
                        </button>
                        <button
                            v-if="props.logoUrl"
                            type="button"
                            :disabled="form.processing || removal.processing"
                            class="min-h-11 rounded-xl border border-slate-300 px-4 text-sm text-slate-700 disabled:opacity-50"
                            @click="remove"
                        >
                            {{ removal.processing ? 'Removing…' : 'Remove uploaded logo' }}
                        </button>
                    </div>
                </form>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 text-center">
                    <p class="mb-5 text-xs font-semibold uppercase tracking-widest text-slate-500">
                        {{ preview ? 'Selected logo preview' : 'Current logo' }}
                    </p>
                    <img
                        v-if="preview || props.logoUrl"
                        :src="preview || props.logoUrl"
                        alt="School logo for claim slips"
                        class="mx-auto h-24 w-40 object-contain"
                    />
                    <p v-else class="text-sm text-slate-500">No uploaded logo. The default school mark is used.</p>
                    <p class="mt-5 font-display text-lg font-bold text-brand-900">St. Vincent College Incorporated</p>
                    <p class="mt-2 border-t border-slate-300 pt-3 text-sm font-semibold tracking-wide text-slate-700">
                        DOCUMENT CLAIM SLIP
                    </p>
                </div>
            </section>
        </div>
    </StaffLayout>
</template>
