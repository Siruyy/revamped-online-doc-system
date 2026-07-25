<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;

class DenyClearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDepartment() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'remarks' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }
}
