<?php

namespace App\Http\Requests\SuperAdmin;

use App\Support\ClearanceSignatories;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'superadmin';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fullname' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::in(ClearanceSignatories::roleOptions())],
        ];
    }
}
