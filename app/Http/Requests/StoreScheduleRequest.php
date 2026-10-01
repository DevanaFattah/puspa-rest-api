<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'child_id' => 'required|string|exists:children,id',
            'therapy_type' => 'required|in:okupasi,fisio,wicara,paedagog',
            'therapist_id' => [
                'required',
                'string',
                Rule::exists('therapists', 'id')->where(function ($query) {
                    if ($this->filled('therapy_type')) {
                        $query->where('therapist_section', $this->therapy_type);
                    }
                }),
            ],
            'day_of_week' => 'required|integer|between:1,7', // 1=Senin, 7=Minggu
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'total_meetings' => 'required|integer|min:1|max:100',
            'period_start' => 'required|date_format:Y-m-d',
            'period_end' => 'required|date_format:Y-m-d|after_or_equal:period_start',
            'status' => 'nullable|in:aktif,non_aktif,selesai',
        ];
    }

    public function messages(): array
    {
        return [
            'therapist_id.exists' => 'Terapis yang dipilih tidak terdaftar atau bidangnya (section) tidak sesuai dengan jenis terapi.',
        ];
    }
}
