<?php

namespace App\Http\Requests\Public;

use App\Models\DocumentType;
use App\Support\FileUploadLimits;
use App\Support\PublicRequestOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePublicDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $maxFileKilobytes = FileUploadLimits::publicIntakeMaxFileKilobytes();

        return [
            'requester_name' => ['required', 'string', 'max:150'],
            'requester_email' => ['required', 'email', 'max:150'],
            'requester_contact_number' => ['required', 'string', 'max:30'],
            'requester_student_id' => ['nullable', 'string', 'max:50'],
            'requester_division' => ['required', Rule::in(array_keys(PublicRequestOptions::DIVISIONS))],
            'basic_education_level' => ['nullable', 'required_if:requester_division,basic_education', Rule::in(array_keys(PublicRequestOptions::BASIC_EDUCATION_LEVELS))],
            'academic_program_id' => [
                'nullable',
                'required_if:requester_division,college',
                'integer',
                Rule::exists('academic_programs', 'id')->where('is_active', true),
            ],
            'requester_year_level' => ['nullable', 'required_if:requester_division,college', 'integer', 'min:1', 'max:8'],
            'requester_last_term_attended' => ['required', Rule::in(array_keys(PublicRequestOptions::TERMS))],
            'requester_last_year_attended' => ['required', Rule::in(PublicRequestOptions::academicYears())],
            'birth_date' => ['required', 'date', 'before:today'],
            'birth_place' => ['required', 'string', 'max:150'],
            'sex' => ['required', 'in:Female,Male,Prefer not to say'],
            'civil_status' => ['required', 'in:Single,Married,Widowed,Separated'],
            'citizenship' => ['required', 'string', 'max:80'],
            'home_address' => ['required', 'string', 'max:500'],
            'father_name' => ['nullable', 'string', 'max:150'],
            'mother_maiden_name' => ['nullable', 'string', 'max:150'],
            'parents_address' => ['nullable', 'string', 'max:500'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_address' => ['nullable', 'string', 'max:500'],
            'education' => ['required', 'array'],
            'education.elementary.school' => ['nullable', 'string', 'max:180'],
            'education.elementary.address' => ['nullable', 'string', 'max:250'],
            'education.elementary.year' => ['nullable', 'string', 'max:20'],
            'education.junior_high.school' => ['nullable', 'string', 'max:180'],
            'education.junior_high.address' => ['nullable', 'string', 'max:250'],
            'education.junior_high.year' => ['nullable', 'string', 'max:20'],
            'education.senior_high.school' => ['nullable', 'string', 'max:180'],
            'education.senior_high.address' => ['nullable', 'string', 'max:250'],
            'education.senior_high.year' => ['nullable', 'string', 'max:20'],
            'employment_status' => ['required', 'in:employed,not_employed,self_employed'],
            'company_name' => ['nullable', 'required_if:employment_status,employed,self_employed', 'string', 'max:180'],
            'company_address' => ['nullable', 'required_if:employment_status,employed,self_employed', 'string', 'max:250'],
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'items.*.copies' => ['required', 'integer', 'min:1', 'max:20'],
            'items.*.authentication_requested' => ['sometimes', 'boolean'],
            'items.*.documentary_stamp_requested' => ['sometimes', 'boolean'],
            'items.*.semester_requested' => ['nullable', 'string', 'max:100'],
            'purpose' => ['required', Rule::in(PublicRequestOptions::PURPOSES)],
            'purpose_other' => ['nullable', 'required_if:purpose,Other official purpose', 'string', 'min:3', 'max:300'],
            'fulfillment_method' => ['required', 'in:pickup,delivery'],
            'delivery_address' => ['nullable', 'required_if:fulfillment_method,delivery', 'string', 'max:500'],
            'is_proxy_request' => ['sometimes', 'boolean'],
            'requirements' => ['nullable', 'array'],
            'requirements.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:'.$maxFileKilobytes],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = (array) $this->input('items', []);
            $documentTypeIds = collect($items)
                ->pluck('document_type_id')
                ->filter()
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values();

            if ($documentTypeIds->isEmpty()) {
                return;
            }

            $documentTypes = DocumentType::query()
                ->whereIn('id', $documentTypeIds)
                ->where('is_active', true)
                ->get(['id', 'category', 'requirements']);

            if ($documentTypes->count() !== $documentTypeIds->count()) {
                $validator->errors()->add('items', 'One or more selected document types are inactive or unavailable.');

                return;
            }

            $expectsBasicEducation = $this->input('requester_division') === 'basic_education';
            $hasWrongDivision = $documentTypes->contains(
                fn (DocumentType $type): bool => ($type->category === 'BasicEd') !== $expectsBasicEducation,
            );

            if ($hasWrongDivision) {
                $validator->errors()->add('items', 'Select documents that match the chosen school division.');

                return;
            }

            $requiredEducation = match ($this->input('basic_education_level')) {
                'elementary' => ['elementary'],
                'junior_high' => ['elementary', 'junior_high'],
                default => ['elementary', 'junior_high', 'senior_high'],
            };

            foreach ($requiredEducation as $level) {
                foreach (['school', 'address', 'year'] as $field) {
                    if (blank(data_get($this->input('education', []), "{$level}.{$field}"))) {
                        $validator->errors()->add(
                            "education.{$level}.{$field}",
                            'This education detail is required.',
                        );
                    }
                }
            }

            $requiredKeys = $documentTypes
                ->flatMap(fn (DocumentType $type): array => (array) $type->requirements)
                ->merge(['photo_2x2', 'psa_birth_certificate'])
                ->when($this->input('civil_status') === 'Married', fn ($keys) => $keys->push('marriage_certificate'))
                ->when($this->boolean('is_proxy_request'), fn ($keys) => $keys->push('authorization_letter')->push('spa'))
                ->filter()
                ->unique()
                ->values();

            foreach ($requiredKeys as $key) {
                if (! $this->hasFile("requirements.{$key}")) {
                    $validator->errors()->add("requirements.{$key}", 'This requirement file is required.');
                }
            }

            foreach ((array) $this->input('items', []) as $index => $item) {
                $type = $documentTypes->firstWhere('id', (int) ($item['document_type_id'] ?? 0));

                if ($type?->code === 'cert_enrollment' && blank($item['semester_requested'] ?? null)) {
                    $validator->errors()->add("items.{$index}.semester_requested", 'The semester is required for a Certificate of Enrollment.');
                }
            }
        });
    }
}
