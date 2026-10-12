<?php

namespace App\Actions\TherapySoap;

use App\Models\TherapySoap;

class CreateTherapySoapAction
{
    /**
     * Create a new therapy SOAP record and load its relationships.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): TherapySoap
    {
        $soap = TherapySoap::create($data);

        // Eager-load relations so TherapySoapResource can format child, schedule, and therapist details
        return $soap->load([
            'therapySession.schedule.child.family.guardians',
            'therapySession.schedule.therapist.user',
            'therapySession.substituteTherapist.user',
        ]);
    }
}
