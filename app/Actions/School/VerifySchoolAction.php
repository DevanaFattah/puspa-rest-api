<?php

namespace App\Actions\School;

use App\Models\School;
use Illuminate\Support\Facades\DB;

class VerifySchoolAction
{
    /**
     * Approve and verify a registered school.
     *
     * @param School $school
     * @return School
     */
    public function execute(School $school): School
    {
        return DB::transaction(function () use ($school) {
            $school->update([
                'verification_status' => 'verified',
                'verified_at' => now(),
            ]);

            if ($school->user) {
                $school->user->update([
                    'is_active' => true,
                ]);
            }

            return $school->fresh(['user']);
        });
    }
}

