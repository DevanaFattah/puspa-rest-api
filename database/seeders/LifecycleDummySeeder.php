<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Assessment;
use App\Models\AssessmentDetail;
use App\Models\Child;
use App\Models\Family;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Observation;
use App\Models\ObservationAnswer;
use App\Models\Payment;
use App\Models\Reschedule;
use App\Models\Schedule;
use App\Models\SessionCredit;
use App\Models\Therapist;
use App\Models\TherapySession;
use App\Models\TherapySoap;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LifecycleDummySeeder extends Seeder
{
    /**
     * Run the lifecycle-compliant dummy data seeder.
     * Mengimplementasikan 5 skenario persona yang mencakup seluruh 6 fase lifecycle Puspa Holistic.
     */
    public function run(): void
    {
        $this->command->info('Memulai seeding skenario lifecycle Puspa Holistic...');

        $admin = Admin::first();
        if (!$admin) {
            $this->command->error('Admin belum ada. Jalankan AdminSeeder terlebih dahulu.');
            return;
        }

        // Terapis per divisi
        $therapistFisio = Therapist::where('therapist_section', 'fisio')->first();
        $therapistOkupasi = Therapist::where('therapist_section', 'okupasi')->first();
        $therapistWicara = Therapist::where('therapist_section', 'wicara')->first();
        $therapistPaedagog = Therapist::where('therapist_section', 'paedagog')->first();

        $defaultTherapist = $therapistFisio ?? Therapist::first();
        if (!$defaultTherapist) {
            $this->command->error('Therapist belum ada. Jalankan TherapistSeeder terlebih dahulu.');
            return;
        }

        // Bersihkan data persona lama agar idempoten dan tidak menduplikasi anak/data terkait
        $this->cleanOldLifecycleData();

        // =========================================================================
        // SKENARIO 1: Persona 1 - "Budi Santoso & Arjuna" (Happy Path - Full Lifecycle)
        // Fase 1 Lulus -> Fase 2 Asesmen Selesai -> Fase 3 Jadwal Aktif ->
        // Fase 4 Presensi (Hadir) + Fase 5 SOAP Terisi -> Fase 6 Invoice Lunas & Berjalan
        // =========================================================================
        $this->command->info('1. Membuat Persona 1: Budi Santoso & Arjuna (Full Lifecycle Flow)...');
        $this->seedPersona1($admin, $therapistWicara ?? $defaultTherapist, $therapistOkupasi ?? $defaultTherapist);

        // =========================================================================
        // SKENARIO 2: Persona 2 - "Tri Wahyuni & Salsabila" (Fase 1 - Tidak Lanjut)
        // Observasi Selesai -> Rekomendasi/Inklusi: TIDAK LANJUT -> Ortu TIDAK BISA LOGIN
        // Tidak ada asesmen & tidak ada jadwal terapi
        // =========================================================================
        $this->command->info('2. Membuat Persona 2: Tri Wahyuni & Salsabila (Fase 1 - Tidak Lanjut)...');
        $this->seedPersona2($admin, $defaultTherapist);

        // =========================================================================
        // SKENARIO 3: Persona 3 - "Ahmad Fauzi & Zainab" (Fase 2 - Sedang Asesmen)
        // Observasi Lulus -> Asesmen Terjadwal -> Akun Ortu AKTIF (Bisa login isi kuesioner)
        // Asesor sedang mengisi instrumen -> Belum ada jadwal terapi
        // =========================================================================
        $this->command->info('3. Membuat Persona 3: Ahmad Fauzi & Zainab (Fase 2 - Sedang Asesmen)...');
        $this->seedPersona3($admin, $therapistPaedagog ?? $defaultTherapist, $therapistFisio ?? $defaultTherapist);

        // =========================================================================
        // SKENARIO 4: Persona 4 - "Devi Ema & Rayhan" (Fase 3 & 4 - Reschedule & Sakit)
        // Jadwal Terapi Berjalan -> Ada Presensi Hadir (SOAP), Ada Sakit -> Reschedule
        // (Sesi awal dibatalkan, Sesi baru terjadwal, Relasi Reschedule tercatat)
        // =========================================================================
        $this->command->info('4. Membuat Persona 4: Devi Ema & Rayhan (Fase 3 & 4 - Reschedule & Sakit)...');
        $this->seedPersona4($admin, $therapistFisio ?? $defaultTherapist, $therapistPaedagog ?? $defaultTherapist);

        // =========================================================================
        // SKENARIO 5: Persona 5 - "Nugroho & Kezia" (Fase 6 - Session Credit & Refund)
        // Tagihan dengan sisa sesi tidak terpakai, refund dana, dan SessionCredit ke periode baru
        // =========================================================================
        $this->command->info('5. Membuat Persona 5: Nugroho & Kezia (Fase 6 - Session Credit & Refund)...');
        $this->seedPersona5($admin, $therapistOkupasi ?? $defaultTherapist);

        $this->command->info('Semua skenario lifecycle berhasil dibuat sesuai standar!');
    }

    /**
     * Persona 1: Full Lifecycle Happy Path
     */
    private function seedPersona1(Admin $admin, Therapist $therapist1, Therapist $therapist2): void
    {
        $family = Family::create([]);

        $user = User::firstOrCreate(
            ['username' => 'ortuArjuna'],
            [
                'email'             => 'budi.arjuna@puspa.test',
                'password'          => Hash::make('Puspa123.'),
                'is_active'         => true, // Aktif karena sudah lolos observasi & dijadwalkan asesmen
                'email_verified_at' => Carbon::now()->subMonths(2),
            ]
        );
        $user->assignRole('user');

        $guardian = Guardian::firstOrCreate(
            ['family_id' => $family->id, 'guardian_name' => 'Budi Santoso'],
            [
                'user_id'             => $user->id,
                'guardian_type'       => 'ayah',
                'guardian_phone'      => '081234567001',
                'guardian_birth_date' => Carbon::now()->subYears(38)->toDateString(),
                'guardian_occupation' => 'Wiraswasta',
            ]
        );

        $child = Child::firstOrCreate(
            ['family_id' => $family->id, 'child_name' => 'Arjuna Pratama'],
            [
                'child_birth_place'    => 'Surabaya',
                'child_birth_date'     => Carbon::now()->subYears(5)->subMonths(3)->toDateString(),
                'child_gender'         => 'laki-laki',
                'child_address'        => 'Jl. Kertajaya Indah No. 12, Surabaya',
                'child_complaint'      => 'Keterlambatan bicara (speech delay) dan motorik halus',
                'child_school'         => 'TK Bintang Kecil',
                'child_service_choice' => 'Terapi Wicara & Terapi Okupasi',
                'child_religion'       => 'islam',
            ]
        );

        // Fase 1: Observasi (Completed, Inklusi: YA)
        $obsDate = Carbon::now()->subMonths(2)->startOfMonth()->addDays(3);
        $observation = Observation::create([
            'child_id'                   => $child->id,
            'admin_id'                   => $admin->id,
            'therapist_id'               => $therapist1->id,
            'scheduled_date'             => $obsDate,
            'age_category'               => 'balita',
            'status'                     => 'completed',
            'completed_at'               => '10:30:00',
            'total_score'                => 85,
            'conclusion'                 => 'Anak kooperatif namun memiliki keterlambatan artikulasi fonem dan fokus visual.',
            'recommendation'             => 'Direkomendasikan intervensi Terapi Wicara intensif dan Terapi Okupasi.',
            'is_continued_to_assessment' => true,
            'created_at'                 => $obsDate->copy()->subDays(2),
            'updated_at'                 => $obsDate,
        ]);

        // Fase 2: Asesmen (Completed, Ortu Completed)
        $asmDate = $obsDate->copy()->addDays(7);
        $assessment = Assessment::create([
            'observation_id'     => $observation->id,
            'child_id'           => $child->id,
            'status'             => 'completed',
            'parent_status'      => 'completed',
            'scheduled_date'     => $asmDate,
            'report_file'        => 'reports/asesmen_arjuna.pdf',
            'report_uploaded_at' => $asmDate->copy()->addDays(2),
            'created_at'         => $asmDate,
            'updated_at'         => $asmDate->copy()->addDays(2),
        ]);

        AssessmentDetail::create([
            'assessment_id'       => $assessment->id,
            'type'                => 'umum',
            'admin_id'            => $admin->id,
            'therapist_id'        => $therapist1->id,
            'completed_at'        => $asmDate->copy()->addHours(2),
            'parent_completed_at' => $asmDate->copy()->addHours(3),
        ]);

        AssessmentDetail::create([
            'assessment_id'       => $assessment->id,
            'type'                => 'wicara',
            'admin_id'            => $admin->id,
            'therapist_id'        => $therapist1->id,
            'completed_at'        => $asmDate->copy()->addHours(4),
            'parent_completed_at' => $asmDate->copy()->addHours(5),
        ]);

        // Fase 3: Jadwal Terapi (Bulan ini)
        $startPeriod = Carbon::now()->startOfMonth();
        $endPeriod = Carbon::now()->endOfMonth();

        $schedule = Schedule::create([
            'child_id'       => $child->id,
            'therapist_id'   => $therapist1->id,
            'therapy_type'   => 'wicara',
            'day_of_week'    => 2, // Selasa
            'start_time'     => '09:00:00',
            'end_time'       => '10:00:00',
            'total_meetings' => 4,
            'period_start'   => $startPeriod->toDateString(),
            'period_end'     => $endPeriod->toDateString(),
            'status'         => 'aktif',
        ]);

        // Fase 4 & 5: Sesi Terapi (Sesi 1: Present + SOAP, Sesi 2: Present + SOAP, Sesi 3 & 4: Pending)
        $session1Date = Carbon::now()->startOfMonth()->next(Carbon::TUESDAY);
        $session1 = TherapySession::create([
            'schedule_id'      => $schedule->id,
            'session_number'   => 1,
            'session_date'     => $session1Date->toDateString(),
            'start_time'       => '09:00:00',
            'end_time'         => '10:00:00',
            'therapist_id'     => $therapist1->id,
            'status'           => 'present',
            'notes'            => 'Hadir tepat waktu, anak sangat antusias.',
            'checked_in_at'    => $session1Date->copy()->setTime(8, 55, 0),
            'attendance_token' => Str::random(32),
        ]);

        TherapySoap::create([
            'therapy_session_id'  => $session1->id,
            'performed_at'        => $session1Date->copy()->setTime(10, 15, 0),
            'therapy_diagnosis'   => 'Speech Delay / Articulation Disorder',
            'interventions'       => 'Oral motor exercise, stimulasi fonem bilabial (p, b, m)',
            'subjective'          => 'Ibu menyampaikan anak mulai mencoba menirukan suku kata di rumah.',
            'objective'           => 'Anak mampu menirukan suara "ba" dan "ma" dengan kontak mata 70%.',
            'assessment'          => 'Respon motorik mulut meningkat dibandingkan saat observasi.',
            'plan'                => 'Lanjutkan stimulasi kata 2 suku kata dan latihan meniup gelembung.',
            'implementation'      => 'Dilakukan oral massage dan repetisi flashcard kosakata bergambar.',
            'advanced_assessment' => 'Prognosis baik dengan keterlibatan aktif orang tua.',
            'advanced_plan'       => 'Home program latihan 15 menit setiap pagi sebelum sekolah.',
        ]);

        $session2Date = $session1Date->copy()->addWeek();
        $session2 = TherapySession::create([
            'schedule_id'      => $schedule->id,
            'session_number'   => 2,
            'session_date'     => $session2Date->toDateString(),
            'start_time'       => '09:00:00',
            'end_time'         => '10:00:00',
            'therapist_id'     => $therapist1->id,
            'status'           => 'present',
            'notes'            => 'Hadir, fokus meningkat.',
            'checked_in_at'    => $session2Date->copy()->setTime(8, 58, 0),
            'attendance_token' => Str::random(32),
        ]);

        TherapySoap::create([
            'therapy_session_id'  => $session2->id,
            'performed_at'        => $session2Date->copy()->setTime(10, 10, 0),
            'therapy_diagnosis'   => 'Speech Delay / Articulation Disorder',
            'interventions'       => 'Stimulasi fonem lingual (t, d, n)',
            'subjective'          => 'Anak ceria dan siap berinteraksi.',
            'objective'           => 'Anak mengikuti instruksi terapis dengan gestur dan repetisi vokal.',
            'assessment'          => 'Penguasaan konsonan bibir stabil, masuk tahap dental.',
            'plan'                => 'Lanjutkan ke variasi kalimat sederhana "mau makan", "mau main".',
            'implementation'      => 'Bermain peran dengan boneka tangan dan kartu kata.',
            'advanced_assessment' => 'Fokus terjaga selama 35 menit berturut-turut.',
            'advanced_plan'       => 'Diskusikan kemajuan saat konsultasi bulanan.',
        ]);

        // Sesi 3 & 4 (Masa datang / pending)
        $session3Date = $session2Date->copy()->addWeek();
        TherapySession::create([
            'schedule_id'      => $schedule->id,
            'session_number'   => 3,
            'session_date'     => $session3Date->toDateString(),
            'start_time'       => '09:00:00',
            'end_time'         => '10:00:00',
            'therapist_id'     => $therapist1->id,
            'status'           => 'pending',
            'attendance_token' => Str::random(32),
        ]);

        $session4Date = $session3Date->copy()->addWeek();
        TherapySession::create([
            'schedule_id'      => $schedule->id,
            'session_number'   => 4,
            'session_date'     => $session4Date->toDateString(),
            'start_time'       => '09:00:00',
            'end_time'         => '10:00:00',
            'therapist_id'     => $therapist1->id,
            'status'           => 'pending',
            'attendance_token' => Str::random(32),
        ]);

        // Fase 6: Keuangan - Invoice Lunas Bulan Lalu
        $lastMonth = Carbon::now()->subMonth();
        $invLunas = Invoice::create([
            'no_invoice'      => sprintf('INV-LC/%s/%04d', $lastMonth->format('Y/m'), rand(1000, 9999)),
            'child_id'        => $child->id,
            'guardian_id'     => $guardian->id,
            'type'            => 'Bulanan',
            'period_label'    => $lastMonth->translatedFormat('F Y'),
            'start_date'      => $lastMonth->copy()->startOfMonth()->toDateString(),
            'end_date'        => $lastMonth->copy()->endOfMonth()->toDateString(),
            'subtotal_amount' => 600000,
            'discount_amount' => 0,
            'total_amount'    => 600000,
            'status'          => 'LUNAS',
            'issued_date'     => $lastMonth->copy()->startOfMonth()->toDateString(),
            'notes'           => 'Tagihan paket 4 sesi terapi wicara bulan ' . $lastMonth->translatedFormat('F Y'),
        ]);

        InvoiceItem::create([
            'invoice_id'     => $invLunas->id,
            'therapy_type'   => 'Terapi Wicara',
            'session_count'  => 4,
            'price_per_session' => 150000,
            'subtotal'       => 600000,
        ]);

        Payment::create([
            'invoice_id'       => $invLunas->id,
            'child_id'         => $child->id,
            'guardian_id'      => $guardian->id,
            'transaction_type' => 'Pemasukan',
            'payment_method'   => 'Transfer bank',
            'amount'           => 600000,
            'receipt_number'   => 'RCP-ARJUNA-001',
            'payment_date'     => $lastMonth->copy()->startOfMonth()->addDays(2)->toDateString(),
            'notes'            => 'Pembayaran lunas via BCA Virtual Account.',
        ]);

        // Invoice Bulan Ini (Status BERJALAN / BELUM DIBAYAR)
        $thisMonth = Carbon::now();
        $invCurrent = Invoice::create([
            'no_invoice'      => sprintf('INV-LC/%s/%04d', $thisMonth->format('Y/m'), rand(1000, 9999)),
            'child_id'        => $child->id,
            'guardian_id'     => $guardian->id,
            'type'            => 'Bulanan',
            'period_label'    => $thisMonth->translatedFormat('F Y'),
            'start_date'      => $thisMonth->copy()->startOfMonth()->toDateString(),
            'end_date'        => $thisMonth->copy()->endOfMonth()->toDateString(),
            'subtotal_amount' => 600000,
            'discount_amount' => 50000,
            'total_amount'    => 550000,
            'status'          => 'BELUM DIBAYAR',
            'issued_date'     => $thisMonth->copy()->startOfMonth()->toDateString(),
            'notes'           => 'Diskon early bird sebesar Rp 50.000.',
        ]);

        InvoiceItem::create([
            'invoice_id'     => $invCurrent->id,
            'therapy_type'   => 'Terapi Wicara',
            'session_count'  => 4,
            'price_per_session' => 150000,
            'subtotal'       => 600000,
        ]);
    }

    /**
     * Persona 2: Fase 1 Selesai -> Tidak Lanjut Asesmen -> Ortu TIDAK BISA LOGIN
     */
    private function seedPersona2(Admin $admin, Therapist $therapist): void
    {
        $family = Family::create([]);

        // User akun ortu dibuat tetapi TIDAK AKTIF (is_active = false)
        // Menurut standar, ortu baru boleh aktif setelah admin menetapkan jadwal asesmen
        $user = User::firstOrCreate(
            ['username' => 'ortuSalsabila'],
            [
                'email'             => 'tri.salsabila@puspa.test',
                'password'          => Hash::make('Puspa123.'),
                'is_active'         => false, // WAJIB FALSE: Tidak lanjut ke asesmen
                'email_verified_at' => null,
            ]
        );
        $user->assignRole('user');

        $guardian = Guardian::firstOrCreate(
            ['family_id' => $family->id, 'guardian_name' => 'Tri Wahyuni'],
            [
                'user_id'             => $user->id,
                'guardian_type'       => 'ibu',
                'guardian_phone'      => '081234567002',
                'guardian_birth_date' => Carbon::now()->subYears(32)->toDateString(),
                'guardian_occupation' => 'Ibu Rumah Tangga',
            ]
        );

        $child = Child::firstOrCreate(
            ['family_id' => $family->id, 'child_name' => 'Salsabila Putri'],
            [
                'child_birth_place'    => 'Sidoarjo',
                'child_birth_date'     => Carbon::now()->subYears(4)->toDateString(),
                'child_gender'         => 'perempuan',
                'child_address'        => 'Perum Griya Asri C-14, Waru, Sidoarjo',
                'child_complaint'      => 'Orang tua merasa anak pemalu dan jarang bersosialisasi',
                'child_school'         => 'PAUD Kasih Ibu',
                'child_service_choice' => 'Observasi Perkembangan',
                'child_religion'       => 'islam',
            ]
        );

        $obsDate = Carbon::now()->subDays(10);
        Observation::create([
            'child_id'                   => $child->id,
            'admin_id'                   => $admin->id,
            'therapist_id'               => $therapist->id,
            'scheduled_date'             => $obsDate,
            'age_category'               => 'balita',
            'status'                     => 'completed',
            'completed_at'               => '11:15:00',
            'total_score'                => 94,
            'conclusion'                 => 'Perkembangan sensomotorik, bahasa, dan adaptasi sosial anak dalam batas normal sesuai usia (milestone tercapai).',
            'recommendation'             => 'Tidak memerlukan intervensi terapi klinis. Disarankan stimulasi natural di lingkungan keluarga dan sekolah.',
            'is_continued_to_assessment' => false, // KEPUTUSAN INKLUSI: TIDAK
            'created_at'                 => $obsDate->copy()->subDays(3),
            'updated_at'                 => $obsDate,
        ]);
        // Tidak ada record di tabel assessments, schedules, atau invoices
    }

    /**
     * Persona 3: Sedang di Fase 2 Asesmen (Ortu Login Aktif, Mengisi Kuesioner, Asesor Mengisi Instrumen)
     */
    private function seedPersona3(Admin $admin, Therapist $assessorPaedagog, Therapist $assessorFisio): void
    {
        $family = Family::create([]);

        // Akun ortu AKTIF karena jadwal asesmen sudah dibuat oleh admin
        $user = User::firstOrCreate(
            ['username' => 'ortuZainab'],
            [
                'email'             => 'fauzi.zainab@puspa.test',
                'password'          => Hash::make('Puspa123.'),
                'is_active'         => true,
                'email_verified_at' => Carbon::now()->subDays(5),
            ]
        );
        $user->assignRole('user');

        $guardian = Guardian::firstOrCreate(
            ['family_id' => $family->id, 'guardian_name' => 'Ahmad Fauzi'],
            [
                'user_id'             => $user->id,
                'guardian_type'       => 'ayah',
                'guardian_phone'      => '081234567003',
                'guardian_birth_date' => Carbon::now()->subYears(41)->toDateString(),
                'guardian_occupation' => 'PNS',
            ]
        );

        $child = Child::firstOrCreate(
            ['family_id' => $family->id, 'child_name' => 'Zainab Khairunnisa'],
            [
                'child_birth_place'    => 'Gresik',
                'child_birth_date'     => Carbon::now()->subYears(7)->toDateString(),
                'child_gender'         => 'perempuan',
                'child_address'        => 'Jl. Raya Manyar No. 88, Gresik',
                'child_complaint'      => 'Kesulitan konsentrasi belajar, disleksia awal, dan postur duduk cepat lelah',
                'child_school'         => 'SD Permata Hati Kelas 1',
                'child_service_choice' => 'Paedagog (PLB) & Fisioterapi',
                'child_religion'       => 'islam',
            ]
        );

        // Fase 1: Observasi Lulus (is_continued = true)
        $obsDate = Carbon::now()->subDays(14);
        $observation = Observation::create([
            'child_id'                   => $child->id,
            'admin_id'                   => $admin->id,
            'therapist_id'               => $assessorPaedagog->id,
            'scheduled_date'             => $obsDate,
            'age_category'               => 'anak-anak',
            'status'                     => 'completed',
            'completed_at'               => '14:00:00',
            'total_score'                => 68,
            'conclusion'                 => 'Ditemukan hambatan perceptual kognitif dan tonus otot postural rendah.',
            'recommendation'             => 'Lanjut ke asesmen klinis mendalam paedagogik dan fisioterapi.',
            'is_continued_to_assessment' => true,
            'created_at'                 => $obsDate->copy()->subDays(4),
            'updated_at'                 => $obsDate,
        ]);

        // Fase 2: Asesmen sedang berjalan (status: scheduled / ongoing)
        $asmDate = Carbon::now()->subDays(3);
        $assessment = Assessment::create([
            'observation_id'     => $observation->id,
            'child_id'           => $child->id,
            'status'             => 'scheduled', // Sedang proses asesmen
            'parent_status'      => 'completed', // Ortu sudah selesai mengisi kuesioner pertimbangan
            'scheduled_date'     => $asmDate,
            'report_file'        => null, // Belum upload hasil final asesmen
            'report_uploaded_at' => null,
            'created_at'         => $asmDate,
            'updated_at'         => Carbon::now(),
        ]);

        // Asesmen Umum: Selesai
        AssessmentDetail::create([
            'assessment_id'       => $assessment->id,
            'type'                => 'umum',
            'admin_id'            => $admin->id,
            'therapist_id'        => $assessorPaedagog->id,
            'completed_at'        => $asmDate->copy()->addHours(2),
            'parent_completed_at' => $asmDate->copy()->addHours(1),
        ]);

        // Asesmen Paedagog: Selesai
        AssessmentDetail::create([
            'assessment_id'       => $assessment->id,
            'type'                => 'paedagog',
            'admin_id'            => $admin->id,
            'therapist_id'        => $assessorPaedagog->id,
            'completed_at'        => $asmDate->copy()->addHours(4),
            'parent_completed_at' => $asmDate->copy()->addHours(3),
        ]);

        // Asesmen Fisio: Masih menunggu jadwal / belum selesai (completed_at null)
        AssessmentDetail::create([
            'assessment_id'       => $assessment->id,
            'type'                => 'fisio',
            'admin_id'            => $admin->id,
            'therapist_id'        => $assessorFisio->id,
            'completed_at'        => null, // Belum selesai diisi oleh asesor fisio
            'parent_completed_at' => $asmDate->copy()->addHours(3),
        ]);
        // Belum memiliki jadwal terapi karena asesmen belum tuntas
    }

    /**
     * Persona 4: Terapi Aktif dengan Kasus Reschedule (Sakit/Izin)
     */
    private function seedPersona4(Admin $admin, Therapist $therapistFisio, Therapist $therapistPaedagog): void
    {
        $family = Family::create([]);

        // Akun ortuRayhan yang sering dipakai testing di GuardianSeeder
        $user = User::firstOrCreate(
            ['username' => 'orangtuaRayhan'],
            [
                'email'             => 'rayhan.ortu@puspa.test',
                'password'          => Hash::make('Puspa123.'),
                'is_active'         => true,
                'email_verified_at' => Carbon::now()->subMonths(1),
            ]
        );
        $user->assignRole('user');

        $guardian = Guardian::firstOrCreate(
            ['family_id' => $family->id, 'guardian_name' => 'Devi Ema'],
            [
                'user_id'             => $user->id,
                'guardian_type'       => 'ibu',
                'guardian_phone'      => '081234567004',
                'guardian_birth_date' => Carbon::now()->subYears(35)->toDateString(),
                'guardian_occupation' => 'Dokter Gigi',
            ]
        );

        $child = Child::firstOrCreate(
            ['family_id' => $family->id, 'child_name' => 'Rayhan Dhafa Pratama'],
            [
                'child_birth_place'    => 'Surabaya',
                'child_birth_date'     => Carbon::now()->subYears(6)->toDateString(),
                'child_gender'         => 'laki-laki',
                'child_address'        => 'Darmo Permai Timur VII/21, Surabaya',
                'child_complaint'      => 'Gangguan motorik kasar pasca cidera ringan dan regulasi emosi',
                'child_school'         => 'TK Al-Hikmah',
                'child_service_choice' => 'Fisioterapi & Paedagog',
                'child_religion'       => 'islam',
            ]
        );

        // Fase 1 Observasi
        $obsDate = Carbon::now()->subMonths(1)->startOfMonth()->addDays(2);
        $observation = Observation::create([
            'child_id'                   => $child->id,
            'admin_id'                   => $admin->id,
            'therapist_id'               => $therapistFisio->id,
            'scheduled_date'             => $obsDate,
            'age_category'               => 'anak-anak',
            'status'                     => 'completed',
            'completed_at'               => '09:30:00',
            'total_score'                => 78,
            'conclusion'                 => 'Kelemahan otot tungkai kanan bawah dan memerlukan latihan keseimbangan dinamis.',
            'recommendation'             => 'Jadwalkan program fisioterapi rutin 4 kali per bulan.',
            'is_continued_to_assessment' => true,
            'created_at'                 => $obsDate->copy()->subDays(2),
            'updated_at'                 => $obsDate,
        ]);

        // Fase 2 Asesmen
        $asmDate = $obsDate->copy()->addDays(5);
        $assessment = Assessment::create([
            'observation_id'     => $observation->id,
            'child_id'           => $child->id,
            'status'             => 'completed',
            'parent_status'      => 'completed',
            'scheduled_date'     => $asmDate,
            'report_file'        => 'reports/asesmen_rayhan.pdf',
            'report_uploaded_at' => $asmDate->copy()->addDay(),
            'created_at'         => $asmDate,
            'updated_at'         => $asmDate->copy()->addDay(),
        ]);

        AssessmentDetail::create([
            'assessment_id'       => $assessment->id,
            'type'                => 'fisio',
            'admin_id'            => $admin->id,
            'therapist_id'        => $therapistFisio->id,
            'completed_at'        => $asmDate->copy()->addHours(2),
            'parent_completed_at' => $asmDate->copy()->addHours(1),
        ]);

        // Fase 3: Jadwal Fisioterapi
        $thisMonth = Carbon::now();
        $schedule = Schedule::create([
            'child_id'       => $child->id,
            'therapist_id'   => $therapistFisio->id,
            'therapy_type'   => 'fisio',
            'day_of_week'    => 4, // Kamis
            'start_time'     => '14:30:00',
            'end_time'       => '15:30:00',
            'total_meetings' => 4,
            'period_start'   => $thisMonth->copy()->startOfMonth()->toDateString(),
            'period_end'     => $thisMonth->copy()->endOfMonth()->toDateString(),
            'status'         => 'aktif',
        ]);

        // Sesi 1: HADIR + SOAP
        $ses1Date = $thisMonth->copy()->startOfMonth()->next(Carbon::THURSDAY);
        $ses1 = TherapySession::create([
            'schedule_id'      => $schedule->id,
            'session_number'   => 1,
            'session_date'     => $ses1Date->toDateString(),
            'start_time'       => '14:30:00',
            'end_time'         => '15:30:00',
            'therapist_id'     => $therapistFisio->id,
            'status'           => 'present',
            'notes'            => 'Hadir bersama Ibu, latihan penguatan otot betis berhasil dilakukan.',
            'checked_in_at'    => $ses1Date->copy()->setTime(14, 25, 0),
            'attendance_token' => Str::random(32),
        ]);

        TherapySoap::create([
            'therapy_session_id'  => $ses1->id,
            'performed_at'        => $ses1Date->copy()->setTime(15, 45, 0),
            'therapy_diagnosis'   => 'Mild Postural Gait Instability',
            'interventions'       => 'Balance board exercise, strengthening quadriceps & gastrocnemius',
            'subjective'          => 'Ibu menyatakan anak sudah bisa berlari kecil tanpa tersandung.',
            'objective'           => 'Range of motion sendi ankle normal, kekuatan otot skala 4/5.',
            'assessment'          => 'Stabilitas dinamis membaik 20% dibandingkan minggu lalu.',
            'plan'                => 'Tingkatkan durasi latihan tumpuan 1 kaki (single leg stance).',
            'implementation'      => 'Latihan balance beam 10 menit, rintangan tangga busa 15 menit.',
            'advanced_assessment' => 'Kepatuhan terapi sangat baik.',
            'advanced_plan'       => 'Lanjutkan sesi minggu berikutnya.',
        ]);

        // Sesi 2: SAKIT -> DIBATALKAN & DI-RESCHEDULE
        $ses2Date = $ses1Date->copy()->addWeek();
        $ses2Original = TherapySession::create([
            'schedule_id'      => $schedule->id,
            'session_number'   => 2,
            'session_date'     => $ses2Date->toDateString(),
            'start_time'       => '14:30:00',
            'end_time'         => '15:30:00',
            'therapist_id'     => $therapistFisio->id,
            'status'           => 'cancelled', // Sesi asli dibatalkan karena reschedule
            'notes'            => 'Anak mengalami demam tinggi (38.5 C), orang tua mengajukan reschedule H-1.',
            'attendance_token' => Str::random(32),
        ]);

        // Sesi Pengganti (Replacement)
        $ses2ReplacementDate = $ses2Date->copy()->addDays(3); // Digeser ke hari Minggu
        $ses2Replacement = TherapySession::create([
            'schedule_id'      => $schedule->id,
            'session_number'   => 2, // Tetap sesi nomor 2 pengganti
            'session_date'     => $ses2ReplacementDate->toDateString(),
            'start_time'       => '10:00:00',
            'end_time'         => '11:00:00',
            'therapist_id'     => $therapistFisio->id,
            'status'           => 'present', // Sesi pengganti sukses hadir
            'notes'            => 'Sesi pengganti (reschedule) setelah kondisi anak pulih.',
            'checked_in_at'    => $ses2ReplacementDate->copy()->setTime(9, 50, 0),
            'attendance_token' => Str::random(32),
        ]);

        // Catatan di tabel reschedules
        Reschedule::create([
            'original_session_id' => $ses2Original->id,
            'new_session_id'      => $ses2Replacement->id,
            'requested_by'        => 'guardian',
            'reason'              => 'Anak sakit demam dan flu, surat dokter terlampir.',
        ]);

        TherapySoap::create([
            'therapy_session_id'  => $ses2Replacement->id,
            'performed_at'        => $ses2ReplacementDate->copy()->setTime(11, 15, 0),
            'therapy_diagnosis'   => 'Mild Postural Gait Instability',
            'interventions'       => 'Light stretching & gait re-education',
            'subjective'          => 'Kondisi fisik anak sudah segar dan tidak ada keluhan pusing.',
            'objective'           => 'Tanda vital stabil, mampu menyelesaikan seluruh modul latihan.',
            'assessment'          => 'Reschedule berjalan lancar tanpa penurunan performa klinis.',
            'plan'                => 'Lanjutkan ke sesi reguler berikutnya.',
            'implementation'      => 'Stretching pasif dan latihan jalan di garis lurus.',
            'advanced_assessment' => 'Toleransi beban baik.',
            'advanced_plan'       => 'Pertahankan jadwal normal.',
        ]);

        // Sesi 3 & 4: Pending
        $ses3Date = $ses2Date->copy()->addWeek();
        TherapySession::create([
            'schedule_id'      => $schedule->id,
            'session_number'   => 3,
            'session_date'     => $ses3Date->toDateString(),
            'start_time'       => '14:30:00',
            'end_time'         => '15:30:00',
            'therapist_id'     => $therapistFisio->id,
            'status'           => 'pending',
            'attendance_token' => Str::random(32),
        ]);

        $ses4Date = $ses3Date->copy()->addWeek();
        TherapySession::create([
            'schedule_id'      => $schedule->id,
            'session_number'   => 4,
            'session_date'     => $ses4Date->toDateString(),
            'start_time'       => '14:30:00',
            'end_time'         => '15:30:00',
            'therapist_id'     => $therapistFisio->id,
            'status'           => 'pending',
            'attendance_token' => Str::random(32),
        ]);

        // Invoice Bulan Ini Lunas
        $invRayhan = Invoice::create([
            'no_invoice'      => sprintf('INV-LC/%s/%04d', $thisMonth->format('Y/m'), rand(1000, 9999)),
            'child_id'        => $child->id,
            'guardian_id'     => $guardian->id,
            'type'            => 'Bulanan',
            'period_label'    => $thisMonth->translatedFormat('F Y'),
            'start_date'      => $thisMonth->copy()->startOfMonth()->toDateString(),
            'end_date'        => $thisMonth->copy()->endOfMonth()->toDateString(),
            'subtotal_amount' => 700000,
            'discount_amount' => 0,
            'total_amount'    => 700000,
            'status'          => 'LUNAS',
            'issued_date'     => $thisMonth->copy()->startOfMonth()->toDateString(),
            'notes'           => 'Paket 4 sesi fisioterapi @ Rp 175.000.',
        ]);

        InvoiceItem::create([
            'invoice_id'     => $invRayhan->id,
            'therapy_type'   => 'Fisioterapi',
            'session_count'  => 4,
            'price_per_session' => 175000,
            'subtotal'       => 700000,
        ]);

        Payment::create([
            'invoice_id'       => $invRayhan->id,
            'child_id'         => $child->id,
            'guardian_id'      => $guardian->id,
            'transaction_type' => 'Pemasukan',
            'payment_method'   => 'QRIS',
            'amount'           => 700000,
            'receipt_number'   => 'RCP-RAYHAN-OCT',
            'payment_date'     => $thisMonth->copy()->startOfMonth()->addDay()->toDateString(),
            'notes'            => 'Lunas via QRIS Dinamis.',
        ]);
    }

    /**
     * Persona 5: Kasus Keuangan Kompleks (SessionCredit / Deposit & Pengembalian Dana)
     */
    private function seedPersona5(Admin $admin, Therapist $therapistOkupasi): void
    {
        $family = Family::create([]);

        $user = User::firstOrCreate(
            ['username' => 'ortuKezia'],
            [
                'email'             => 'nugroho.kezia@puspa.test',
                'password'          => Hash::make('Puspa123.'),
                'is_active'         => true,
                'email_verified_at' => Carbon::now()->subMonths(2),
            ]
        );
        $user->assignRole('user');

        $guardian = Guardian::firstOrCreate(
            ['family_id' => $family->id, 'guardian_name' => 'Nugroho Wicaksono'],
            [
                'user_id'             => $user->id,
                'guardian_type'       => 'ayah',
                'guardian_phone'      => '081234567005',
                'guardian_birth_date' => Carbon::now()->subYears(40)->toDateString(),
                'guardian_occupation' => 'Manajer Operasional',
            ]
        );

        $child = Child::firstOrCreate(
            ['family_id' => $family->id, 'child_name' => 'Kezia Aurelia'],
            [
                'child_birth_place'    => 'Malang',
                'child_birth_date'     => Carbon::now()->subYears(5)->toDateString(),
                'child_gender'         => 'perempuan',
                'child_address'        => 'Jl. Ijen No. 45, Malang',
                'child_complaint'      => 'Sensory processing issue dan taktil defensif',
                'child_school'         => 'TK Cor Jesu',
                'child_service_choice' => 'Terapi Okupasi (Sensory Integration)',
                'child_religion'       => 'katolik',
            ]
        );

        // Observasi & Asesmen
        $obsDate = Carbon::now()->subMonths(2);
        $observation = Observation::create([
            'child_id'                   => $child->id,
            'admin_id'                   => $admin->id,
            'therapist_id'               => $therapistOkupasi->id,
            'scheduled_date'             => $obsDate,
            'age_category'               => 'balita',
            'status'                     => 'completed',
            'completed_at'               => '10:00:00',
            'total_score'                => 72,
            'conclusion'                 => 'Hipersensitivitas taktil dan vestibular.',
            'recommendation'             => 'Program Sensory Integration Okupasi Terapi intensif.',
            'is_continued_to_assessment' => true,
        ]);

        $asmDate = $obsDate->copy()->addDays(7);
        $assessment = Assessment::create([
            'observation_id'     => $observation->id,
            'child_id'           => $child->id,
            'status'             => 'completed',
            'parent_status'      => 'completed',
            'scheduled_date'     => $asmDate,
            'report_file'        => 'reports/asesmen_kezia.pdf',
            'report_uploaded_at' => $asmDate->copy()->addDay(),
        ]);

        AssessmentDetail::create([
            'assessment_id'       => $assessment->id,
            'type'                => 'okupasi',
            'admin_id'            => $admin->id,
            'therapist_id'        => $therapistOkupasi->id,
            'completed_at'        => $asmDate->copy()->addHours(2),
            'parent_completed_at' => $asmDate->copy()->addHours(1),
        ]);

        // Invoice Bulan Lalu: Status PERIODE BERIKUTNYA (karena ada 2 sesi batal karena klinik renovasi / terapis berhalangan)
        $lastMonth = Carbon::now()->subMonth();
        $invOld = Invoice::create([
            'no_invoice'      => sprintf('INV-LC/%s/%04d', $lastMonth->format('Y/m'), rand(1000, 9999)),
            'child_id'        => $child->id,
            'guardian_id'     => $guardian->id,
            'type'            => 'Bulanan',
            'period_label'    => $lastMonth->translatedFormat('F Y'),
            'start_date'      => $lastMonth->copy()->startOfMonth()->toDateString(),
            'end_date'        => $lastMonth->copy()->endOfMonth()->toDateString(),
            'subtotal_amount' => 600000, // 4 sesi @ 150.000
            'discount_amount' => 0,
            'total_amount'    => 600000,
            'status'          => 'PERIODE BERIKUTNYA',
            'issued_date'     => $lastMonth->copy()->startOfMonth()->toDateString(),
            'notes'           => 'Sisa 2 sesi tidak terpakai dialihkan sebagai deposit ke bulan berikutnya.',
        ]);

        InvoiceItem::create([
            'invoice_id'        => $invOld->id,
            'therapy_type'      => 'Terapi Okupasi',
            'session_count'     => 4,
            'price_per_session' => 150000,
            'subtotal'          => 600000,
        ]);

        Payment::create([
            'invoice_id'       => $invOld->id,
            'child_id'         => $child->id,
            'guardian_id'      => $guardian->id,
            'transaction_type' => 'Ke periode berikutnya',
            'payment_method'   => '-',
            'amount'           => 300000,
            'receipt_number'   => 'DEP-KEZIA-001',
            'payment_date'     => $lastMonth->copy()->endOfMonth()->toDateString(),
            'notes'            => 'Dialihkan ke periode bulan berikutnya sebesar 2 sesi (Rp 300.000).',
        ]);

        // Invoice Bulan Ini yang menggunakan Session Credit
        $thisMonth = Carbon::now();
        $invNew = Invoice::create([
            'no_invoice'      => sprintf('INV-LC/%s/%04d', $thisMonth->format('Y/m'), rand(1000, 9999)),
            'child_id'        => $child->id,
            'guardian_id'     => $guardian->id,
            'type'            => 'Bulanan',
            'period_label'    => $thisMonth->translatedFormat('F Y'),
            'start_date'      => $thisMonth->copy()->startOfMonth()->toDateString(),
            'end_date'        => $thisMonth->copy()->endOfMonth()->toDateString(),
            'subtotal_amount' => 600000,
            'discount_amount' => 300000, // Dipotong kredit sesi bulan lalu
            'total_amount'    => 300000,
            'status'          => 'LUNAS',
            'issued_date'     => $thisMonth->copy()->startOfMonth()->toDateString(),
            'notes'           => 'Dipoptong deposit 2 sesi dari invoice ' . $invOld->no_invoice,
        ]);

        InvoiceItem::create([
            'invoice_id'     => $invNew->id,
            'therapy_type'   => 'Terapi Okupasi',
            'session_count'  => 4,
            'price_per_session' => 150000,
            'subtotal'       => 600000,
        ]);

        // Record SessionCredit
        SessionCredit::create([
            'child_id'             => $child->id,
            'guardian_id'          => $guardian->id,
            'source_invoice_id'    => $invOld->id,
            'unused_session_count' => 2,
            'credit_amount'        => 300000,
            'is_used'              => true,
            'used_in_invoice_id'   => $invNew->id,
        ]);

        // Sisa tagihan dibayar lunas
        Payment::create([
            'invoice_id'       => $invNew->id,
            'child_id'         => $child->id,
            'guardian_id'      => $guardian->id,
            'transaction_type' => 'Pemasukan',
            'payment_method'   => 'Transfer bank',
            'amount'           => 300000,
            'receipt_number'   => 'RCP-KEZIA-NOV',
            'payment_date'     => $thisMonth->copy()->startOfMonth()->addDays(2)->toDateString(),
            'notes'            => 'Pelunasan sisa tagihan via Mandiri Transfer.',
        ]);
    }

    /**
     * Membersihkan data persona dummy sebelumnya agar seeder idempoten (tepat 5 pasien).
     */
    private function cleanOldLifecycleData(): void
    {
        $usernames = [
            'ortuArjuna',
            'ortuSalsabila',
            'ortuZainab',
            'orangtuaRayhan',
            'ortuKezia',
        ];

        $childNames = [
            'Arjuna Pratama',
            'Salsabila Putri',
            'Zainab Khairunnisa',
            'Rayhan Dhafa Pratama',
            'Kezia Aurelia',
        ];

        // 1. Ambil semua child yang namanya sesuai persona lifecycle
        $children = Child::whereIn('child_name', $childNames)->get();

        foreach ($children as $child) {
            $childId = $child->id;

            // Hapus observasi & jawaban
            ObservationAnswer::whereHas('observation', fn($q) => $q->where('child_id', $childId))->delete();
            Observation::where('child_id', $childId)->delete();

            // Hapus asesmen & detail
            AssessmentDetail::whereHas('assessment', fn($q) => $q->where('child_id', $childId))->delete();
            Assessment::where('child_id', $childId)->delete();

            // Hapus jadwal & sesi terapi beserta SOAP & Reschedule
            $schedules = Schedule::where('child_id', $childId)->get();
            foreach ($schedules as $schedule) {
                TherapySoap::whereHas('therapySession', fn($q) => $q->where('schedule_id', $schedule->id))->delete();
                Reschedule::whereHas('originalSession', fn($q) => $q->where('schedule_id', $schedule->id))->delete();
                Reschedule::whereHas('newSession', fn($q) => $q->where('schedule_id', $schedule->id))->delete();
                TherapySession::where('schedule_id', $schedule->id)->delete();
                $schedule->delete();
            }

            // Hapus sesi lepas jika ada
            TherapySession::where('attendance_token', 'LIKE', '%')->whereHas('schedule', fn($q) => $q->where('child_id', $childId))->delete();

            // Hapus keuangan
            Payment::where('child_id', $childId)->delete();
            SessionCredit::where('child_id', $childId)->delete();
            InvoiceItem::whereHas('invoice', fn($q) => $q->where('child_id', $childId))->delete();
            Invoice::where('child_id', $childId)->delete();

            $familyId = $child->family_id;
            $child->delete();

            if ($familyId) {
                Guardian::where('family_id', $familyId)->delete();
                Family::where('id', $familyId)->delete();
            }
        }

        // Hapus user persona
        User::whereIn('username', $usernames)->delete();
    }
}

