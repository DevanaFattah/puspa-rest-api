<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'required',
                Rule::in([
                    'pending',
                    'present',
                    'absent',
                    'excused',
                    'sick',
                    'therapist_absent',
                    'therapist_excused',
                    'cancelled',
                ]),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }
}
