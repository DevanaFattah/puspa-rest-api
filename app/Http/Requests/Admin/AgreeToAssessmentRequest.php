<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AgreeToAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_date'                    => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time'                    => ['required', 'date_format:H:i'],
            'therapists'                        => ['required', 'array', 'min:1'],
            'therapists.*.assessment_detail_id' => ['required', 'integer', 'exists:assessment_details,id'],
            'therapists.*.therapist_id'         => ['required', 'string', 'exists:therapists,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_date.required'                    => 'Tanggal terjadwal tidak boleh kosong',
            'scheduled_time.required'                    => 'Waktu terjadwal tidak boleh kosong',
            'scheduled_date.after_or_equal'              => 'Jadwal tidak bisa di bawah dari waktu sekarang.',
            'therapists.required'                        => 'Data terapis tidak boleh kosong',
            'therapists.array'                           => 'Data terapis harus berupa array',
            'therapists.min'                             => 'Minimal harus ada 1 terapis yang ditugaskan',
            'therapists.*.assessment_detail_id.required' => 'ID assessment detail wajib diisi',
            'therapists.*.assessment_detail_id.exists'   => 'Assessment detail tidak ditemukan',
            'therapists.*.therapist_id.required'         => 'ID terapis wajib diisi',
            'therapists.*.therapist_id.exists'           => 'Terapis tidak ditemukan',
        ];
    }
}

