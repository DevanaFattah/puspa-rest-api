<?php

namespace Database\Seeders;

use App\Models\Reschedule;
use App\Models\Therapist;
use App\Models\TherapySession;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RescheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $targetCount = 30;

        // Clean up previously seeded reschedules and their replacement sessions
        $existingReschedules = Reschedule::all();
        if ($existingReschedules->isNotEmpty()) {
            $replacementIds = $existingReschedules->pluck('new_session_id')->filter()->all();
            Reschedule::query()->delete();
            if (!empty($replacementIds)) {
                TherapySession::whereIn('id', $replacementIds)->delete();
            }
        }

        $reasons = [
            'guardian' => [
                'Anak mengalami demam tinggi dan flu sejak semalam.',
                'Orang tua ada urusan kedinasan mendadak ke luar kota.',
                'Jadwal bentrok dengan ujian penilaian tengah semester di sekolah anak.',
                'Keluarga besar sedang ada acara hajatan penting di luar kota.',
                'Kendaraan keluarga mengalami kendala teknis dalam perjalanan ke klinik.',
                'Anak sedang dalam kondisi diare dan disarankan dokter istirahat di rumah.',
                'Ada jadwal kontrol rutin dokter spesialis anak di rumah sakit.',
                'Orang tua mengalami lembur kerja mendadak dan tidak ada keluarga lain yang bisa mengantar.',
                'Akses jalan menuju klinik tergenang banjir pasca hujan lebat.',
                'Anak kelelahan dan tantrum hebat setelah kegiatan pentas seni sekolah.',
                'Anak sedang rawat inap di rumah sakit karena tipus.',
                'Orang tua sedang sakit sehingga tidak memungkinkan mengantar anak terapi.',
            ],
            'therapist' => [
                'Terapis berhalangan hadir karena sedang sakit dan menjalani pemulihan.',
                'Terapis ditugaskan mengikuti pelatihan kompetensi sensori integrasi terakreditasi.',
                'Terapis ada jadwal supervisi klinis dan evaluasi kasus mendesak.',
                'Terapis menghadiri lokakarya penanganan keterlambatan wicara anak.',
                'Terapis sedang cuti medis selama dua hari.',
                'Terapis mengikuti seminar nasional terapi okupasi dan tumbuh kembang.',
                'Terapis mengalami musibah keluarga di luar kota.',
            ],
            'children' => [
                'Anak menolak masuk mobil dan mengalami sensory overload di pagi hari.',
                'Anak sangat lelah setelah jadwal fisioterapi intensif di faskes rujukan.',
                'Kondisi fisik anak lemah dan memerlukan istirahat penuh pasca imunisasi.',
                'Mood anak sangat labil dan tidak memungkinkan untuk mengikuti sesi terapi terarah.',
                'Anak menangis terus-menerus dan menolak berinteraksi sejak bangun tidur.',
            ],
        ];

        $eligibleStatuses = ['absent', 'excused', 'sick', 'therapist_absent', 'therapist_excused'];

        // 1. Find sessions that strictly match:
        // - status OTHER THAN present, pending, and cancelled
        // - do NOT have a SOAP record
        // - have not been rescheduled
        $candidateSessions = TherapySession::with('schedule')
            ->whereDoesntHave('soap')
            ->whereDoesntHave('rescheduleOriginal')
            ->whereDoesntHave('rescheduleReplacement')
            ->whereIn('status', $eligibleStatuses)
            ->orderBy('id', 'asc')
            ->get();

        // If fewer than targetCount exist in the database, supplement with additional sessions
        // by setting their initial status to an eligible non-present/pending status before rescheduling
        if ($candidateSessions->count() < $targetCount) {
            $needed = $targetCount - $candidateSessions->count();
            $moreSessions = TherapySession::with('schedule')
                ->whereDoesntHave('soap')
                ->whereDoesntHave('rescheduleOriginal')
                ->whereDoesntHave('rescheduleReplacement')
                ->whereNotIn('id', $candidateSessions->pluck('id'))
                ->where('status', '!=', 'present')
                ->orderBy('id', 'asc')
                ->take($needed)
                ->get();

            foreach ($moreSessions as $session) {
                $session->update([
                    'status' => $eligibleStatuses[array_rand($eligibleStatuses)],
                ]);
            }

            $candidateSessions = $candidateSessions->merge($moreSessions);
        }

        $sessionsToReschedule = $candidateSessions->take($targetCount);

        if ($sessionsToReschedule->isEmpty()) {
            $this->command?->warn('Tidak ada TherapySession yang dapat di-reschedule.');
            return;
        }

        $therapistsBySection = Therapist::all()->groupBy('therapist_section');
        $allTherapists = Therapist::all();

        $created = 0;

        DB::transaction(function () use ($sessionsToReschedule, $reasons, $therapistsBySection, $allTherapists, &$created, $targetCount) {
            foreach ($sessionsToReschedule as $index => $originalSession) {
                if ($created >= $targetCount) {
                    break;
                }

                $originalStatus = $originalSession->status;

                // Match requested_by to status context
                if (in_array($originalStatus, ['therapist_absent', 'therapist_excused'])) {
                    $requestedBy = 'therapist';
                } elseif ($originalStatus === 'sick') {
                    $requestedBy = (rand(1, 10) <= 8) ? 'guardian' : 'children';
                } else {
                    $rand = rand(1, 10);
                    $requestedBy = ($rand <= 7) ? 'guardian' : (($rand <= 9) ? 'therapist' : 'children');
                }

                $reasonList = $reasons[$requestedBy];
                $reason = $reasonList[array_rand($reasonList)];

                // Calculate replacement session date (2 to 7 days after the original session date)
                $origDate = $originalSession->session_date
                    ? Carbon::parse($originalSession->session_date)
                    : Carbon::now()->addDays($index + 1);

                $newDate = $origDate->copy()->addDays(rand(2, 7));

                // Optional substitute therapist: 30% chance to assign another therapist
                $substituteTherapistId = null;
                if (rand(1, 10) <= 3 && $allTherapists->isNotEmpty()) {
                    $section = $originalSession->schedule?->therapy_type;
                    $pool = ($section && isset($therapistsBySection[$section]))
                        ? $therapistsBySection[$section]
                        : $allTherapists;

                    $originalTherapistId = $originalSession->therapist_id ?? $originalSession->schedule?->therapist_id;
                    $candidate = $pool->first(fn ($t) => $t->id !== $originalTherapistId);
                    if ($candidate) {
                        $substituteTherapistId = $candidate->id;
                    }
                }

                // 1. Update original session status to cancelled
                $originalSession->update([
                    'status' => 'cancelled',
                    'notes'  => "Dibatalkan (status awal: {$originalStatus}) karena di-reschedule ke tanggal " . $newDate->format('Y-m-d'),
                ]);

                // 2. Replacement session status: pending or present
                $newSessionStatus = 'pending';
                if ($newDate->isPast() && rand(1, 10) <= 4) {
                    $newSessionStatus = 'present';
                }

                // 3. Create new replacement therapy session
                $newSession = TherapySession::create([
                    'schedule_id'    => $originalSession->schedule_id,
                    'session_number' => $originalSession->session_number,
                    'session_date'   => $newDate->toDateString(),
                    'therapist_id'   => $substituteTherapistId,
                    'status'         => $newSessionStatus,
                    'notes'          => 'Reschedule dari sesi tanggal ' . $origDate->format('Y-m-d'),
                ]);

                // 4. Create Reschedule record
                Reschedule::create([
                    'original_session_id' => $originalSession->id,
                    'new_session_id'      => $newSession->id,
                    'requested_by'        => $requestedBy,
                    'reason'              => $reason,
                ]);

                $created++;
            }
        });

        $this->command?->info("Berhasil membuat {$created} data reschedule (sesi asal berstatus selain present & pending, dan tanpa SOAP).");
    }
}
