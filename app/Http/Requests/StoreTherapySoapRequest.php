<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTherapySoapRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    $user = $this->user();
    if (!$user) {
      return false;
    }

    return $user->hasRole(['asesor', 'terapis']);
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'therapy_session_id'  => [
        'required',
        'string',
        'size:26',
        'exists:therapy_sessions,id',
        'unique:therapy_soaps,therapy_session_id',
      ],
      'performed_at'        => ['required', 'date', 'date_format:Y-m-d H:i:s'],
      'therapy_diagnosis'   => ['required', 'string'],
      'interventions'       => ['required', 'string'],
      'subjective'          => ['required', 'string'],
      'objective'           => ['required', 'string'],
      'assessment'          => ['required', 'string'],
      'plan'                => ['required', 'string'],
      'implementation'      => ['required', 'string'],
      'advanced_assessment' => ['required', 'string'],
      'advanced_plan'       => ['required', 'string'],
    ];
  }
  /**
   * Get the error messages for the defined validation rules.
   */
  public function messages(): array
  {
    return [
      'therapy_session_id.required' => 'ID sesi terapi wajib diisi.',
      'therapy_session_id.size'     => 'ID sesi terapi harus berupa ULID 26 karakter.',
      'therapy_session_id.exists'   => 'Sesi terapi tidak ditemukan.',
      'therapy_session_id.unique'   => 'Catatan SOAP untuk sesi terapi ini sudah ada.',
      'performed_at.required'       => 'Waktu pelaksanaan terapi wajib diisi.',
      'performed_at.date_format'    => 'Format waktu pelaksanaan harus Y-m-d H:i:s (contoh: 2026-10-11 14:30:00).',
      'therapy_diagnosis.required'  => 'Diagnosis terapi wajib diisi.',
      'interventions.required'      => 'Tindakan intervensi terapi wajib diisi.',
      'subjective.required'         => 'Data subjektif (S) wajib diisi.',
      'objective.required'          => 'Data objektif (O) wajib diisi.',
      'assessment.required'         => 'Asesmen (A) wajib diisi.',
      'plan.required'               => 'Perencanaan/Plan (P) wajib diisi.',
      'implementation.required'     => 'Implementasi wajib diisi.',
      'advanced_assessment.required' => 'Asesmen lanjutan wajib diisi.',
      'advanced_plan.required'      => 'Rencana lanjutan wajib diisi.',
    ];
  }
}
