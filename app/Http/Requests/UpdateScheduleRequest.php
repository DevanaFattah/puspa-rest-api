<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'child_id' => 'sometimes|string|exists:children,id',
            'therapy_type' => 'sometimes|in:okupasi,fisio,wicara,paedagog',
            'therapist_id' => [
                'sometimes',
                'string',
                Rule::exists('therapists', 'id')->where(function ($query) {
                    $therapyType = $this->input('therapy_type') ?? $this->route('schedule')?->therapy_type;
                    if ($therapyType) {
                        $query->where('therapist_section', $therapyType);
                    }
                }),
            ],
            'day_of_week' => 'sometimes|integer|between:1,7',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'total_meetings' => 'sometimes|integer|min:1|max:100',
            'period_start' => 'sometimes|date_format:Y-m-d',
            'period_end' => 'sometimes|date_format:Y-m-d|after_or_equal:period_start',
            'status' => 'sometimes|in:aktif,non_aktif,selesai',
        ];
    }

    public function messages(): array
    {
        return [
            'therapist_id.exists' => 'Terapis yang dipilih tidak terdaftar atau bidangnya (section) tidak sesuai dengan jenis terapi.',
        ];
    }
}
