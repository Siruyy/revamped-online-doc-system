<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\Usernames;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => Usernames::normalize($this->input('username')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'contact_number' => $this->input('contact_number') === '' ? null : $this->input('contact_number'),
            'course' => $this->input('course') === '' ? null : $this->input('course'),
            'year_level' => $this->input('year_level') === '' ? null : $this->input('year_level'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => Usernames::rules($this->user()->id),
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'course' => ['nullable', 'string', 'max:100'],
            'year_level' => ['nullable', 'integer', 'min:1', 'max:8'],
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
