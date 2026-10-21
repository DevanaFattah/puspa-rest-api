<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRescheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'new_therapist_id' => ['nullable', 'string', 'exists:therapists,id'],
            'reason' => ['required', 'string', 'max:500'],
            'requested_by' => ['required', Rule::in(['children', 'therapist', 'guardian'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var \App\Models\TherapySession|null $session */
            $session = $this->route('session');

            if ($session) {
                if ($session->soap()->exists()) {
                    $validator->errors()->add(
                        'session',
                        'Sesi yang sudah memiliki catatan SOAP tidak dapat di-reschedule.'
                    );
                }

                if ($session->rescheduleOriginal()->exists()) {
                    $validator->errors()->add(
                        'session',
                        'Sesi ini sudah pernah di-reschedule sebelumnya.'
                    );
                }
            }
        });
    }
}
