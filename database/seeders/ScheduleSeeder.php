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
use Illuminate\Support\Str;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure at least one Therapist exists
        $therapist = Therapist::first();
        if (!$therapist) {
            $user = User::create([
                'name' => 'Alief Arifun',
                'email' => 'alief@puspa.test',
                'password' => bcrypt('password'),
            ]);
            $therapist = Therapist::create([
                'user_id' => $user->id,
                'therapist_name' => 'Alief Arifun',
                'therapist_section' => 'paedagog',
                'therapist_phone' => '081234567890',
            ]);
        }

        // 2. Ensure at least one Family & Child exists
        $child = Child::first();
        if (!$child) {
            $family = Family::create([]);
            
            Guardian::create([
                'family_id' => $family->id,
                'guardian_type' => 'ibu',
                'guardian_name' => 'Devi Ema',
                'guardian_phone' => '089876543210',
            ]);

            $child = Child::create([
                'family_id' => $family->id,
                'medical_record_number' => 'RM-000123',
                'child_name' => 'Rayhan',
                'child_birth_place' => 'Surabaya',
                'child_birth_date' => '2019-05-15',
                'child_gender' => 'laki-laki',
                'child_address' => 'Jl. Pemuda No. 123',
                'child_complaint' => 'Keterlambatan bicara (speech delay)',
                'child_service_choice' => 'Paedagog, Terapi Wicara',
            ]);
        } else {
            if (empty($child->medical_record_number)) {
                $child->update(['medical_record_number' => 'RM-000123']);
            }
        }

        // 3. Create Sample Schedule
        $schedule = Schedule::create([
            'child_id' => $child->id,
            'therapist_id' => $therapist->id,
            'therapy_type' => 'paedagog',
            'day_of_week' => 1, // Senin
            'start_time' => '15:30:00',
            'end_time' => '16:30:00',
            'total_meetings' => 4,
            'period_start' => Carbon::now()->startOfWeek()->toDateString(),
            'period_end' => Carbon::now()->startOfWeek()->addWeeks(4)->toDateString(),
            'status' => 'aktif',
        ]);

        // 4. Create Therapy Sessions
        // Session 1: Hadir (Present)
        $session1 = TherapySession::create([
            'schedule_id' => $schedule->id,
            'session_number' => 1,
            'session_date' => Carbon::now()->startOfWeek()->toDateString(),
            'status' => 'present',
            'notes' => 'Anak kooperatif mengikuti sesi pertama',
        ]);

        // Session 2: Terapis Izin (Therapist Excused) -> Di-reschedule
        $session2Cancelled = TherapySession::create([
            'schedule_id' => $schedule->id,
            'session_number' => 2,
            'session_date' => Carbon::now()->startOfWeek()->addWeek()->toDateString(),
            'status' => 'therapist_excused',
            'notes' => 'Terapis menghadiri pelatihan luar kota, sesi di-reschedule',
        ]);

        // Session 2 Replacement (Baru)
        $session2Replacement = TherapySession::create([
            'schedule_id' => $schedule->id,
            'session_number' => 2, // Pertemuan ke-2 versi reschedule
            'session_date' => Carbon::now()->startOfWeek()->addWeek()->addDays(3)->toDateString(), // Kamis
            'status' => 'pending',
            'notes' => 'Sesi pengganti untuk pertemuan 2 (Semula Senin dipindah Kamis)',
        ]);

        // Record Reschedule Link
        Reschedule::create([
            'original_session_id' => $session2Cancelled->id,
            'new_session_id' => $session2Replacement->id,
            'requested_by' => 'therapist',
        ]);

        // Session 3: Pending
        TherapySession::create([
            'schedule_id' => $schedule->id,
            'session_number' => 3,
            'session_date' => Carbon::now()->startOfWeek()->addWeeks(2)->toDateString(),
            'status' => 'pending',
        ]);

        // Session 4: Pending
        TherapySession::create([
            'schedule_id' => $schedule->id,
            'session_number' => 4,
            'session_date' => Carbon::now()->startOfWeek()->addWeeks(3)->toDateString(),
            'status' => 'pending',
        ]);
    }
}
