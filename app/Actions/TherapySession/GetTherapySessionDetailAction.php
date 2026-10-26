<?php

namespace App\Actions\TherapySession;

use App\Models\TherapySession;

class GetTherapySessionDetailAction
{
    /**
     * Load a single TherapySession with all relationships needed for the detail view.
     */
    public function execute(TherapySession $session): TherapySession
    {
        $user = auth()->user();
        $isSupervisory = $user && (
            $user->hasRole('admin') ||
            $user->hasRole('owner') ||
            $user->hasRole('asesor') ||
            $user->hasRole('kepala_terapis')
        );

        if (!$isSupervisory && $user && $user->hasRole('terapis')) {
            $myTherapistId = $user->therapist?->id;
            $sessionTherapistId = $session->therapist_id ?? $session->schedule?->therapist_id;
            if ($sessionTherapistId && $sessionTherapistId !== $myTherapistId) {
                abort(403, 'Anda tidak memiliki hak akses untuk melihat rincian sesi terapis lain.');
            }
        }

        $session->load([
            'schedule:id,child_id,therapist_id,therapy_type,day_of_week,start_time,end_time,status',
            'schedule.child:id,family_id,child_name,child_birth_place,child_birth_date,child_gender,child_address,child_school,child_complaint,medical_record_number',
            'schedule.child.family.guardians:id,family_id,guardian_name,guardian_phone,guardian_type,relationship_with_child',
            'schedule.therapist:id,therapist_name',
            'substituteTherapist:id,therapist_name',
            // rescheduleOriginal → this session was rescheduled, so there is a new session
            'rescheduleOriginal.newSession:id,session_date',
        ]);

        return $session;
    }
}
