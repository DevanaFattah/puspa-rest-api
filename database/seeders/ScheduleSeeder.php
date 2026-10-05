<?php

namespace Database\Seeders;

use App\Models\Child;
use App\Models\Family;
use App\Models\Guardian;
use App\Models\Reschedule;
use App\Models\Schedule;
use App\Models\Therapist;
use App\Models\TherapySession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $sections = ['paedagog', 'okupasi', 'wicara', 'fisio'];
        $therapistMap = [];

        foreach ($sections as $sec) {
            $existing = Therapist::where('therapist_section', $sec)->first();
            if (!$existing) {
                $user = User::create([
                    'name' => 'Terapis ' . ucfirst($sec),
                    'email' => 'terapis_' . $sec . '_' . rand(100, 999) . '@puspa.test',
                    'password' => bcrypt('password'),
                ]);

                $existing = Therapist::create([
                    'user_id' => $user->id,
                    'therapist_name' => 'Terapis ' . ucfirst($sec) . ' Specialist',
                    'therapist_section' => $sec,
                    'therapist_phone' => '0812' . rand(10000000, 99999999),
                ]);
            }
            $therapistMap[$sec] = $existing;
        }

        $childNames = [
            'Rayhan', 'Shofiya', 'Lutfy', 'Zamzam', 'Aisyah', 'Fajar', 'Nabila', 'Ridho', 'Zahra', 'Kevin',
            'Alfin', 'Bintang', 'Citra', 'Dimas', 'Erlangga', 'Farah', 'Gilang', 'Hafiz', 'Intan', 'Joko',
            'Kania', 'Lestari', 'Mahendra', 'Nadia', 'Octavia', 'Putra', 'Qonita', 'Rian', 'Salsa', 'Taufik',
            'Umar', 'Vina', 'Wahyudi', 'Xavier', 'Yusuf', 'Zainab', 'Adit', 'Bagus', 'Cinta', 'Danish',
            'Elina', 'Fikri', 'Gita', 'Hanif', 'Irma', 'Jamal', 'Kiki', 'Laras', 'Mita', 'Naufal',
            'Opik', 'Pandu', 'Qori', 'Rizky', 'Syahputra', 'Tia', 'Uli', 'Vino', 'Wanda', 'Yoga',
            'Zain', 'Arjun', 'Bella', 'Chandra', 'Daffa', 'Eva', 'Fadhil', 'Galih', 'Hana', 'Irfan',
            'Jasmine', 'Kenzie', 'Latifah', 'Mahesa', 'Nisa'
        ];

        $guardianNames = [
            'Devi Ema', 'Fiyafiya', 'Zaini', 'Annisan Nur Qoriah', 'Budi Santoso', 'Siti Rahma', 'Ahmad Fauzi',
            'Nur Hidayah', 'Hasan Basri', 'Tri Wahyuni', 'Agus Setiawan', 'Dewi Fortuna', 'Eko Prasetyo', 'Fanny',
            'Guruh', 'Hariyanto', 'Ika Permata', 'Jainuri', 'Kusuma', 'Luki', 'Marzuki', 'Nurhaliza', 'Oki',
            'Purnomo', 'Qori', 'Rahmat', 'Sri Wahyuni', 'Teguh', 'Utami', 'Vera', 'Wibowo', 'Yanti', 'Zulfikar',
            'Aris', 'Bambang', 'Cici', 'Dedi', 'Endang', 'Faisal', 'Grace', 'Hadi', 'Indah', 'Johan', 'Kartika',
            'Lukman', 'Maya', 'Nugroho', 'Olga', 'Pasha', 'Ria', 'Slamet', 'Tania', 'Usman', 'Violita',
            'Wawan', 'Yulia', 'Zainuddin', 'Adelia', 'Bahrul', 'Cynthia', 'Doni', 'Elvira', 'Firman', 'Ghani',
            'Hestu', 'Ismail', 'Juita', 'Kristian', 'Lia'
        ];

        $therapyTypes = ['paedagog', 'okupasi', 'wicara', 'fisio'];
        $statuses = ['aktif', 'non_aktif', 'selesai'];
        $timeSlots = [
            ['08:00', '09:00'],
            ['09:30', '10:30'],
            ['11:00', '12:00'],
            ['13:00', '14:00'],
            ['14:30', '15:30'],
            ['15:40', '16:40'],
        ];

        for ($i = 0; $i < 75; $i++) {
            $childName = $childNames[$i % count($childNames)];
            $guardianName = $guardianNames[$i % count($guardianNames)];
            $rmNumber = 'RM-' . str_pad((string)($i + 1), 6, '0', STR_PAD_LEFT);

            // Create Family
            $family = Family::create([]);

            // Create Guardian
            Guardian::create([
                'family_id' => $family->id,
                'guardian_type' => ($i % 2 == 0) ? 'ibu' : 'ayah',
                'guardian_name' => $guardianName,
                'guardian_phone' => '0812' . rand(10000000, 99999999),
            ]);

            // Create Child
            $child = Child::create([
                'family_id' => $family->id,
                'medical_record_number' => $rmNumber,
                'child_name' => $childName,
                'child_birth_place' => 'Surabaya',
                'child_birth_date' => Carbon::now()->subYears(rand(3, 10))->toDateString(),
                'child_gender' => ($i % 2 == 0) ? 'laki-laki' : 'perempuan',
                'child_address' => 'Surabaya No. ' . ($i + 1),
                'child_complaint' => 'Perkembangan anak ' . ($i + 1),
                'child_service_choice' => 'Terapi Rutin',
            ]);

            // Pick therapy type & matched therapist
            $therapyType = $therapyTypes[$i % count($therapyTypes)];
            $therapist = $therapistMap[$therapyType];
            $status = $statuses[$i % count($statuses)];
            $dayOfWeek = ($i % 7) + 1; // 1-7
            $slot = $timeSlots[$i % count($timeSlots)];

            $schedule = Schedule::create([
                'child_id' => $child->id,
                'therapist_id' => $therapist->id,
                'therapy_type' => $therapyType,
                'day_of_week' => $dayOfWeek,
                'start_time' => $slot[0],
                'end_time' => $slot[1],
                'total_meetings' => 4,
                'period_start' => Carbon::now()->startOfWeek()->subDays(rand(0, 30))->toDateString(),
                'period_end' => Carbon::now()->startOfWeek()->addDays(rand(30, 90))->toDateString(),
                'status' => $status,
            ]);

            // Generate 4 sessions
            for ($s = 1; $s <= 4; $s++) {
                $sessionStatus = match ($s) {
                    1 => 'present',
                    2 => ($i % 5 === 0) ? 'therapist_excused' : 'present',
                    3 => ($i % 4 === 0) ? 'excused' : 'pending',
                    default => 'pending',
                };

                TherapySession::create([
                    'schedule_id' => $schedule->id,
                    'session_number' => $s,
                    'session_date' => Carbon::now()->startOfWeek()->addWeeks($s - 1)->toDateString(),
                    'status' => $sessionStatus,
                    'notes' => ($sessionStatus === 'present') ? 'Sesi berjalan lancar' : null,
                ]);
            }
        }
    }
}
