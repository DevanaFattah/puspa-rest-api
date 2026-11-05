<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Resources\School\SchoolStudentDetailResource;
use App\Http\Resources\School\SchoolStudentListResource;
use App\Models\Child;
use App\Services\SchoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolPortalController extends Controller
{
    use ResponseFormatter;

    public function __construct(
        private SchoolService $schoolService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $stats = $this->schoolService->getDashboardStats($request->user());

        return $this->successResponse($stats, 'Dashboard sekolah berhasil dimuat', 200);
    }

    public function indexStudents(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search'   => ['nullable', 'string', 'max:100'],
            'gender'   => ['nullable', 'string', 'in:laki-laki,perempuan'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->schoolService->getStudents($request->user(), $filters);

        $items = SchoolStudentListResource::collection($paginator->items());

        return $this->successResponse($items, 'Daftar siswa sekolah berhasil diambil', 200, $paginator);
    }

    public function showStudent(Child $child): JsonResponse
    {
        $this->authorize('viewAsSchool', $child);

        $student = $this->schoolService->getStudentDetail($child);

        return $this->successResponse(
            new SchoolStudentDetailResource($student),
            'Detail siswa berhasil diambil',
            200
        );
    }
}
