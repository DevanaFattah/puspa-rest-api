<?php

namespace Database\Seeders;

use App\Models\Guardian;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GuardianSeeder extends Seeder
{
    /**
     * Seed user accounts for existing guardian records.
     *
     * Links each Guardian to a User account (role: 'user') so they can log in to the frontend.
     * Skips guardians that already have a user_id set.
     */
    public function run(): void
    {
        // A few fixed accounts with known credentials for easy testing
        $fixedAccounts = [
            [
                'username'      => 'orangtuaRayhan',
                'email'         => 'rayhan.ortu@puspa.test',
                'password'      => Hash::make('Puspa123.'),
                'guardian_name' => 'Devi Ema', // matches first guardian from ScheduleSeeder
            ],
            [
                'username'      => 'orangtuaShofiya',
                'email'         => 'shofiya.ortu@puspa.test',
                'password'      => Hash::make('Puspa123.'),
                'guardian_name' => 'Fiyafiya',
            ],
            [
                'username'      => 'orangtuaLutfy',
                'email'         => 'lutfy.ortu@puspa.test',
                'password'      => Hash::make('Puspa123.'),
                'guardian_name' => 'Zaini',
            ],
        ];

        // First, link the fixed test accounts to their matching guardians (by guardian_name)
        foreach ($fixedAccounts as $account) {
            $guardian = Guardian::whereNull('user_id')
                ->where('guardian_name', $account['guardian_name'])
                ->first();

            if (!$guardian) {
                continue;
            }

            // Create the User account
            $user = User::firstOrCreate(
                ['username' => $account['username']],
                [
                    'email'             => $account['email'],
                    'password'          => $account['password'],
                    'is_active'         => true,
                    'email_verified_at' => Carbon::now(),
                ]
            );

            $user->assignRole('user');

            // Link the Guardian to the User
            $guardian->update(['user_id' => $user->id]);
        }

        // Then, bulk-link all remaining guardians that don't have a user account yet
        $unlinkedGuardians = Guardian::whereNull('user_id')->get();
        $created = 0;

        foreach ($unlinkedGuardians as $guardian) {
            // Build a slug from the guardian name for username/email uniqueness
            $slug = strtolower(preg_replace('/\s+/', '_', $guardian->guardian_name));
            $slug = preg_replace('/[^a-z0-9_]/', '', $slug);
            $slug = $slug ?: 'ortu';
            $username = 'ortu_' . $slug . '_' . substr($guardian->id, -4);
            $email    = $slug . '_' . substr($guardian->id, -4) . '@puspa.test';

            $user = User::firstOrCreate(
                ['username' => $username],
                [
                    'email'             => $email,
                    'password'          => Hash::make('Puspa123.'),
                    'is_active'         => true,
                    'email_verified_at' => Carbon::now(),
                ]
            );

            $user->assignRole('user');

            $guardian->update(['user_id' => $user->id]);
            $created++;
        }

        $totalFixed = count(array_filter(
            $fixedAccounts,
            fn($a) => Guardian::whereHas('user', fn($q) => $q->where('username', $a['username']))->exists()
        ));

        $this->command?->info("Berhasil menautkan {$created} guardian ke akun user baru.");
        $this->command?->info("Akun test yang bisa digunakan untuk login ke frontend:");
        $this->command?->table(
            ['Username', 'Password'],
            array_map(fn($a) => [$a['username'], 'Puspa123.'], $fixedAccounts)
        );
        $this->command?->info("Selain itu, semua guardian sudah bisa login dengan format:");
        $this->command?->line("  Username: ortu_<nama>_<4 karakter ID>");
        $this->command?->line("  Password: Puspa123.");
    }
}
