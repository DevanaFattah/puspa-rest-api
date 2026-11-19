<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SearchGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin', 'owner', 'manager']) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.required' => 'Kata kunci pencarian wajib diisi.',
            'search.min'      => 'Kata kunci pencarian minimal :min karakter.',
            'search.max'      => 'Kata kunci pencarian maksimal :max karakter.',
        ];
    }
}
