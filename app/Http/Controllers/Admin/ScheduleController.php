<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Resources\ScheduleDetailResource;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Schedules",
 *     description="API Endpoints untuk Pengelolaan Master Jadwal Terapi Anak"
 * )
 */
class ScheduleController extends Controller
{
    use ResponseFormatter;

    protected ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * @OA\Get(
     *     path="/schedules",
     *     summary="Mengambil daftar master jadwal terapi (with filters & pagination)",
     *     tags={"Schedules"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", description="Halaman paginasi", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Jumlah per halaman", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="day_of_week", in="query", description="Filter hari (1=Senin..7=Minggu)", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", description="Filter status (aktif, non_aktif, selesai)", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="therapy_type", in="query", description="Filter jenis terapi (okupasi, fisio, wicara, paedagog)", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="search", in="query", description="Cari nama anak, RM, atau nama wali", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Daftar Master Jadwal Terapi")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['day_of_week', 'status', 'therapy_type', 'search']);
        $perPage = (int) $request->input('per_page', 10);

        $schedules = $this->scheduleService->getPaginatedSchedules($filters, $perPage);

        return $this->successResponse([
            'items' => ScheduleResource::collection($schedules->items()),
            'meta' => [
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
                'per_page' => $schedules->perPage(),
                'total' => $schedules->total(),
                'from' => $schedules->firstItem(),
                'to' => $schedules->lastItem(),
            ],
            'links' => [
                'first' => $schedules->url(1),
                'last' => $schedules->url($schedules->lastPage()),
                'prev' => $schedules->previousPageUrl(),
                'next' => $schedules->nextPageUrl(),
            ],
        ], 'Daftar Master Jadwal Terapi', 200);
    }

    /**
     * @OA\Post(
     *     path="/schedules",
     *     summary="Membuat master jadwal baru (Otomatis me-generate sesi pertemuan)",
     *     tags={"Schedules"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"child_id","therapist_id","therapy_type","day_of_week","start_time","end_time","total_meetings","period_start","period_end"},
     *             @OA\Property(property="child_id", type="string", example="01M3P024T4DF062NCEJZXFZEH1"),
     *             @OA\Property(property="therapist_id", type="string", example="01M3P024T4DF062NCEJZXFZEH2"),
     *             @OA\Property(property="therapy_type", type="string", example="paedagog"),
     *             @OA\Property(property="day_of_week", type="integer", example=1),
     *             @OA\Property(property="start_time", type="string", example="15:30"),
     *             @OA\Property(property="end_time", type="string", example="16:30"),
     *             @OA\Property(property="total_meetings", type="integer", example=4),
     *             @OA\Property(property="period_start", type="string", example="2026-10-05"),
     *             @OA\Property(property="period_end", type="string", example="2026-11-05"),
     *             @OA\Property(property="status", type="string", example="aktif")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Berhasil menambahkan jadwal baru")
     * )
     */
    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $schedule = $this->scheduleService->createSchedule($data);
        $response = new ScheduleDetailResource($schedule);

        return $this->successResponse($response, 'Berhasil menambahkan jadwal baru', 201);
    }

    /**
     * @OA\Get(
     *     path="/schedules/{schedule}",
     *     summary="Mengambil detail master jadwal beserta sesi pertemuan",
     *     tags={"Schedules"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="schedule", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Detail Master Jadwal Terapi")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $schedule = $this->scheduleService->getScheduleDetail($id);
        $response = new ScheduleDetailResource($schedule);

        return $this->successResponse($response, 'Detail Master Jadwal Terapi', 200);
    }

    /**
     * @OA\Put(
     *     path="/schedules/{schedule}",
     *     summary="Memperbarui data master jadwal",
     *     tags={"Schedules"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="schedule", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="day_of_week", type="integer", example=2),
     *             @OA\Property(property="start_time", type="string", example="16:00"),
     *             @OA\Property(property="end_time", type="string", example="17:00"),
     *             @OA\Property(property="status", type="string", example="aktif")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Berhasil memperbarui jadwal")
     * )
     */
    public function update(UpdateScheduleRequest $request, Schedule $schedule): JsonResponse
    {
        $data = $request->validated();
        $updated = $this->scheduleService->updateSchedule($data, $schedule);
        $response = new ScheduleDetailResource($updated);

        return $this->successResponse($response, 'Berhasil memperbarui jadwal', 200);
    }

    /**
     * @OA\Delete(
     *     path="/schedules/{schedule}",
     *     summary="Menghapus master jadwal dan seluruh sesinya",
     *     tags={"Schedules"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="schedule", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Berhasil menghapus jadwal")
     * )
     */
    public function destroy(Schedule $schedule): JsonResponse
    {
        $this->scheduleService->deleteSchedule($schedule);

        return $this->successResponse([], 'Berhasil menghapus jadwal', 200);
    }
}
