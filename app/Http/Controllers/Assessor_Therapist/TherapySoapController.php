<?php

namespace App\Http\Controllers\Assessor_Therapist;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Requests\IndexTherapySoapRequest;
use App\Http\Resources\TherapySoapResource;
use App\Services\TherapySoapService;
use Illuminate\Http\JsonResponse;

class TherapySoapController extends Controller
{
    use ResponseFormatter;

    public function __construct(
        private TherapySoapService $therapySoapService,
    ) {}

    /**
     * GET /therapy_soaps
     *
     * Return a paginated list of therapy SOAP notes for asesor & terapis roles.
     *
     * Query Parameters:
     *   - therapy_type : 'okupasi' | 'wicara' | 'paedagog' | 'fisio'
     *   - search       : partial match on child or parent name
     *   - date_range   : 'today' | 'tomorrow' | 'this_week' | 'this_month'
     *   - date         : specific date in Y-m-d format (overrides date_range, filters on performed_at)
     *   - per_page     : items per page (default 15, max 100)
     *   - page         : page number (default 1)
     */
    public function index(IndexTherapySoapRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $soaps   = $this->therapySoapService->getTherapySoaps($filters);

        return $this->successResponse(
            TherapySoapResource::collection($soaps),
            'Daftar Catatan SOAP Terapi',
            200,
            $soaps
        );
    }
}
