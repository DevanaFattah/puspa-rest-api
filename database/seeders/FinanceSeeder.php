<?php

namespace Database\Seeders;

use App\Models\Child;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\SessionCredit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Symfony\Component\Uid\Ulid;

class FinanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil data anak yang memiliki family dan guardian
        $children = Child::with(['family.guardians'])->has('family.guardians')->get();

        if ($children->isEmpty()) {
            $this->command->warn('Tidak ada data anak dengan wali yang valid. Jalankan ScheduleSeeder terlebih dahulu.');
            return;
        }

        $therapyTypes = [
            'Terapi Wicara' => 150000,
            'Terapi Okupasi' => 150000,
            'Fisioterapi' => 175000,
            'PLB (Paedagog)' => 125000,
        ];

        $now = Carbon::now();
        $invoiceCounter = 1;

        // Iterasi pada sebagian anak (misal 15 anak pertama) untuk ragam skenario keuangan
        foreach ($children->slice(0, 15) as $index => $child) {
            $guardian = $child->family->guardians->first();
            if (!$guardian) {
                continue;
            }

            // Skenario 1: Invoice Lunas bulan lalu (dengan bukti transfer & payment)
            $lastMonth = $now->copy()->subMonth();
            $periodLabel = $lastMonth->translatedFormat('F Y');
            $noInvoice = sprintf('INV/%s/%04d', $lastMonth->format('Y/m'), $invoiceCounter++);

            // Buat invoice items
            $item1Type = 'Terapi Wicara';
            $item1Count = 4;
            $item1Price = $therapyTypes[$item1Type];
            $item1Subtotal = $item1Count * $item1Price;

            $item2Type = 'Terapi Okupasi';
            $item2Count = 4;
            $item2Price = $therapyTypes[$item2Type];
            $item2Subtotal = $item2Count * $item2Price;

            $subtotal = $item1Subtotal + $item2Subtotal;
            $discount = ($index % 3 === 0) ? 50000 : 0; // Sebagian dapat potongan
            $total = $subtotal - $discount;

            $invLunas = Invoice::create([
                'id' => (string) Ulid::generate(),
                'no_invoice' => $noInvoice,
                'child_id' => $child->id,
                'guardian_id' => $guardian->id,
                'type' => 'Bulanan',
                'period_label' => $periodLabel,
                'start_date' => $lastMonth->copy()->startOfMonth()->toDateString(),
                'end_date' => $lastMonth->copy()->endOfMonth()->toDateString(),
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'status' => 'LUNAS',
                'issued_date' => $lastMonth->copy()->startOfMonth()->toDateString(),
                'notes' => 'Tagihan terapi bulan ' . $periodLabel,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invLunas->id,
                'therapy_type' => $item1Type,
                'session_count' => $item1Count,
                'price_per_session' => $item1Price,
                'subtotal' => $item1Subtotal,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invLunas->id,
                'therapy_type' => $item2Type,
                'session_count' => $item2Count,
                'price_per_session' => $item2Price,
                'subtotal' => $item2Subtotal,
            ]);

            Payment::create([
                'invoice_id' => $invLunas->id,
                'child_id' => $child->id,
                'guardian_id' => $guardian->id,
                'transaction_type' => 'Pemasukan',
                'payment_method' => ($index % 2 === 0) ? 'Transfer bank' : 'QRIS',
                'amount' => $total,
                'receipt_number' => 'RESI-' . rand(100000, 999999),
                'proof_file_path' => 'payments/dummy-proof.jpg',
                'payment_date' => $lastMonth->copy()->addDays(2)->toDateString(),
                'notes' => 'Pembayaran lunas via transfer',
            ]);

            // Skenario 2: Invoice Bulan Ini
            // Variasi status: Belum dibayar, Berjalan, Ke Periode Berikutnya, atau Dikembalikan
            $currentMonth = $now->copy();
            $currPeriodLabel = $currentMonth->translatedFormat('F Y');
            $currNoInvoice = sprintf('INV/%s/%04d', $currentMonth->format('Y/m'), $invoiceCounter++);

            $statusOptions = ['BELUM DIBAYAR', 'BELUM DIBAYAR', 'LUNAS', 'PERIODE BERIKUTNYA', 'DIKEMBALIKAN'];
            $chosenStatus = $statusOptions[$index % count($statusOptions)];

            $currSubtotal = 4 * 150000;
            $currDiscount = 0;
            $currTotal = $currSubtotal;

            $invCurrent = Invoice::create([
                'id' => (string) Ulid::generate(),
                'no_invoice' => $currNoInvoice,
                'child_id' => $child->id,
                'guardian_id' => $guardian->id,
                'type' => 'Bulanan',
                'period_label' => $currPeriodLabel,
                'start_date' => $currentMonth->copy()->startOfMonth()->toDateString(),
                'end_date' => $currentMonth->copy()->endOfMonth()->toDateString(),
                'subtotal_amount' => $currSubtotal,
                'discount_amount' => $currDiscount,
                'total_amount' => $currTotal,
                'status' => $chosenStatus,
                'issued_date' => $currentMonth->copy()->startOfMonth()->toDateString(),
                'notes' => 'Tagihan terapi bulan ' . $currPeriodLabel,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invCurrent->id,
                'therapy_type' => 'Terapi Wicara',
                'session_count' => 4,
                'price_per_session' => 150000,
                'subtotal' => 600000,
            ]);

            if ($chosenStatus === 'LUNAS') {
                Payment::create([
                    'invoice_id' => $invCurrent->id,
                    'child_id' => $child->id,
                    'guardian_id' => $guardian->id,
                    'transaction_type' => 'Pemasukan',
                    'payment_method' => 'Transfer bank',
                    'amount' => $currTotal,
                    'receipt_number' => 'RESI-' . rand(100000, 999999),
                    'payment_date' => $currentMonth->copy()->addDays(1)->toDateString(),
                    'notes' => 'Pembayaran awal bulan',
                ]);
            } elseif ($chosenStatus === 'PERIODE BERIKUTNYA') {
                // Ada sisa 1 sesi tidak terpakai yang dikonversi ke kredit saldo
                Payment::create([
                    'invoice_id' => $invCurrent->id,
                    'child_id' => $child->id,
                    'guardian_id' => $guardian->id,
                    'transaction_type' => 'Ke periode berikutnya',
                    'payment_method' => '-',
                    'amount' => 150000,
                    'payment_date' => $currentMonth->copy()->addDays(5)->toDateString(),
                    'notes' => 'Sisa 1 sesi dialihkan ke periode berikutnya',
                ]);

                SessionCredit::create([
                    'child_id' => $child->id,
                    'guardian_id' => $guardian->id,
                    'source_invoice_id' => $invCurrent->id,
                    'unused_session_count' => 1,
                    'credit_amount' => 150000,
                    'is_used' => false,
                ]);
            } elseif ($chosenStatus === 'DIKEMBALIKAN') {
                // Ada refund dana ke orang tua
                Payment::create([
                    'invoice_id' => $invCurrent->id,
                    'child_id' => $child->id,
                    'guardian_id' => $guardian->id,
                    'transaction_type' => 'Pengembalian dana',
                    'payment_method' => 'Transfer bank',
                    'amount' => 150000,
                    'payment_date' => $currentMonth->copy()->addDays(7)->toDateString(),
                    'notes' => 'Pengembalian dana sesi yang berhalangan',
                ]);
            }
        }
    }
}
