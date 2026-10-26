<?php

namespace App\Http\Controllers\Assessor_Therapist;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Requests\StoreTherapySoapRequest;
use App\Http\Resources\TherapySoapResource;
use App\Services\TherapySoapService;
use Illuminate\Http\JsonResponse;

class TherapySoapController extends Controller
{
    use ResponseFormatter;

    public function __construct(
        private TherapySoapService $therapySoapService,
    ) {}

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
