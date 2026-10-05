<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendanceExport;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Http\Requests\MarkAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Resources\RescheduleResource;
use App\Http\Resources\SessionAttendanceResource;
use App\Models\TherapySession;
use App\Services\AttendanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    use ResponseFormatter;

    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function todaySessions(Request $request): JsonResponse
    {
        $filters = $request->only(['date', 'therapist_id', 'therapy_type', 'status', 'search']);
        $perPage = (int) $request->input('per_page', 10);

        $sessions = $this->attendanceService->getTodaySessions($filters, $perPage);

        return $this->successResponse([
            'items' => SessionAttendanceResource::collection($sessions->items()),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
                'from' => $sessions->firstItem(),
                'to' => $sessions->lastItem(),
            ],
            'links' => [
                'first' => $sessions->url(1),
                'last' => $sessions->url($sessions->lastPage()),
                'prev' => $sessions->previousPageUrl(),
                'next' => $sessions->nextPageUrl(),
            ],
        ], 'Daftar Sesi Presensi Terapi', 200);
    }

    public function rescheduledSessions(Request $request): JsonResponse
    {
        $filters = $request->only(['date', 'search']);
        $perPage = (int) $request->input('per_page', 10);

        $reschedules = $this->attendanceService->getRescheduledSessions($filters, $perPage);

        return $this->successResponse([
            'items' => RescheduleResource::collection($reschedules->items()),
            'meta' => [
                'current_page' => $reschedules->currentPage(),
                'last_page' => $reschedules->lastPage(),
                'per_page' => $reschedules->perPage(),
                'total' => $reschedules->total(),
                'from' => $reschedules->firstItem(),
                'to' => $reschedules->lastItem(),
            ],
            'links' => [
                'first' => $reschedules->url(1),
                'last' => $reschedules->url($reschedules->lastPage()),
                'prev' => $reschedules->previousPageUrl(),
                'next' => $reschedules->nextPageUrl(),
            ],
        ], 'Daftar Sesi Reschedule Terapi', 200);
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

    public function exportAttendance(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'therapy_type', 'therapist_id', 'child_id', 'status']);
        $format = strtolower($request->input('format', 'excel'));

        $filename = 'rekap_kehadiran_' . date('Y-m-d_His');

        if ($format === 'pdf') {
            $export = new AttendanceExport($filters);
            $sessions = $export->collection();
            $pdf = Pdf::loadView('exports.attendance_pdf', compact('sessions'))
                ->setPaper('a4', 'landscape');

            return $pdf->download($filename . '.pdf');
        }

        return Excel::download(new AttendanceExport($filters), $filename . '.xlsx');
    }
}
