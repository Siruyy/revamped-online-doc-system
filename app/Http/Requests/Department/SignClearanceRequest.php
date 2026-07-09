<?php

namespace App\Http\Requests\Department;

use App\Support\ClearanceSignatories;
use Illuminate\Foundation\Http\FormRequest;

class SignClearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ClearanceSignatories::isSignatoryRole($this->user()?->role);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
