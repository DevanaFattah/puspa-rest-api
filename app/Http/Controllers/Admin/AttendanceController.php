<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Requests\ExportAttendanceRequest;
use App\Http\Requests\MarkAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Resources\RescheduleResource;
use App\Http\Resources\SessionAttendanceResource;
use App\Models\TherapySession;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use ResponseFormatter;

    public function __construct(
        private AttendanceService $attendanceService,
    ) {}

    public function todaySessions(Request $request): JsonResponse
    {
        $filters = $request->only([
            'date',
            'date_from',
            'date_to',
            'child_id',
            'child_ids',
            'therapist_id',
            'therapist_ids',
            'therapy_type',
            'therapy_types',
            'therapy_type_ids',
            'status',
            'search'
        ]);
        $perPage = (int) $request->input('per_page', 10);

        $sessions = $this->attendanceService->getTodaySessions($filters, $perPage);

        return $this->successResponse(
            SessionAttendanceResource::collection($sessions->items()),
            'Daftar Sesi Presensi Terapi',
            200,
            $sessions
        );
    }

    public function rescheduledSessions(Request $request): JsonResponse
    {
        $filters = $request->only(['date', 'search']);
        $perPage = (int) $request->input('per_page', 10);

        $reschedules = $this->attendanceService->getRescheduledSessions($filters, $perPage);

        return $this->successResponse(
            RescheduleResource::collection($reschedules->items()),
            'Daftar Sesi Reschedule Terapi',
            200,
            $reschedules
        );
    }

    public function markAttendance(MarkAttendanceRequest $request, TherapySession $session): JsonResponse
    {
        $updated = $this->attendanceService->markAttendance($session, $request->validated());

        return $this->successResponse(
            new SessionAttendanceResource($updated),
            'Presensi berhasil disimpan',
            200
        );
    }

    public function updateAttendance(UpdateAttendanceRequest $request, TherapySession $session): JsonResponse
    {
        $updated = $this->attendanceService->updateAttendance($session, $request->validated());

        return $this->successResponse(
            new SessionAttendanceResource($updated),
            'Presensi berhasil diperbarui',
            200
        );
    }

    public function getQrCode(TherapySession $session): JsonResponse
    {
        $token = $this->attendanceService->generateQrToken($session);

        return $this->successResponse([
            'session_id' => $session->id,
            'attendance_token' => $token,
            'qr_payload' => json_encode(['token' => $token, 'session_id' => $session->id]),
        ], 'Token QR Code Presensi', 200);
    }

    public function scanQrCode(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $session = $this->attendanceService->processQrScan($request->token);

            return $this->successResponse(
                new SessionAttendanceResource($session),
                'Presensi QR Code berhasil dicatat',
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 400);
        }
    }

    public function export(ExportAttendanceRequest $request)
    {
        $user = $request->user();
        $filters = $request->validated();

        ['pdf' => $pdf, 'file_name' => $fileName] = $this->attendanceService->export($user, $filters);

        return $pdf->stream($fileName);
    }
}
