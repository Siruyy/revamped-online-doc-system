<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;

class SignClearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['teacher', 'dean', 'accounting', 'sao'], true);
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
