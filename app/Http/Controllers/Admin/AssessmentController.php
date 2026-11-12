<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Requests\Admin\AssessmentUpdateRequest;
use App\Http\Requests\IndexAssessmentsByStatusRequest;
use App\Http\Resources\AssessmentListAdminResource;
use App\Models\Assessment;
use App\Services\AssessmentService;
use Illuminate\Http\JsonResponse;


class AssessmentController extends Controller
{
    use ResponseFormatter;

    protected $assessmentService;

    public function __construct(AssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }

    // Display registered assessments based on status (scheduled, completed) and type (fisio, wicara, etc.)
    public function indexAssessments(IndexAssessmentsByStatusRequest $request, string $status): JsonResponse
    {
        $validated = $request->validated();

        $assessments = $this->assessmentService->getAssessmentsByType($validated);

        $response = AssessmentListAdminResource::collection($assessments);
        $message = 'Daftar Asesmen ' . ucfirst($status);
        if (isset($validated['type'])) {
            $message .= ' ' . ucfirst($validated['type']);
        }
        return $this->successResponse($response, $message);
    }

    public function updateAssessmentDate(AssessmentUpdateRequest $request, Assessment $assessment): JsonResponse
    {
        $data = $request->validated();
        $this->assessmentService->updateScheduledDate($assessment, $data);

        return $this->successResponse([], 'Jadwal Assessment Berhasil Diperbarui', 200);
    }

    public function therapistOptions(Assessment $assessment): JsonResponse
    {
        $options = $this->assessmentService->therapistOptions($assessment);

        return $this->successResponse($options, 'Opsi Terapis Berhasil Diambil', 200);
    }
}
