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
    ) {}

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
}
