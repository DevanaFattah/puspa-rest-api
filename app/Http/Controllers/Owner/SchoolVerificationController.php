<?php

namespace App\Http\Controllers\Owner;

use App\Actions\School\RejectSchoolAction;
use App\Actions\School\VerifySchoolAction;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Resources\OwnerSchoolResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolVerificationController extends Controller
{
    use ResponseFormatter;

    /**
     * Get list of all schools for Owner / Manager UI (All Sekolah menu).
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');

        $schools = School::with(['user'])
            ->withCount('children')
            ->when($search, function ($q, $search) {
                $q->where('school_name', 'like', "%{$search}%")
                  ->orWhere('npsn', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('username', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            })
            ->latest()
            ->get();

        return $this->successResponse(
            OwnerSchoolResource::collection($schools),
            'Daftar Semua Sekolah Berhasil Diambil',
            200
        );
    }

    /**
     * Get list of unverified schools waiting for approval.
     */
    public function unverified(Request $request): JsonResponse
    {
        $schools = School::with(['user'])
            ->where(function ($q) {
                $q->where('verification_status', 'pending')
                    ->orWhereHas('user', fn($u) => $u->where('is_active', false));
            })
            ->latest()
            ->get();

        return $this->successResponse(
            OwnerSchoolResource::collection($schools),
            'Daftar Sekolah Menunggu Verifikasi',
            200
        );
    }

    /**
     * Approve and verify school.
     */
    public function verify(School $school, VerifySchoolAction $action): JsonResponse
    {
        $verified = $action->execute($school);

        return $this->successResponse(
            new OwnerSchoolResource($verified),
            "Sekolah {$verified->school_name} berhasil diverifikasi",
            200
        );
    }

    /**
     * Reject school registration.
     */
    public function reject(School $school, RejectSchoolAction $action): JsonResponse
    {
        $rejected = $action->execute($school);

        return $this->successResponse(
            new OwnerSchoolResource($rejected),
            "Pendaftaran sekolah {$rejected->school_name} telah ditolak",
            200
        );
    }
}
