<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('status') === 'present') {
                /** @var \App\Models\TherapySession|null $session */
                $session = $this->route('session');

                if ($session) {
                    $sessionDateStr = $session->session_date ? $session->session_date->format('Y-m-d') : null;
                    $todayStr = \Carbon\Carbon::today()->toDateString();

                    if ($sessionDateStr && $sessionDateStr !== $todayStr) {
                        $validator->errors()->add(
                            'status',
                            'Presensi hadir hanya dapat dicatat pada hari H tanggal sesi berlangsung (' . $session->session_date->format('d/m/Y') . ').'
                        );
                        return;
                    }

                    $startTime = $session->start_time ?: $session->schedule?->start_time;

                    if ($startTime) {
                        $nowTime = \Carbon\Carbon::now()->format('H:i');
                        $startTimeShort = substr($startTime, 0, 5);

                        if ($nowTime < $startTimeShort) {
                            $validator->errors()->add(
                                'status',
                                "Presensi hadir belum dapat dicatat karena jam sesi belum dimulai ({$startTimeShort})."
                            );
                        }
                    }
                }
            }
        });
    }
}
