<?php

namespace App\Http\Requests\SuperAdmin;

use App\Support\ClearanceSignatories;
use App\Support\Usernames;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => Usernames::normalize($this->input('username')),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

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
            'username' => Usernames::rules(),
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::in(ClearanceSignatories::roleOptions())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return Usernames::messages();
    }
}
