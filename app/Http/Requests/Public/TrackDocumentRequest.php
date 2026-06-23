<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class TrackDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'reference_no' => ['required', 'string', 'max:20', 'regex:/^REQ-[0-9]{4}-[0-9]{6}$/'],
        ];
    }
}
