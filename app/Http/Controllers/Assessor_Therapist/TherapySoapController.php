<?php

namespace App\Http\Controllers\Assessor_Therapist;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Requests\StoreTherapySoapRequest;
use App\Http\Resources\TherapySoapResource;
use App\Models\TherapySession;
use App\Services\TherapySoapService;
use Illuminate\Http\JsonResponse;

class TherapySoapController extends Controller
{
    use ResponseFormatter;

    public function __construct(
        private TherapySoapService $therapySoapService,
    )
    {
    }

    /**
     * GET /therapy_soaps/{session}
     *
     * Return the therapy SOAP note for a specific therapy session.
     */
    public function show(TherapySession $session): JsonResponse
    {
        $soap = $this->therapySoapService->getTherapySoapBySession($session);

        if (!$soap) {
            return $this->errorResponse('Catatan SOAP tidak ditemukan untuk sesi ini', [], 404);
        }

        return $this->successResponse(
            new TherapySoapResource($soap),
            'Detail Catatan SOAP Terapi',
            200
        );
    }

    public function store(StoreTherapySoapRequest $request): JsonResponse
    {
        $data = $request->validated();
        $soap   = $this->therapySoapService->createTherapySoap($data);

        return $this->successResponse(
            new TherapySoapResource($soap),
            'Catatan SOAP Berhasil Disimpan!',
            201
        );
    }

    /**
     * GET /therapy_soaps/export/filter-options
     *
     * Return filter options for exporting SOAP notes (children treated, therapist name, therapy section).
     */
    public function exportFilterOptions(\Illuminate\Http\Request $request): JsonResponse
    {
        $therapist = $request->user()->therapist;
        if (!$therapist)
        {
            return $this->errorResponse('Therapist profile tidak ditemukan', [], 404);
        }

        $options = $this->therapySoapService->getExportFilterOptions($therapist->id);

        return $this->successResponse(
            $options,
            'Opsi Filter Export SOAP',
            200
        );
    }

    /**
     * GET /therapy_soaps/export
     *
     * Return unpaginated list of therapy SOAP notes matching criteria for export.
     * Automatically scoped to the current authenticated therapist.
     *
     * Query Parameters:
     *   - child_id     : child ULID (single or array)
     *   - period_type  : 'daily' | 'weekly' | 'monthly' | 'custom'
     *   - date         : specific date 'Y-m-d' (for daily)
     *   - week         : integer 1-53 (for weekly; week of year or week of month if month provided)
     *   - month        : integer 1-12 (for monthly or combined with week)
     *   - year         : integer (e.g. 2026, default current year)
     *   - date_from    : specific date string in 'Y-m-d' format (for custom)
     *   - date_to      : specific date string in 'Y-m-d' format (for custom)
     */
    public function export(\App\Http\Requests\ExportTherapySoapRequest $request): JsonResponse
    {
        $therapist = $request->user()->therapist;
        if (!$therapist)
        {
            return $this->errorResponse('Therapist profile tidak ditemukan', [], 404);
        }

        $filters = $request->validated();
        $filters['therapist_id'] = $therapist->id;

        $soaps = $this->therapySoapService->exportTherapySoaps($filters);

        return $this->successResponse(
            TherapySoapResource::collection($soaps),
            'Data Export SOAP Terapi',
            200
        );
    }
}
