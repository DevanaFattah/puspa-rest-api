<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Requests\Admin\AgreeToAssessmentRequest;
use App\Http\Requests\Admin\ObservationUpdateRequest;
use App\Models\Observation;
use App\Services\ObservationService;
use Illuminate\Http\JsonResponse;

class ObservationController extends Controller
{
    use ResponseFormatter;

    public function __construct(
        private ObservationService $observationService
    ) {}

    public function updateObservationDate(ObservationUpdateRequest $request, Observation $observation): JsonResponse
    {
        $data = $request->validated();
        $this->observationService->reschedule($observation, $data);

        return $this->successResponse([], 'Jadwal Observasi Berhasil Diperbarui', 200);
    }

    /**
     * @throws \Exception
     */
    public function assessmentAgreement(AgreeToAssessmentRequest $request, Observation $observation): JsonResponse
    {
        $data = $request->validated();
        $this->observationService->agreeToAssessment($observation, $data);

        return $this->successResponse([], 'Assessment Berhasil Diperbarui', 200);
    }

    public function therapistOptions(Observation $observation): JsonResponse
    {
        $options = $this->observationService->therapistOptions($observation);

        return $this->successResponse($options, 'Opsi Terapis Berhasil Diambil', 200);
    }
}
