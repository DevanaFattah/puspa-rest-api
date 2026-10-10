<?php

namespace App\Services;

use App\Models\Child;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\SessionCredit;
use App\Models\TherapySession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinanceService
{
    /**
     * Get list of children with their primary guardian for the frontend dropdown.
     */
    public function getChildrenWithGuardians()
    {
        return Child::with(['family.guardians'])->get()->map(function ($child) {
            $guardian = $child->family?->guardians?->first();
            return [
                'child_id' => $child->id,
                'child_name' => $child->child_name,
                'guardian_id' => $guardian?->id,
                'guardian_name' => $guardian?->guardian_name ?? 'Tanpa Wali',
            ];
        });
    }

    /**
     * List invoices with filtering, search, and pagination.
     */
    public function getInvoices(array $filters)
    {
        $query = Invoice::with(['child', 'guardian', 'items', 'payments', 'sessionCredit']);

        // Tab Status Filter
        if (!empty($filters['status_tab']) && $filters['status_tab'] !== 'Semua') {
            $tab = $filters['status_tab'];
            if ($tab === 'Belum dibayar') {
                $query->where('status', 'BELUM DIBAYAR');
            } elseif ($tab === 'Berjalan') {
                $query->where('type', 'Paket Durasi')->where('status', 'LUNAS');
            } elseif ($tab === 'Periode berakhir') {
                $query->where('type', 'Bulanan')->where('status', 'LUNAS');
            } elseif ($tab === 'Selesai') {
                $query->whereIn('status', ['DIKEMBALIKAN', 'PERIODE BERIKUTNYA']);
            }
        }

        // Tipe Filter
        if (!empty($filters['tipe']) && $filters['tipe'] !== 'Tipe') {
            $query->where('type', $filters['tipe']);
        }

        // Tahun Filter
        if (!empty($filters['tahun']) && $filters['tahun'] !== 'Tahun') {
            $tahun = $filters['tahun'];
            $query->where(function ($q) use ($tahun) {
                $q->where('period_label', 'like', "%{$tahun}%")
                  ->orWhereYear('issued_date', $tahun);
            });
        }

        // Bulan Filter
        if (!empty($filters['bulan']) && $filters['bulan'] !== 'Bulan') {
            $bulan = $filters['bulan'];
            $query->where('period_label', 'like', "%{$bulan}%");
        }

        // Search Keyword
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('no_invoice', 'like', "%{$search}%")
                  ->orWhereHas('child', function ($cq) use ($search) {
                      $cq->where('child_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('guardian', function ($gq) use ($search) {
                      $gq->where('guardian_name', 'like', "%{$search}%");
                  });
            });
        }

        // Stats summary calculation before pagination
        $allInvoices = (clone $query)->get();
        $totalUnpaid = Invoice::where('status', 'BELUM DIBAYAR')->sum('total_amount');
        $periodeBerakhirCount = Invoice::where('type', 'Bulanan')->where('status', 'LUNAS')->count();
        $nextDiscountAmount = SessionCredit::where('is_used', false)->sum('credit_amount');

        $perPage = $filters['per_page'] ?? 10;
        $paginated = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return [
            'summary' => [
                'jumlah_tagihan' => $allInvoices->count(),
                'total_belum_dibayar' => (float) $totalUnpaid,
                'periode_berakhir' => $periodeBerakhirCount,
                'potongan_berikutnya' => (float) $nextDiscountAmount,
            ],
            'invoices' => $paginated,
        ];
    }

    /**
     * Create invoice with line items and auto number generation.
     */
    public function createInvoice(array $data)
    {
        return DB::transaction(function () use ($data) {
            $type = $data['type'] ?? 'Bulanan';
            $childId = $data['child_id'];
            $guardianId = $data['guardian_id'];
            
            // Auto generate Invoice Number
            $noInvoice = $this->generateInvoiceNumber($type);

            $periodLabel = $data['period_label'] ?? '—';
            $startDate = $data['start_date'] ?? null;
            $endDate = $data['end_date'] ?? null;
            $discount = (float) ($data['discount'] ?? 0);
            $notes = $data['notes'] ?? null;
            $issuedDate = $data['issued_date'] ?? now()->toDateString();

            // Calculate Subtotal from Items
            $itemsData = $data['items'] ?? [];
            $subtotal = 0;
            foreach ($itemsData as $item) {
                $sessCount = (int) ($item['session_count'] ?? 1);
                $price = (float) ($item['price_per_session'] ?? 0);
                $subtotal += ($sessCount * $price);
            }

            $total = max(0, $subtotal - $discount);

            $invoice = Invoice::create([
                'no_invoice' => $noInvoice,
                'child_id' => $childId,
                'guardian_id' => $guardianId,
                'type' => $type,
                'period_label' => $periodLabel,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'status' => 'BELUM DIBAYAR',
                'issued_date' => $issuedDate,
                'notes' => $notes,
            ]);

            // Save Line Items
            foreach ($itemsData as $item) {
                $sessCount = (int) ($item['session_count'] ?? 1);
                $price = (float) ($item['price_per_session'] ?? 0);
                $itemSubtotal = $sessCount * $price;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'therapy_type' => $item['therapy_type'],
                    'session_count' => $sessCount,
                    'price_per_session' => $price,
                    'subtotal' => $itemSubtotal,
                ]);
            }

            // Mark unused credit as used if discount was applied
            if ($discount > 0) {
                $credit = SessionCredit::where('child_id', $childId)
                    ->where('is_used', false)
                    ->first();
                if ($credit) {
                    $credit->update([
                        'is_used' => true,
                        'used_in_invoice_id' => $invoice->id,
                    ]);
                }
            }

            return $invoice->load(['child', 'guardian', 'items']);
        });
    }

    /**
     * Update invoice details and items.
     */
    public function updateInvoice(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $invoice = Invoice::findOrFail($id);

            $type = $data['type'] ?? $invoice->type;
            $childId = $data['child_id'] ?? $invoice->child_id;
            $guardianId = $data['guardian_id'] ?? $invoice->guardian_id;
            $periodLabel = $data['period_label'] ?? $invoice->period_label;
            $startDate = $data['start_date'] ?? $invoice->start_date;
            $endDate = $data['end_date'] ?? $invoice->end_date;
            $discount = isset($data['discount']) ? (float) $data['discount'] : $invoice->discount_amount;
            $notes = $data['notes'] ?? $invoice->notes;

            $itemsData = $data['items'] ?? [];
            if (!empty($itemsData)) {
                $subtotal = 0;
                // Delete existing items
                InvoiceItem::where('invoice_id', $invoice->id)->delete();

                foreach ($itemsData as $item) {
                    $sessCount = (int) ($item['session_count'] ?? 1);
                    $price = (float) ($item['price_per_session'] ?? 0);
                    $itemSubtotal = $sessCount * $price;
                    $subtotal += $itemSubtotal;

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'therapy_type' => $item['therapy_type'],
                        'session_count' => $sessCount,
                        'price_per_session' => $price,
                        'subtotal' => $itemSubtotal,
                    ]);
                }
                $invoice->subtotal_amount = $subtotal;
            }

            $invoice->type = $type;
            $invoice->child_id = $childId;
            $invoice->guardian_id = $guardianId;
            $invoice->period_label = $periodLabel;
            $invoice->start_date = $startDate;
            $invoice->end_date = $endDate;
            $invoice->discount_amount = $discount;
            $invoice->total_amount = max(0, $invoice->subtotal_amount - $discount);
            $invoice->notes = $notes;
            $invoice->save();

            return $invoice->load(['child', 'guardian', 'items']);
        });
    }

    /**
     * Delete an invoice.
     */
    public function deleteInvoice(string $id)
    {
        $invoice = Invoice::findOrFail($id);
        return $invoice->delete();
    }

    /**
     * Record payment for an invoice ("Catat Bayar").
     */
    public function payInvoice(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $invoice = Invoice::findOrFail($id);

            $paymentDate = $data['payment_date'] ?? now()->toDateString();
            $method = $data['payment_method'] ?? 'Transfer bank';
            $receiptNumber = $data['receipt_number'] ?? null;
            $proofFilePath = $data['proof_file_path'] ?? null;

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'child_id' => $invoice->child_id,
                'guardian_id' => $invoice->guardian_id,
                'transaction_type' => 'Pemasukan',
                'payment_method' => $method,
                'amount' => $invoice->total_amount,
                'receipt_number' => $receiptNumber,
                'proof_file_path' => $proofFilePath,
                'payment_date' => $paymentDate,
                'notes' => 'Pelunasan tagihan ' . $invoice->no_invoice,
            ]);

            $invoice->update(['status' => 'LUNAS']);

            return $payment;
        });
    }

    /**
     * Calculate session attendance for refund modal from therapy_sessions table.
     */
    public function getAttendanceForRefund(string $invoiceId)
    {
        $invoice = Invoice::with('items')->findOrFail($invoiceId);
        $totalSessions = $invoice->items->sum('session_count') ?: 8;

        // Count present sessions for child
        $presentCount = TherapySession::whereHas('schedule', function ($q) use ($invoice) {
            $q->where('child_id', $invoice->child_id);
        })->where('status', 'present')->count();

        // Ensure within 0 and totalSessions
        $calculatedPresent = min($totalSessions, max(0, $presentCount > 0 ? $presentCount : $totalSessions));

        return [
            'invoice_id' => $invoice->id,
            'child_id' => $invoice->child_id,
            'child_name' => $invoice->child->child_name ?? '',
            'total_sessions' => $totalSessions,
            'present_sessions' => $calculatedPresent,
            'harga_per_sesi' => $totalSessions > 0 ? round($invoice->total_amount / $totalSessions) : 0,
        ];
    }

    /**
     * Process Refund or Carryover ("Pengembalian Dana").
     */
    public function processRefundOrCredit(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $invoice = Invoice::findOrFail($id);

            $mode = $data['mode']; // 'dikembalikan' or 'catat'
            $kehadiran = (int) ($data['kehadiran'] ?? 8);
            $totalSesi = (int) ($data['total_sesi'] ?? 8);
            $sisaSesi = max(0, $totalSesi - $kehadiran);
            $hargaPerSesi = $totalSesi > 0 ? ($invoice->total_amount / $totalSesi) : 0;
            $nilaiSisaSesi = $sisaSesi * $hargaPerSesi;

            $tanggal = $data['tanggal'] ?? now()->toDateString();

            if ($mode === 'dikembalikan') {
                $metode = $data['metode_pengembalian'] ?? 'Transfer bank';
                $noResi = $data['no_resi'] ?? null;
                $proofFilePath = $data['proof_file_path'] ?? null;

                // Record cash outflow
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'child_id' => $invoice->child_id,
                    'guardian_id' => $invoice->guardian_id,
                    'transaction_type' => 'Pengembalian dana',
                    'payment_method' => $metode,
                    'amount' => $nilaiSisaSesi,
                    'receipt_number' => $noResi,
                    'proof_file_path' => $proofFilePath,
                    'payment_date' => $tanggal,
                    'notes' => "Pengembalian dana sisa {$sisaSesi} sesi",
                ]);

                $invoice->update(['status' => 'DIKEMBALIKAN']);
            } else {
                // Record to session_credits table
                SessionCredit::create([
                    'child_id' => $invoice->child_id,
                    'guardian_id' => $invoice->guardian_id,
                    'source_invoice_id' => $invoice->id,
                    'unused_session_count' => $sisaSesi,
                    'credit_amount' => $nilaiSisaSesi,
                    'is_used' => false,
                ]);

                // Record flow indicator in payment log
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'child_id' => $invoice->child_id,
                    'guardian_id' => $invoice->guardian_id,
                    'transaction_type' => 'Ke periode berikutnya',
                    'payment_method' => '-',
                    'amount' => $nilaiSisaSesi,
                    'payment_date' => $tanggal,
                    'notes' => "Sisa {$sisaSesi} sesi dialihkan ke periode berikutnya",
                ]);

                $invoice->update(['status' => 'PERIODE BERIKUTNYA']);
            }

            return $invoice;
        });
    }

    /**
     * Get list of active session credits for next invoices.
     */
    public function getCredits()
    {
        $credits = SessionCredit::with(['child', 'guardian', 'sourceInvoice'])
            ->where('is_used', false)
            ->get();

        return [
            'count_anak' => $credits->unique('child_id')->count(),
            'total_potongan' => (float) $credits->sum('credit_amount'),
            'items' => $credits->map(function ($c) {
                return [
                    'id' => $c->id,
                    'child_id' => $c->child_id,
                    'anak' => $c->child->child_name ?? 'Anak',
                    'guardian_id' => $c->guardian_id,
                    'wali' => $c->guardian->guardian_name ?? 'Wali',
                    'jumlah' => (float) $c->credit_amount,
                    'unused_sessions' => $c->unused_session_count,
                    'source_invoice_no' => $c->sourceInvoice->no_invoice ?? '—',
                ];
            }),
        ];
    }

    /**
     * Get cash flow transaction log with filters and pagination.
     */
    public function getTransactions(array $filters)
    {
        $query = Payment::with(['child', 'guardian', 'invoice']);

        if (!empty($filters['metode']) && $filters['metode'] !== 'Semua metode') {
            $query->where('payment_method', $filters['metode']);
        }

        if (!empty($filters['tahun']) && $filters['tahun'] !== 'Semua tahun') {
            $tahun = str_replace('Tahun ', '', $filters['tahun']);
            $query->whereYear('payment_date', $tahun);
        }

        if (!empty($filters['bulan']) && $filters['bulan'] !== 'Semua bulan') {
            $monthNames = [
                'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
                'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
                'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
            ];
            if (isset($monthNames[$filters['bulan']])) {
                $query->whereMonth('payment_date', $monthNames[$filters['bulan']]);
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('child', function ($cq) use ($search) {
                    $cq->where('child_name', 'like', "%{$search}%");
                })
                ->orWhereHas('guardian', function ($gq) use ($search) {
                    $gq->where('guardian_name', 'like', "%{$search}%");
                })
                ->orWhereHas('invoice', function ($iq) use ($search) {
                    $iq->where('no_invoice', 'like', "%{$search}%");
                });
            });
        }

        $allPayments = (clone $query)->get();
        $totalPemasukan = $allPayments->where('transaction_type', 'Pemasukan')->sum('amount');
        $totalPengembalian = $allPayments->where('transaction_type', 'Pengembalian dana')->sum('amount');

        $perPage = $filters['per_page'] ?? 10;
        $paginated = $query->orderBy('payment_date', 'desc')->paginate($perPage);

        return [
            'summary' => [
                'jumlah_transaksi' => $allPayments->count(),
                'total_pemasukan' => (float) $totalPemasukan,
                'total_pengembalian' => (float) $totalPengembalian,
            ],
            'transactions' => $paginated,
        ];
    }

    /**
     * Generate sequential invoice number.
     */
    private function generateInvoiceNumber(string $type): string
    {
        $year = now()->year;
        $month = str_pad(now()->month, 2, '0', STR_PAD_LEFT);

        if ($type === 'Paket Durasi') {
            $count = Invoice::where('type', 'Paket Durasi')->whereYear('created_at', $year)->count() + 1;
            return sprintf("INV/%d/PKT/%04d", $year, $count);
        }

        $count = Invoice::where('type', 'Bulanan')->whereYear('created_at', $year)->whereMonth('created_at', $month)->count() + 1;
        return sprintf("INV/%d/%s/%04d", $year, $month, $count);
    }
}
