<?php

namespace App\Services;

use App\Actions\Assessment\GetAssessmentsAction;
use App\Actions\Assessment\GetAssessmentsByTypeAction;
use App\Actions\Assessment\StoreAssessorAssessmentAction;
use App\Actions\Assessment\StoreParentAssessmentAction;
use App\Actions\Assessment\UpdateScheduledDateAction;
use App\Actions\Observation\TherapistOptionsAction;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentQuestionGroup;
use App\Models\Guardian;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AssessmentService
{
    private const CACHE_QUESTIONS = 'assessment_questions_';

    public function __construct(
        private GetAssessmentsAction $getAssessmentsAction,
        private GetAssessmentsByTypeAction $getAssessmentsByTypeAction,
        private StoreParentAssessmentAction $storeParentAssessmentAction,
        private StoreAssessorAssessmentAction $storeAssessorAssessmentAction,
        private UpdateScheduledDateAction $updateScheduledDateAction,
        private TherapistOptionsAction $therapistOptionsAction,
    ) {}

    public function getAssessments(array $filters = [])
    {
        return $this->getAssessmentsAction->execute($filters);
    }

    public function getAssessmentsByType(array $filters = [])
    {
        return $this->getAssessmentsByTypeAction->execute($filters);
    }

    public function getQuestionsByType(string $type): array
    {
        $groups = Cache::remember(self::CACHE_QUESTIONS . $type, 3600, function () use ($type) {
            return AssessmentQuestionGroup::with('questions')
                ->where('assessment_type', $type)
                ->orderBy('sort_order')
                ->get();
        });

        return [
            'assessment_type' => $type,
            'groups' => $groups->map(fn($group) => [
                'group_id'     => $group->id,
                'group_key'    => $group->group_key,
                'title'        => $group->group_title,
                'filled_by'    => $group->filled_by,
                'sort_order'   => $group->sort_order,
                'questions'    => $group->questions->map(fn($q) => [
                    'id'              => $q->id,
                    'question_code'   => $q->question_code,
                    'question_number' => $q->question_number,
                    'question_text'   => $q->question_text,
                    'answer_type'     => $q->answer_type,
                    'answer_options'  => $q->answer_options,
                    'answer_format'   => $q->answer_format,
                    'extra_schema'    => $q->extra_schema,
                ]),
            ]),
        ];
    }

    public function getAnswers(Assessment $assessment, string $type)
    {
        $cacheKey = "answers_{$assessment->id}_{$type}";

        return Cache::remember($cacheKey, 1800, function () use ($assessment, $type) {
            $detailType = str_replace(['_parent', '_assessor'], '', $type);
            $detail = $assessment->assessmentDetails()->where('type', $detailType)->first();

            if (!$detail) return [];

            return AssessmentAnswer::where('assessment_detail_id', $detail->id)
                ->where('type', $type)
                ->select('id', 'question_id', 'answer_value', 'note')
                ->with('question:id,question_text')
                ->get()
                ->map(fn($answer) => [
                    'question_id'   => $answer->question_id,
                    'question_text' => $answer->question->question_text ?? null,
                    'answer'        => $answer->answer_value,
                    'note'          => $answer->note,
                ]);
        });
    }

    public function storeParentAssessment(Assessment $assessment, string $type, array $data): void
    {
        $this->storeParentAssessmentAction->execute($assessment, $type, $data);
        Cache::forget("answers_{$assessment->id}_{$type}");
    }

    public function storeAssessorAssessment(Assessment $assessment, string $type, array $data): void
    {
        $this->storeAssessorAssessmentAction->execute($assessment, $type, $data);
        Cache::forget("answers_{$assessment->id}_{$type}");
    }

    public function updateScheduledDate(Assessment $assessment, array $data): void
    {
        $this->updateScheduledDateAction->execute($assessment, $data);
    }

    public function therapistOptions(Assessment $assessment): Collection
    {
        return $this->therapistOptionsAction->executeForAssessment($assessment);
    }

    public function getChildrenAssessment(string $userId)
    {
        return Cache::remember("guardian_{$userId}_children_assessments", 300, function () use ($userId) {
            $guardian = Guardian::where('user_id', $userId)->firstOrFail();

            return $guardian->family->children()
                ->whereHas('assessment')
                ->with(['assessment' => function ($query) {
                    $query->orderBy('scheduled_date', 'asc');
                }])
                ->get();
        });
    }

    public function getAssessmentById(string $userId)
    {
        return Cache::remember("guardian_{$userId}_children_assessments", 300, function () use ($userId) {
            $guardian = Guardian::where('user_id', $userId)->firstOrFail();

            return $guardian->family->children()
                ->whereHas('assessment')
                ->with(['assessment' => function ($query) {
                    $query->orderBy('scheduled_date', 'asc');
                }])
                ->get();
        });
    }
}
