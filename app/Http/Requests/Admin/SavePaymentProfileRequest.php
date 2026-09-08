<?php

namespace App\Http\Requests\Admin;

use App\Models\PaymentProfile;
use Illuminate\Foundation\Http\FormRequest;

class SavePaymentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', PaymentProfile::class) ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:120'],
            'account_name' => ['required', 'string', 'max:180'],
            'account_number' => ['required', 'string', 'max:60'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'extensions:jpg,jpeg,png,gif,webp', 'max:4096', 'dimensions:max_width=4000,max_height=4000'],
        ];
    }
}
