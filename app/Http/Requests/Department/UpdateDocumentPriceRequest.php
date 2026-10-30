<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updatePrice', $this->route('documentType')) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return ['fee' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99']];
    }
}
