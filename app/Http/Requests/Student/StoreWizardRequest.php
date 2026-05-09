<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreWizardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'student';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'items.*.copies' => ['required', 'integer', 'min:1', 'max:20'],
            'purpose' => ['required', 'string', 'min:5', 'max:500'],
            'extra_data' => ['nullable', 'array'],
            'has_cno' => ['nullable', 'boolean'],
            'has_external_notice' => ['nullable', 'boolean'],
            'special_class_eligibility' => ['nullable', 'array'],
            'special_class_eligibility.graduating_this_term' => ['nullable', 'boolean'],
            'special_class_eligibility.subject_deficiency_certified' => ['nullable', 'boolean'],
            'special_class_eligibility.subject_conflict' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Please select at least one document type.',
            'items.*.document_type_id.required' => 'Each item must have a document type.',
            'items.*.document_type_id.exists' => 'One or more selected document types are invalid.',
            'items.*.copies.min' => 'Copies must be at least 1.',
            'items.*.copies.max' => 'Maximum 20 copies per document type.',
        ];
    }
}
