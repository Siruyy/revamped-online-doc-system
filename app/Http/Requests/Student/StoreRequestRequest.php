<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'student';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_ids' => ['required', 'array', 'min:1', 'max:5'],
            'document_ids.*' => ['integer', 'distinct', 'exists:document_types,id'],
            'purpose' => ['nullable', 'string', 'max:500'],
        ];
    }
}
