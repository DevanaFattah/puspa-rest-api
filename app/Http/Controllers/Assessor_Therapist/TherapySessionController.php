<?php

namespace App\Http\Controllers\Assessor_Therapist;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Requests\IndexTherapySessionRequest;
use App\Http\Resources\TherapySessionResource;
use App\Services\TherapySessionService;
use Illuminate\Http\JsonResponse;

class TherapySessionController extends Controller
{
    use ResponseFormatter;

    public function __construct(
        private TherapySessionService $therapySessionService,
    ) {}

    /**
     * GET /therapy_sessions
     *
     * Return a paginated list of therapy sessions for asesor & terapis roles.
     *
     * Query Parameters:
     *   - therapy_type : 'okupasi' | 'wicara' | 'paedagog' | 'fisio'
     *   - status       : 'scheduled' | 'completed' | 'cancelled' | 'pending'
     *   - search       : partial match on child or parent name
     *   - date_range   : 'today' | 'tomorrow' | 'this_week' | 'this_month'
     *   - date         : specific date in Y-m-d format (overrides date_range)
     *   - per_page     : items per page (default 15, max 100)
     *   - page         : page number (default 1)
     */
    public function index(IndexTherapySessionRequest $request): JsonResponse
    {
        $filters  = $request->validated();
        $sessions = $this->therapySessionService->getTherapySessions($filters);

        return $this->successResponse(
            TherapySessionResource::collection($sessions),
            'Daftar Sesi Terapi',
            200,
            $sessions
        );
    }
}
