<?php

namespace Database\Seeders;

use App\Models\TherapySession;
use App\Models\TherapySoap;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TherapySoapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            'wicara' => [
                'therapy_diagnosis' => 'Keterlambatan bicara dan artikulasi (Speech Delay / Articulation Disorder)',
                'interventions' => 'Oral motor exercise, stimulasi artikulasi fonem /r/ dan /s/, serta latihan imitasi suku kata.',
                'subjective' => 'Orang tua menyampaikan anak masih kesulitan mengucapkan kata berakhiran konsonan dan perbendaharaan kata bertambah perlahan.',
                'objective' => 'Kontak mata baik selama 15 menit awal. Anak mampu meniru fonem vokal dan 4 fonem konsonan dengan bantuan visual.',
                'assessment' => 'Kemampuan imitasi vokal meningkat, respon auditori cukup konsisten, artikulasi konsonan masih memerlukan penguatan.',
                'plan' => 'Lanjutkan stimulasi fonem bilabial dan konsonan dental pada sesi berikutnya. Orang tua diberikan home program membaca buku bersama.',
                'implementation' => 'Menggunakan kartu flashcard gambar hewan, cermin artikulasi, dan bubble blower untuk penguatan otot bibir.',
                'advanced_assessment' => 'Skrining perkembangan fonologi menunjukkan kemajuan 15% dari baseline asesmen awal.',
                'advanced_plan' => 'Target pertemuan berikutnya: Anak mampu mengucapkan 10 kata bermakna dengan kejelasan minimal 70% secara spontan.',
            ],
            'okupasi' => [
                'therapy_diagnosis' => 'Gangguan integrasi sensori dan koordinasi motorik halus (Fine Motor & Sensory Integration Dysfunction)',
                'interventions' => 'Latihan sensori vestibular, koordinasi bilateral jemari tangan, dan latihan pre-writing grip.',
                'subjective' => 'Ibu mengeluhkan anak masih enggan memegang pensil dengan benar dan mudah terdistraksi suara bising.',
                'objective' => 'Anak menyelesaikan rintangan papan titian 3 repetisi, mampu meronce manik-manik besar selama 10 menit.',
                'assessment' => 'Regulasi sensori vestibular membaik, kekuatan genggaman (pincer grasp) tangan dominan masih perlu dilatih.',
                'plan' => 'Fokus penguatan otot postural dan latihan menggenggam media bertekstur (playdough, pasir kinetik).',
                'implementation' => 'Pemberian rangsang sensori taktil menggunakan playdough dan latihan pincer grasp dengan penjepit jemuran kecil.',
                'advanced_assessment' => 'Tingkat fokus anak meningkat dari 5 menit menjadi 12 menit dalam aktivitas meja tertarget.',
                'advanced_plan' => 'Persiapan transisi memegang krayon segitiga dengan tripod grasp mandiri pada siklus terapi berikutnya.',
            ],
            'fisio' => [
                'therapy_diagnosis' => 'Hipotonia otot postural dan keterlambatan motorik kasar (Gross Motor Delay)',
                'interventions' => 'Core muscle strengthening, latihan keseimbangan statis dan dinamis, fasilitasi transisi duduk ke berdiri.',
                'subjective' => 'Orang tua menyatakan anak sering tersandung saat berlari dan cepat lelah saat berjalan jauh.',
                'objective' => 'Anak mampu mempertahankan posisi berdiri 1 kaki selama 4 detik dengan bantuan, tonus otot trunk agak lemah.',
                'assessment' => 'Kekuatan otot ekstremitas bawah cukup baik, stabilitas panggul dan core masih perlu stimulasi konsisten.',
                'plan' => 'Latihan stabilisasi panggul pada gym ball dan latihan melangkah melewati rintangan rendah.',
                'implementation' => 'Penggunaan peanut ball untuk stimulasi keseimbangan serta tangga busa untuk latihan menaiki undakan.',
                'advanced_assessment' => 'Peningkatan daya tahan (endurance) fisik terlihat, durasi berdiri mandiri meningkat stabil.',
                'advanced_plan' => 'Evaluasi milestone berjalan mandiri di permukaan tidak rata pada akhir paket pertemuan.',
            ],
            'paedagog' => [
                'therapy_diagnosis' => 'Kesulitan konsentrasi dan pemahaman instruksi dasar (Pedagogical Learning Difficulty / ADHD symptoms)',
                'interventions' => 'Latihan rentang atensi visual, pemilahan konsep warna & bentuk, pembiasaan instruksi dua tahap.',
                'subjective' => 'Orang tua mengeluhkan anak susah duduk tenang saat belajar di rumah dan sering berpindah-pindah aktivitas.',
                'objective' => 'Anak mampu menyelesaikan puzzle 6 keping dengan 2 kali pengingat, rentang atensi terarah sekitar 10 menit.',
                'assessment' => 'Kepatuhan terhadap aturan sesi meningkat bila menggunakan token reinforcement positif.',
                'plan' => 'Lanjutkan pembiasaan instruksi 2 tahap dan tugas pengelompokan pola bergambar.',
                'implementation' => 'Metode structured task menggunakan reward chart stiker dan kartu instruksi bergambar.',
                'advanced_assessment' => 'Perilaku impulsif saat memilih tugas mulai berkurang dengan bantuan visual schedule.',
                'advanced_plan' => 'Peningkatan kompleksitas tugas kognitif dan kerja sama sosial dengan media bermain peran bergantian.',
            ],
        ];

        $sessions = TherapySession::with('schedule')->get();

        foreach ($sessions as $session) {
            if (TherapySoap::where('therapy_session_id', $session->id)->exists()) {
                continue;
            }

            $therapyType = $session->schedule?->therapy_type ?? 'paedagog';
            $data = $templates[$therapyType] ?? $templates['paedagog'];

            $sessionDate = $session->session_date ? Carbon::parse($session->session_date)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
            $startTime = $session->schedule?->start_time ?? '09:00:00';
            $performedAt = Carbon::parse($sessionDate . ' ' . $startTime);

            TherapySoap::create([
                'therapy_session_id' => $session->id,
                'performed_at' => $performedAt,
                'therapy_diagnosis' => $data['therapy_diagnosis'],
                'interventions' => $data['interventions'],
                'subjective' => $data['subjective'],
                'objective' => $data['objective'],
                'assessment' => $data['assessment'],
                'plan' => $data['plan'],
                'implementation' => $data['implementation'],
                'advanced_assessment' => $data['advanced_assessment'],
                'advanced_plan' => $data['advanced_plan'],
            ]);
        }
    }
}
