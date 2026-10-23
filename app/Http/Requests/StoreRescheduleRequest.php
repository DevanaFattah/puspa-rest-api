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

                // Check conflict jadwal terapis
                $newDate = $this->input('new_date');
                $targetTherapistId = $this->input('new_therapist_id') ?: ($session->therapist_id ?: $session->schedule?->therapist_id);
                $startTime = $this->input('start_time') ?: ($session->start_time ?: $session->schedule?->start_time);
                $endTime = $this->input('end_time') ?: ($session->end_time ?: $session->schedule?->end_time);

                if ($newDate && $targetTherapistId && $startTime && $endTime) {
                    $conflictQuery = \App\Models\TherapySession::query()
                        ->where('session_date', $newDate)
                        ->where('status', '!=', 'cancelled')
                        ->where(function ($q) use ($targetTherapistId) {
                            $q->where('therapist_id', $targetTherapistId)
                              ->orWhere(function ($sq) use ($targetTherapistId) {
                                  $sq->whereNull('therapist_id')
                                     ->whereHas('schedule', function ($scq) use ($targetTherapistId) {
                                         $scq->where('therapist_id', $targetTherapistId);
                                     });
                              });
                        })
                        ->where(function ($tq) use ($startTime, $endTime) {
                            $tq->where(function ($overlap) use ($startTime, $endTime) {
                                $overlap->where('start_time', '<', $endTime)
                                        ->where('end_time', '>', $startTime);
                            })->orWhere(function ($sqOverlap) use ($startTime, $endTime) {
                                $sqOverlap->whereNull('start_time')
                                          ->whereHas('schedule', function ($sched) use ($startTime, $endTime) {
                                              $sched->where('start_time', '<', $endTime)
                                                    ->where('end_time', '>', $startTime);
                                          });
                            });
                        });

                    if ($conflictQuery->exists()) {
                        $validator->errors()->add(
                            'new_therapist_id',
                            'Terapis yang dipilih sudah memiliki jadwal sesi lain pada tanggal dan jam tersebut.'
                        );
                    }
                }
            }
        });
    }
}
