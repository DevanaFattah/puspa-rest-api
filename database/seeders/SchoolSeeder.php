<?php

namespace Database\Seeders;

use App\Models\Child;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $schoolsData = [
            [
                'username'       => 'sekolahHarapan',
                'email'          => 'harapan@sekolah.com',
                'password'       => Hash::make('Puspa123.'),
                'school_name'    => 'SD Harapan Bangsa',
                'npsn'           => '20101001',
                'school_phone'   => '081234567890',
                'school_address' => 'Jl. Merdeka No. 45, Surabaya',
                'pic_name'       => 'Budi Prasetyo, S.Pd.',
            ],
            [
                'username'       => 'sekolahBintang',
                'email'          => 'bintang@sekolah.com',
                'password'       => Hash::make('Puspa123.'),
                'school_name'    => 'TK Bintang Ceria',
                'npsn'           => '20101002',
                'school_phone'   => '081987654321',
                'school_address' => 'Jl. Pahlawan No. 12, Surabaya',
                'pic_name'       => 'Siti Rahmawati, S.Psi.',
            ],
        ];

        foreach ($schoolsData as $index => $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'username'          => $data['username'],
                    'password'          => $data['password'],
                    'is_active'         => true,
                    'email_verified_at' => now(),
                ]
            );

            if (!$user->hasRole('sekolah')) {
                $user->assignRole('sekolah');
            }

            $school = School::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'school_name'    => $data['school_name'],
                    'npsn'           => $data['npsn'],
                    'school_phone'   => $data['school_phone'],
                    'school_address' => $data['school_address'],
                    'pic_name'       => $data['pic_name'],
                ]
            );

            // Link sample children to this school (chunk of children)
            $children = Child::whereNull('school_id')
                ->take(4)
                ->get();

            foreach ($children as $child) {
                $child->update([
                    'school_id'    => $school->id,
                    'child_school' => $school->school_name,
                ]);
            }
        }
    }
}

