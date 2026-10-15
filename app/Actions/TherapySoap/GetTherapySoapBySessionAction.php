<?php

namespace App\Actions\TherapySoap;

use App\Models\TherapySession;
use App\Models\TherapySoap;

class GetTherapySoapBySessionAction
{
    /**
     * Retrieve the SOAP note for a specific therapy session with all required relations.
     */
    public function execute(TherapySession $session): ?TherapySoap
    {
        return TherapySoap::query()
            ->where('therapy_session_id', $session->id)
            ->with([
                'therapySession:id,schedule_id,session_number,session_date,therapist_id,status',
                'therapySession.schedule:id,child_id,therapist_id,therapy_type,day_of_week,start_time,end_time,total_meetings',
                'therapySession.schedule.child:id,family_id,child_name,child_birth_place,child_birth_date,child_gender,child_school,child_address,medical_record_number',
                'therapySession.schedule.child.family.guardians:id,family_id,guardian_name,guardian_phone,guardian_type,relationship_with_child',
                'therapySession.schedule.therapist:id,therapist_name',
                'therapySession.substituteTherapist:id,therapist_name',
            ])
            ->first();
    }
}
