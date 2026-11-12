<?php

namespace App\Http\Controllers\Admin_Assessor_Therapist;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Requests\IndexObservationsByStatusRequest;
use App\Http\Resources\ObservationCompletedDetailResource;
use App\Http\Resources\ObservationDetailAnswerResource;
use App\Http\Resources\ObservationQuestionsResource;
use App\Http\Resources\ObservationScheduledDetailResource;
use App\Http\Resources\ObservationsCompletedResource;
use App\Http\Resources\ObservationsPendingResource;
use App\Http\Resources\ObservationsScheduledResource;
use App\Models\Observation;
use App\Services\ObservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ObservationController extends Controller
{
    use ResponseFormatter;

    protected $observationService;

    public function __construct(ObservationService $observationService)
    {
        $this->observationService = $observationService;
    }

    public function indexByStatus(IndexObservationsByStatusRequest $request, string $status): JsonResponse
    {
        $validated = $request->validated();

        $observations = $this->observationService->getObservations($validated);

        $resourceCollection = match ($status) {
            'pending' => ObservationsPendingResource::collection($observations),
            'scheduled' => ObservationsScheduledResource::collection($observations),
            'completed' => ObservationsCompletedResource::collection($observations),
            default => null,
        };

        $message = 'Daftar Observasi ' . ucfirst($status);

        return $this->successResponse($resourceCollection, $message, 200);
    }

    public function showDetailByType(Request $request, Observation $observation): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:scheduled,completed,question,answer'],
        ]);

        $type = $validated['type'];

        if (in_array($type, ['scheduled', 'completed'])) {
            $observation->load(['child', 'child.family.guardians']);
        } elseif ($type === 'answer') {
            $observation->load('observation_answers.observation_question');
        }

        [$response, $message] = match ($type) {
            'scheduled' => [
                new ObservationScheduledDetailResource($observation),
                'Detail Observasi Terjadwal'
            ],
            'completed' => [
                new ObservationCompletedDetailResource($observation),
                'Detail Observasi Selesai'
            ],
            'question' => [
                ObservationQuestionsResource::collection(
                    $this->observationService->getQuestions($observation)
                ),
                'Pertanyaan Observasi'
            ],
            'answer' => [
                new ObservationDetailAnswerResource($observation),
                'Detail Jawaban Observasi'
            ],
        };

        return $this->successResponse($response, $message, 200);
    }
}
