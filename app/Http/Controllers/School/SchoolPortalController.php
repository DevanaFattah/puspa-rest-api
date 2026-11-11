<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Requests\School\UpdateSchoolProfileRequest;
use App\Http\Resources\School\SchoolStudentDetailResource;
use App\Http\Resources\School\SchoolStudentListResource;
use App\Models\Child;
use App\Services\SchoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $school = $user->school;

        if (!$school) {
            return $this->errorResponse('Data profil sekolah tidak ditemukan', [], 404);
        }

        $data = [
            'id'             => $school->id,
            'school_name'    => $school->school_name,
            'nama'           => $school->school_name,
            'npsn'           => $school->npsn,
            'jenjang'        => $school->jenjang,
            'pic_name'       => $school->pic_name,
            'school_phone'   => $school->school_phone,
            'phone'          => $school->school_phone,
            'school_address' => $school->school_address,
            'alamat'         => $school->school_address,
            'username'       => $user->username,
            'email'          => $user->email,
        ];

        return $this->successResponse($data, 'Profil sekolah berhasil diambil', 200);
    }

    public function updateProfile(UpdateSchoolProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $school = $user->school;

        if (!$school) {
            return $this->errorResponse('Data profil sekolah tidak ditemukan', [], 404);
        }

        $school->update($request->validated());

        $data = [
            'id'             => $school->id,
            'school_name'    => $school->school_name,
            'nama'           => $school->school_name,
            'npsn'           => $school->npsn,
            'jenjang'        => $school->jenjang,
            'pic_name'       => $school->pic_name,
            'school_phone'   => $school->school_phone,
            'phone'          => $school->school_phone,
            'school_address' => $school->school_address,
            'alamat'         => $school->school_address,
            'username'       => $user->username,
            'email'          => $user->email,
        ];

        return $this->successResponse($data, 'Profil sekolah berhasil diperbarui', 200);
    }

    public function downloadAssessmentReport(Request $request, Child $child)
    {
        $user = $request->user();
        $school = $user->school;

        if (!$school || $child->school_id !== $school->id) {
            return $this->errorResponse('Forbidden: Anda tidak memiliki akses ke data siswa ini', [], 403);
        }

        $assessment = $child->assessment;

        if (!$assessment || !$assessment->report_file) {
            return $this->errorResponse('Laporan asesmen belum tersedia untuk siswa ini', [], 404);
        }

        // Catat timestamp unduhan pertama kali (tanpa mengunci unduhan berikutnya)
        if (!$assessment->school_downloaded_at) {
            $assessment->update(['school_downloaded_at' => now()]);
        }

        $filePath = storage_path('app/public/' . $assessment->report_file);
        if (!file_exists($filePath)) {
            // Coba cek path relatif tanpa public jika disimpan langsung di storage/app/
            $altPath = storage_path('app/' . $assessment->report_file);
            if (file_exists($altPath)) {
                $filePath = $altPath;
            } else {
                return $this->errorResponse('Berkas fisik laporan asesmen tidak ditemukan di penyimpanan server', [], 404);
            }
        }

        $downloadFileName = 'Laporan_Asesmen_' . Str::slug($child->child_name) . '.pdf';

        return response()->download($filePath, $downloadFileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
