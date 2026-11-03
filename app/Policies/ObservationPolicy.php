<?php

namespace App\Policies;

use App\Models\Observation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ObservationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can submit the observation.
     */
    public function submit(User $user, Observation $observation): Response
    {
        if (! $user->therapist) {
            return $this->deny('Profil terapis tidak ditemukan.');
        }

        if ($user->therapist->id !== $observation->therapist_id) {
            return $this->deny('Kamu bukan terapis yang ditugaskan untuk observasi ini.');
        }

        return $this->allow();
    }
}
