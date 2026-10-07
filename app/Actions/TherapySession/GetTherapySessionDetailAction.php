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
        $session->load([
            'schedule:id,child_id,therapist_id,therapy_type,day_of_week,start_time,end_time,status',
            'schedule.child:id,family_id,child_name,child_birth_place,child_birth_date,child_gender,child_school,child_complaint,medical_record_number',
            'schedule.child.family.guardians:id,family_id,guardian_name,guardian_phone,guardian_type,relationship_with_child',
            'schedule.therapist:id,therapist_name',
            'substituteTherapist:id,therapist_name',
            // rescheduleOriginal → this session was rescheduled, so there is a new session
            'rescheduleOriginal.newSession:id,session_date',
        ]);

        return $session;
    }
}
