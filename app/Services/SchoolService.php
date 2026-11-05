<?php

namespace App\Services;

use App\Models\Child;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SchoolService
{
    public function getDashboardStats(User $user): array
    {
        $school = $user->school;

        $totalStudents = Child::where('school_id', $school->id)->count();

        $studentsWithObservation = Child::where('school_id', $school->id)
            ->whereHas('observation')
            ->count();

        $studentsWithAssessment = Child::where('school_id', $school->id)
            ->whereHas('assessment')
            ->count();

        return [
            'school_name'                => $school->school_name,
            'npsn'                       => $school->npsn,
            'pic_name'                   => $school->pic_name,
            'total_students'             => $totalStudents,
            'students_with_observation'  => $studentsWithObservation,
            'students_with_assessment'   => $studentsWithAssessment,
        ];
    }

    public function getStudents(User $user, array $filters = []): LengthAwarePaginator
    {
        $school = $user->school;
        $perPage = $filters['per_page'] ?? 15;

        return Child::query()
            ->where('school_id', $school->id)
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $query->where('child_name', 'like', '%' . $filters['search'] . '%');
            })
            ->when(!empty($filters['gender']), function ($query) use ($filters) {
                $query->where('child_gender', $filters['gender']);
            })
            ->with([
                'observation:id,child_id,status',
                'assessment:id,child_id,status',
            ])
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function getStudentDetail(Child $child): Child
    {
        return $child->load([
            'school:id,school_name,npsn',
            'family.guardians:id,family_id,guardian_name,guardian_type',
            'observation',
            'assessment.assessmentDetails',
        ]);
    }
}

