<?php

namespace App\Http\Requests\Admin;

use App\Models\SchoolBranding;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', SchoolBranding::class) ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg', 'extensions:png,jpg,jpeg', 'max:2048', 'dimensions:max_width=3000,max_height=3000'],
        ];
    }
}
