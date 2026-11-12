<?php

namespace App\Http\Requests\School;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('sekolah') ?? false;
    }

    public function rules(): array
    {
        return [
            'school_name'    => ['sometimes', 'required', 'string', 'max:150'],
            'npsn'           => ['nullable', 'string', 'max:20'],
            'jenjang'        => ['nullable', 'string', 'max:30'],
            'pic_name'       => ['nullable', 'string', 'max:100'],
            'school_phone'   => ['nullable', 'string', 'max:50'],
            'school_address' => ['nullable', 'string', 'max:500'],
        ];
    }
}

