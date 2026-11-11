<?php

namespace App\Actions\Assessment;

use App\Models\Assessment;
use Illuminate\Validation\ValidationException;

class SendAssessmentToSchoolAction
{
    /**
     * Send an assessment report to the child's associated school.
     *
     * @param Assessment $assessment
     * @return Assessment
     * @throws ValidationException
     */
    public function execute(Assessment $assessment): Assessment
    {
        $assessment->loadMissing(['child', 'child.school']);

        if (!$assessment->child || !$assessment->child->school_id) {
            throw ValidationException::withMessages([
                'school' => ['Anak belum terafiliasi dengan sekolah mana pun.'],
            ]);
        }

        $assessment->update([
            'sent_to_school_at' => now(),
        ]);

        return $assessment->fresh(['child', 'child.school']);
    }
}

