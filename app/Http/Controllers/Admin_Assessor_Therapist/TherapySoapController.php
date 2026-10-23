<?php

namespace App\Http\Controllers\Admin_Assessor_Therapist;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Requests\ExportTherapySoapRequest;
use App\Services\TherapySoapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TherapySoapController extends Controller
{
    use ResponseFormatter;

    public function __construct(
        private TherapySoapService $therapySoapService,
    ) {}

    /**
     * GET /therapy_soaps/export/filter-options
     *
     * Return filter options for exporting SOAP notes (children, therapists, and therapy sections).
     * For admin, returns all therapists and children with therapy_type pairing.
     * For terapis/asesor, returns only the logged-in therapist and their handled children.
     */
    public function exportFilterOptions(Request $request): JsonResponse
    {
        $user = $request->user();
        $filters = $request->only(['therapy_type', 'therapist_id']);

        $options = $this->therapySoapService->getExportFilterOptions($user, $filters);

        return $this->successResponse(
            $options,
            'Opsi Filter Export SOAP',
            200
        );
    }

    /**
     * GET /therapy_soaps/export
     *
     * Export therapy SOAP notes matching criteria as a streamed PDF document.
     * For admin: can export all children, all therapists, or filtered by therapist/therapy_type.
     * For terapis/asesor: automatically scoped to the logged-in therapist.
     *
     * Query Parameters:
     *   - child_id     : child ULID (single or array)
     *   - therapist_id : therapist ULID (admin only)
     *   - therapy_type : 'okupasi' | 'wicara' | 'paedagog' | 'fisio'
     *   - period_type  : 'daily' | 'weekly' | 'monthly' | 'custom'
     *   - date         : specific date 'Y-m-d' (for daily)
     *   - week         : integer 1-53 (for weekly; week of year or week of month if month provided)
     *   - month        : integer 1-12 (for monthly or combined with week)
     *   - year         : integer (e.g. 2026, default current year)
     *   - date_from    : specific date string in 'Y-m-d' format (for custom)
     *   - date_to      : specific date string in 'Y-m-d' format (for custom)
     */
    public function export(ExportTherapySoapRequest $request)
    {
        $user = $request->user();
        $filters = $request->validated();

        if (!$user->hasRole('admin')) {
            $therapist = $user->therapist;
            if (!$therapist) {
                return $this->errorResponse('Therapist profile tidak ditemukan', [], 404);
            }

            $filters['therapist_id'] = $therapist->id;
            $filters['therapy_type'] = $therapist->therapist_section;
        }

        ['pdf' => $pdf, 'file_name' => $fileName] = $this->therapySoapService->export($user, $filters);

        return $pdf->stream($fileName);
    }
}
