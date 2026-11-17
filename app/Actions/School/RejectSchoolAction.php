<?php

namespace App\Actions\School;

use App\Models\School;
use Illuminate\Support\Facades\DB;

class RejectSchoolAction
{
    /**
     * Reject a registered school.
     *
     * @param School $school
     * @return School
     */
    public function execute(School $school): School
    {
        return DB::transaction(function () use ($school) {
            $school->update([
                'verification_status' => 'rejected',
            ]);

            if ($school->user_id) {
                \App\Models\User::where('id', $school->user_id)->update([
                    'is_active' => false,
                ]);
            }

            return $school->fresh(['user']);
        });
    }
}

