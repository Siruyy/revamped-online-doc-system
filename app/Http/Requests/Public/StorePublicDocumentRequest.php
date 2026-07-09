<?php

namespace App\Http\Requests\Public;

use App\Models\DocumentType;
use App\Support\FileUploadLimits;
use Illuminate\Foundation\Http\FormRequest;
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
            'requester_course' => ['required', 'string', 'max:100'],
            'requester_year_level' => ['required', 'integer', 'min:1', 'max:8'],
            'requester_graduation_or_last_sem' => ['required', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'items.*.copies' => ['required', 'integer', 'min:1', 'max:20'],
            'purpose' => ['required', 'string', 'min:5', 'max:500'],
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_reference_number' => ['nullable', 'string', 'max:100'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:'.$maxFileKilobytes],
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
                ->get(['id', 'requirements']);

            if ($documentTypes->count() !== $documentTypeIds->count()) {
                $validator->errors()->add('items', 'One or more selected document types are inactive or unavailable.');

                return;
            }

            $requiredKeys = $documentTypes
                ->flatMap(fn (DocumentType $type): array => (array) $type->requirements)
                ->filter()
                ->unique()
                ->values();

            foreach ($requiredKeys as $key) {
                if (! $this->hasFile("requirements.{$key}")) {
                    $validator->errors()->add("requirements.{$key}", 'This requirement file is required.');
                }
            }
        });
    }
}
