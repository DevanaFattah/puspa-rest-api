<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseFormatter;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinanceController extends Controller
{
    use ResponseFormatter;

    protected FinanceService $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    /**
     * Get children & guardians mapping.
     */
    public function childrenWithGuardians()
    {
        try {
            $data = $this->financeService->getChildrenWithGuardians();
            return $this->successResponse($data, 'Berhasil mengambil data anak dan wali');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 500);
        }
    }

    /**
     * Get list of invoices.
     */
    public function indexInvoices(Request $request)
    {
        try {
            $filters = $request->only(['status_tab', 'tipe', 'tahun', 'bulan', 'search', 'per_page', 'page']);
            $result = $this->financeService->getInvoices($filters);
            return $this->successResponse($result, 'Berhasil mengambil daftar tagihan');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 500);
        }
    }

    /**
     * Create invoice.
     */
    public function storeInvoice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|string',
            'guardian_id' => 'required|string',
            'type' => 'required|in:Bulanan,Paket Durasi',
            'period_label' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.therapy_type' => 'required|string',
            'items.*.session_count' => 'required|numeric',
            'items.*.price_per_session' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', $validator->errors()->toArray(), 422);
        }

        try {
            $invoice = $this->financeService->createInvoice($request->all());
            return $this->successResponse($invoice, 'Tagihan berhasil dibuat', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 500);
        }
    }

    /**
     * Update invoice.
     */
    public function updateInvoice(Request $request, string $id)
    {
        try {
            $invoice = $this->financeService->updateInvoice($id, $request->all());
            return $this->successResponse($invoice, 'Tagihan berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 500);
        }
    }

    /**
     * Delete invoice.
     */
    public function deleteInvoice(string $id)
    {
        try {
            $this->financeService->deleteInvoice($id);
            return $this->successResponse(null, 'Tagihan berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 500);
        }
    }

    /**
     * Catat pembayaran.
     */
    public function payInvoice(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'receipt_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', $validator->errors()->toArray(), 422);
        }

        try {
            $data = $request->all();
            if ($request->hasFile('bukti_file')) {
                $file = $request->file('bukti_file');
                $path = $file->store('payments', 'public');
                $data['proof_file_path'] = $path;
            }

            $payment = $this->financeService->payInvoice($id, $data);
            return $this->successResponse($payment, 'Pembayaran berhasil dicatat');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 500);
        }
    }

    /**
     * Attendance count for refund modal.
     */
    public function attendanceForRefund(string $id)
    {
        try {
            $data = $this->financeService->getAttendanceForRefund($id);
            return $this->successResponse($data, 'Berhasil menghitung kehadiran sesi');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 500);
        }
    }

    /**
     * Process refund or session carryover.
     */
    public function refundInvoice(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'mode' => 'required|in:dikembalikan,catat',
            'kehadiran' => 'required|numeric',
            'total_sesi' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', $validator->errors()->toArray(), 422);
        }

        try {
            $data = $request->all();
            if ($request->hasFile('bukti_file')) {
                $file = $request->file('bukti_file');
                $path = $file->store('payments', 'public');
                $data['proof_file_path'] = $path;
            }

            $result = $this->financeService->processRefundOrCredit($id, $data);
            return $this->successResponse($result, 'Pengembalian / pencatatan sisa sesi berhasil diproses');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 500);
        }
    }

    /**
     * Active credits list for next invoices.
     */
    public function credits()
    {
        try {
            $data = $this->financeService->getCredits();
            return $this->successResponse($data, 'Berhasil mengambil daftar potongan sesi');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 500);
        }
    }

    /**
     * Cash flow transactions log.
     */
    public function transactions(Request $request)
    {
        try {
            $filters = $request->only(['metode', 'tahun', 'bulan', 'search', 'per_page', 'page']);
            $data = $this->financeService->getTransactions($filters);
            return $this->successResponse($data, 'Berhasil mengambil riwayat transaksi');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 500);
        }
    }
}
