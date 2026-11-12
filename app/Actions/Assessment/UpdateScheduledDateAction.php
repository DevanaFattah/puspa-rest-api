<?php

namespace App\Actions\Assessment;

use App\Events\AssessmentUpdated;
use App\Models\Assessment;
use App\Models\AssessmentDetail;
use App\Models\Observation;
use App\Models\Therapist;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateScheduledDateAction
{
    public function execute(Assessment $assessment, array $data): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i', $data['scheduled_date'] . ' ' . $data['scheduled_time']);

        $this->validateScheduleGap($assessment, $date);

        $adminId = auth()->user()?->admin?->id;

        $detailIds = collect($data['therapists'])->pluck('assessment_detail_id');
        $details = AssessmentDetail::whereIn('id', $detailIds)
            ->where('assessment_id', $assessment->id)
            ->get()
            ->keyBy('id');

        if ($details->count() !== $detailIds->count()) {
            throw ValidationException::withMessages([
                'therapists' => ['Beberapa assessment detail tidak ditemukan atau tidak termasuk dalam assessment ini.'],
            ]);
        }

        $therapistIds = collect($data['therapists'])->pluck('therapist_id')->unique();
        $therapists   = Therapist::with('user.roles')->whereIn('id', $therapistIds)->get()->keyBy('id');

        foreach ($data['therapists'] as $index => $item) {
            $detail    = $details->get($item['assessment_detail_id']);
            $therapist = $therapists->get($item['therapist_id']);

            if (!$therapist->user?->hasRole('asesor')) {
                throw ValidationException::withMessages([
                    "therapists.{$index}.therapist_id" => [
                        "Terapis {$therapist->therapist_name} tidak memiliki role asesor."
                    ],
                ]);
            }

            if ($detail->type !== 'umum' && $therapist->therapist_section !== $detail->type) {
                throw ValidationException::withMessages([
                    "therapists.{$index}.therapist_id" => [
                        "Terapis {$therapist->therapist_name} tidak sesuai dengan tipe assessment '{$detail->type}'."
                    ],
                ]);
            }

            $this->validateTherapistAvailability($therapist, $date, $assessment);
        }

        DB::transaction(function () use ($assessment, $data, $date, $adminId, $details) {
            foreach ($data['therapists'] as $item) {
                $details->get($item['assessment_detail_id'])->update([
                    'therapist_id' => $item['therapist_id'],
                    'admin_id'     => $adminId,
                ]);
            }

            $assessment->update([
                'scheduled_date' => $date,
                'status'         => 'scheduled',
            ]);

            event(new AssessmentUpdated());
        });
    }

    private function validateTherapistAvailability(Therapist $therapist, Carbon $date, Assessment $currentAssessment): void
    {
        $from = $date->copy()->subHours(2);
        $to   = $date->copy()->addHours(2);

        // Check: therapist already assigned to another scheduled assessment in this window
        $assessmentConflict = AssessmentDetail::where('therapist_id', $therapist->id)
            ->whereHas(
                'assessment',
                fn($q) => $q
                    ->where('status', 'scheduled')
                    ->whereNotNull('scheduled_date')
                    ->whereBetween('scheduled_date', [$from, $to])
                    ->where('id', '!=', $currentAssessment->id)
            )
            ->exists();

        // Check: therapist is assessor on another scheduled observation in this window
        $observationConflict = Observation::where('therapist_id', $therapist->id)
            ->when($currentAssessment->observation_id, fn($q) => $q->where('id', '!=', $currentAssessment->observation_id))
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_date')
            ->whereBetween('scheduled_date', [$from, $to])
            ->exists();

        if ($assessmentConflict || $observationConflict) {
            throw ValidationException::withMessages([
                'therapists' => [
                    "Terapis {$therapist->therapist_name} sudah memiliki jadwal dalam rentang 2 jam pada waktu yang dipilih."
                ],
            ]);
        }
    }

    private function validateScheduleGap(Assessment $assessment, Carbon $date): void
    {
        $twoHoursBefore = $date->copy()->subHours(2);
        $twoHoursAfter  = $date->copy()->addHours(2);

        $conflictingAssessment = Assessment::query()
            ->where('id', '!=', $assessment->id)
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_date')
            ->whereBetween('scheduled_date', [$twoHoursBefore, $twoHoursAfter])
            ->first();

        if ($conflictingAssessment) {
            $conflictTime = Carbon::parse($conflictingAssessment->scheduled_date)
                ->timezone('Asia/Jakarta')
                ->format('d/m/Y H:i');

            throw ValidationException::withMessages([
                'scheduled_date' => [
                    "Jadwal assessment harus memiliki jeda minimal 2 jam dengan assessment lain. " .
                        "Terdapat assessment pada {$conflictTime}."
                ]
            ]);
        }
    }
}
