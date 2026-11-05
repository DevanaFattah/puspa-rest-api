<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit_school') ?? false;
    }

    public function rules(): array
    {
        $school = $this->route('school');
        $schoolId = is_object($school) ? $school->id : $school;

        return [
            'school_name'    => ['sometimes', 'required', 'string', 'max:150'],
            'npsn'           => ['nullable', 'string', 'max:20', 'unique:schools,npsn,' . $schoolId . ',id'],
            'school_phone'   => ['nullable', 'string', 'max:20'],
            'school_address' => ['nullable', 'string', 'max:255'],
            'pic_name'       => ['nullable', 'string', 'max:100'],
        ];
    }
}

