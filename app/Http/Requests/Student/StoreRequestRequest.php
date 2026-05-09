<?php

namespace App\Http\Requests\Student;

use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_ids' => ['required', 'array', 'min:1', 'max:5'],
            'document_ids.*' => ['integer', 'distinct', 'exists:document_types,id'],
            'purpose' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
