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
                if (in_array($session->status, ['present', 'pending'])) {
                    $validator->errors()->add(
                        'session',
                        "Sesi dengan status '{$session->status}' tidak dapat di-reschedule. Hanya sesi berstatus di luar 'present' dan 'pending' yang dapat di-reschedule."
                    );
                }

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
