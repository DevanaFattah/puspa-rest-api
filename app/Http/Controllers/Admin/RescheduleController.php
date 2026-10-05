<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Requests\StoreRescheduleRequest;
use App\Http\Resources\RescheduleResource;
use App\Models\TherapySession;
use App\Services\RescheduleService;
use Illuminate\Http\JsonResponse;

class RescheduleController extends Controller
{
    use ResponseFormatter;

    protected RescheduleService $rescheduleService;

    public function __construct(RescheduleService $rescheduleService)
    {
        $this->rescheduleService = $rescheduleService;
    }

    public function store(StoreRescheduleRequest $request, TherapySession $session): JsonResponse
    {
        $reschedule = $this->rescheduleService->createReschedule($session, $request->validated());

        return $this->successResponse(
            new RescheduleResource($reschedule),
            'Jadwal terapi berhasil di-reschedule',
            201
        );
    }
}
