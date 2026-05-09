<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSuperAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'superadmin';
    }

    protected function prepareForValidation(): void
    {
        $year = $this->input('year_level');
        $this->merge([
            'year_level' => $year === '' || $year === null ? null : (int) $year,
            'course' => $this->input('course') === '' ? null : $this->input('course'),
            'student_id' => $this->input('student_id') === '' ? null : $this->input('student_id'),
            'contact_number' => $this->input('contact_number') === '' ? null : $this->input('contact_number'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'fullname' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', Rule::in(['student', 'admin', 'teacher', 'dean', 'accounting', 'sao', 'superadmin'])],
            'status' => ['required', Rule::in(['pending', 'active', 'suspended', 'rejected'])],
            'course' => ['nullable', 'string', 'max:100'],
            'year_level' => ['nullable', 'integer', 'min:1', 'max:4'],
            'student_id' => ['nullable', 'string', 'max:50', Rule::unique('users', 'student_id')->ignore($userId)],
            'contact_number' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('role') === 'student' && blank($this->input('student_id'))) {
                $validator->errors()->add('student_id', 'Student ID is required when role is student.');
            }
        });
    }
}
