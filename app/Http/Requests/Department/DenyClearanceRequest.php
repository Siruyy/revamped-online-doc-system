<?php

namespace App\Http\Requests\Department;

use App\Support\ClearanceSignatories;
use Illuminate\Foundation\Http\FormRequest;

class DenyClearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && ClearanceSignatories::isSignatoryRole($this->user()->role);
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
