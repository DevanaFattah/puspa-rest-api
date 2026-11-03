<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ActiveTherapistRule;

class ObservationUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'therapist_id'   => ['required', 'string', new ActiveTherapistRule],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['required', 'date_format:H:i']
        ];
    }

    public function messages(): array
    {
        return [
            'therapist_id.required'         => 'Terapis yang ditugaskan tidak boleh kosong',
            'scheduled_date.required'       => 'Tanggal terjadwal tidak boleh kosong',
            'scheduled_time.required'       => 'Waktu terjadwal tidak boleh kosong',
            'scheduled_date.after_or_equal' => 'Jadwal observasi tidak bisa di bawah dari waktu sekarang.'
        ];
    }
}
