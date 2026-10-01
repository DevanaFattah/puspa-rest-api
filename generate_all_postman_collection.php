<?php

use App\Models\Child;
use App\Models\Schedule;
use App\Models\Therapist;
use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$child = Child::first();
$childId = $child ? $child->id : '01M3P024T4DF062NCEJZXFZEH1';

$therapist = Therapist::first();
$therapistId = $therapist ? $therapist->id : '01M3P024T4DF062NCEJZXFZEH2';

$user = User::first();
$userId = $user ? $user->id : '01M3P024T4DF062NCEJZXFZEH4';

$schedule = Schedule::first();
$scheduleId = $schedule ? $schedule->id : '01M3P024T4DF062NCEJZXFZEH3';

$collection = [
    'info' => [
        'name' => 'Puspa Holistic Integrative Care API - Complete Collection',
        'description' => 'Koleksi LENGKAP seluruh API endpoints (Auth, Notifications, Schedules, Owner, Admin, Assessor, Therapist, Parent).',
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        [
            'key' => 'baseUrl',
            'value' => 'http://127.0.0.1:8000/api/v1',
            'type' => 'string',
        ],
        [
            'key' => 'baseurl',
            'value' => 'http://127.0.0.1:8000/api/v1',
            'type' => 'string',
        ],
        [
            'key' => 'token',
            'value' => 'YOUR_BEARER_TOKEN_HERE',
            'type' => 'string',
        ],
    ],
    'auth' => [
        'type' => 'bearer',
        'bearer' => [
            [
                'key' => 'token',
                'value' => '{{token}}',
                'type' => 'string',
            ],
        ],
    ],
    'item' => [],
];

function makeItem($name, $method, $urlPath, $headers = [], $body = null, $authRequired = true) {
    $item = [
        'name' => $name,
        'request' => [
            'method' => strtoupper($method),
            'header' => array_merge([
                ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
            ], $headers),
            'url' => [
                'raw' => 'http://127.0.0.1:8000/api/v1' . $urlPath,
                'protocol' => 'http',
                'host' => ['127', '0', '0', '1'],
                'port' => '8000',
                'path' => array_merge(['api', 'v1'], array_values(array_filter(explode('/', ltrim($urlPath, '/'))))),
            ],
        ],
    ];

    if (!$authRequired) {
        $item['request']['auth'] = ['type' => 'noauth'];
    }

    if ($body !== null) {
        $item['request']['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($body, JSON_PRETTY_PRINT),
            'options' => [
                'raw' => [
                    'language' => 'json',
                ],
            ],
        ];
    }

    return $item;
}

// 1. Auth & Public
$collection['item'][] = [
    'name' => '1. Auth & Public',
    'item' => [
        makeItem('Public Registration (Orang Tua / Wali)', 'POST', '/registration', [], [
            'guardian_name' => 'Ibu Siti',
            'guardian_phone' => '081234567890',
            'guardian_type' => 'ibu',
            'temp_email' => 'siti@example.com',
            'child_name' => 'Budi Santoso',
            'child_birth_place' => 'Surabaya',
            'child_birth_date' => '2019-05-15',
            'child_gender' => 'laki-laki',
            'child_address' => 'Jl. Mawar No. 10, Surabaya',
            'child_complaint' => 'Keterlambatan bicara (speech delay)',
            'child_service_choice' => 'Paedagog, Terapi Wicara',
        ], false),
        makeItem('Login User / Admin / Owner / Therapist', 'POST', '/auth/login', [], [
            'identifier' => 'adminAnnisa',
            'password' => 'Annisa123.',
        ], false),
        makeItem('Register Employee (Admin/Terapist)', 'POST', '/auth/register', [], [
            'name' => 'Dr. Ahmad',
            'email' => 'ahmad@puspa.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'terapis',
        ], false),
        makeItem('Refresh Token', 'POST', '/auth/refresh', [], null, false),
        makeItem('Forgot Password', 'POST', '/auth/forgot-password', [], ['email' => 'user@example.com'], false),
        makeItem('Reset Password', 'POST', '/auth/reset-password', [], [
            'token' => 'RESET_TOKEN_HERE',
            'email' => 'user@example.com',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ], false),
        makeItem('Logout', 'POST', '/auth/logout'),
        makeItem('Protected Check', 'GET', '/auth/protected'),
        makeItem('Update Password', 'PUT', '/profile/update-password', [], [
            'current_password' => 'password',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]),
    ],
];

// 2. Notifications
$collection['item'][] = [
    'name' => '2. Notifications',
    'item' => [
        makeItem('Get All Notifications', 'GET', '/notifications'),
        makeItem('Mark All as Read', 'GET', '/notifications/read-all'),
        makeItem('Mark Single Read', 'GET', '/notifications/1/read'),
        makeItem('Delete Single Notification', 'DELETE', '/notifications/1'),
        makeItem('Delete All Notifications', 'DELETE', '/notifications'),
    ],
];

// 3. Modul Jadwal Terapi (Schedules)
$collection['item'][] = [
    'name' => '3. Modul Jadwal Terapi (Schedules)',
    'item' => [
        makeItem('1. GET Ambil Semua Jadwal (With Filters & Pagination)', 'GET', '/schedules?page=1&per_page=10&day_of_week=1&status=aktif&therapy_type=paedagog&search=Rayhan'),
        makeItem('2. POST Tambah Master Jadwal Baru', 'POST', '/schedules', [], [
            'child_id' => $childId,
            'therapist_id' => $therapistId,
            'therapy_type' => 'paedagog',
            'day_of_week' => 1,
            'start_time' => '15:30',
            'end_time' => '16:30',
            'total_meetings' => 4,
            'period_start' => date('Y-m-d'),
            'period_end' => date('Y-m-d', strtotime('+30 days')),
            'status' => 'aktif',
        ]),
        makeItem('3. GET Detail Master Jadwal (+ List Sesi Pertemuan)', 'GET', '/schedules/' . $scheduleId),
        makeItem('4. PUT Edit Master Jadwal', 'PUT', '/schedules/' . $scheduleId, [], [
            'day_of_week' => 2,
            'start_time' => '16:00',
            'end_time' => '17:00',
            'status' => 'aktif',
        ]),
        makeItem('5. DELETE Hapus Master Jadwal', 'DELETE', '/schedules/' . $scheduleId),
    ],
];

// 4. Owner Management
$collection['item'][] = [
    'name' => '4. Owner',
    'item' => [
        makeItem('Owner Dashboard Stats', 'GET', '/owners/dashboard'),
        makeItem('Get Unverified Users (Admin/Therapist)', 'GET', '/users/therapist/unverified'),
        makeItem('Activate Account', 'GET', '/users/' . $userId . '/activate'),
        makeItem('Deactivate Account', 'GET', '/users/' . $userId . '/deactive'),
        makeItem('Promote to Assessor', 'GET', '/users/' . $userId . '/promote-to-assessor'),
    ],
];

// 5. Admin & Owner Shared Management
$collection['item'][] = [
    'name' => '5. Owner & Admin Shared',
    'item' => [
        makeItem('Get Admins List', 'GET', '/admins'),
        makeItem('Get Admin Detail', 'GET', '/admins/' . $userId),
        makeItem('Get Therapists List (Filter by section: paedagog, okupasi, wicara, fisio)', 'GET', '/therapists?section=paedagog'),
        makeItem('Get Therapist Detail', 'GET', '/therapists/' . $therapistId),
        makeItem('Get Children List', 'GET', '/children'),
        makeItem('Get Child Detail', 'GET', '/children/' . $childId),
    ],
];

// 6. Admin Management
$collection['item'][] = [
    'name' => '6. Admin Management',
    'item' => [
        makeItem('Admin Dashboard Stats', 'GET', '/admins/dashboard/stats'),
        makeItem('Today Therapy Schedule', 'GET', '/admins/dashboard/today-schedule'),
        makeItem('Get Admin Profile', 'GET', '/admins/profile'),
        makeItem('Update Admin Profile', 'POST', '/admins/' . $userId . '/profile', [], [
            'name' => 'Admin Utama',
            'phone' => '08123456789',
        ]),
        makeItem('Create Admin', 'POST', '/admins', [], [
            'name' => 'Admin Baru',
            'email' => 'adminbaru@puspa.test',
            'password' => 'password',
        ]),
        makeItem('Update Admin', 'PUT', '/admins/' . $userId, [], [
            'name' => 'Admin Updated',
        ]),
        makeItem('Delete Admin', 'DELETE', '/admins/' . $userId),
        makeItem('Create Therapist', 'POST', '/therapists', [], [
            'user_id' => $userId,
            'therapist_name' => 'Alief Arifun',
            'therapist_section' => 'paedagog',
            'therapist_phone' => '081234567890',
        ]),
        makeItem('Update Therapist', 'PUT', '/therapists/' . $therapistId, [], [
            'therapist_name' => 'Alief Arifun S.Pd',
        ]),
        makeItem('Delete Therapist', 'DELETE', '/therapists/' . $therapistId),
        makeItem('Create Child', 'POST', '/children', [], [
            'family_id' => '01M3P024T4DF062NCEJZXFZEH0',
            'medical_record_number' => 'RM-000125',
            'child_name' => 'Ananda',
            'child_birth_place' => 'Surabaya',
            'child_birth_date' => '2020-01-01',
            'child_gender' => 'perempuan',
            'child_address' => 'Surabaya',
            'child_complaint' => 'Fisioterapi',
            'child_service_choice' => 'Fisioterapi',
        ]),
        makeItem('Update Child', 'PUT', '/children/' . $childId, [], [
            'child_name' => 'Rayhan Pratama',
        ]),
        makeItem('Delete Child', 'DELETE', '/children/' . $childId),
        makeItem('Update Observation Date', 'PUT', '/observations/1', [], [
            'scheduled_date' => '2026-10-10',
            'scheduled_time' => '10:00',
        ]),
        makeItem('Observation Agreement', 'PUT', '/observations/1/agreement', [], [
            'status' => 'agreed',
        ]),
        makeItem('Admin Assessments List', 'GET', '/assessments/scheduled/admin'),
    ],
];

// 7. Observations (Admin, Assessor, Therapist)
$collection['item'][] = [
    'name' => '7. Observations',
    'item' => [
        makeItem('Get Observations by Status (pending/scheduled/completed)', 'GET', '/observations/pending?search=Rayhan'),
        makeItem('Get Observation Detail', 'GET', '/observations/1/detail?type=scheduled'),
        makeItem('Submit Observation Result', 'POST', '/observations/1/submit', [], [
            'notes' => 'Hasil observasi cukup baik',
            'answers' => [],
        ]),
    ],
];

// 8. Assessor & Therapist Management
$collection['item'][] = [
    'name' => '8. Assessor & Therapist',
    'item' => [
        makeItem('Assessor/Therapist Dashboard Stats', 'GET', '/asse-thera/dashboard'),
        makeItem('Assessor/Therapist Profile', 'GET', '/asse-thera/profile'),
        makeItem('Update Profile', 'POST', '/asse-thera/' . $therapistId . '/profile', [], [
            'therapist_name' => 'Alief Arifun',
        ]),
        makeItem('Upcoming Schedules', 'GET', '/asse-thera/upcoming-schedules'),
    ],
];

// 9. Assessment Management (Assessor & Admin)
$collection['item'][] = [
    'name' => '9. Assessment Management',
    'item' => [
        makeItem('Update Assessment Date', 'PATCH', '/assessments/1', [], [
            'scheduled_date' => '2026-10-15',
        ]),
        makeItem('Get Assessment Detail', 'GET', '/assessments/1/detail'),
        makeItem('Upload Assessment Report File', 'POST', '/assessments/1/report-upload'),
        makeItem('Get Answers by Type', 'GET', '/assessments/1/answer/paedagog_assessor'),
        makeItem('Get Assessor Assessments List (scheduled/completed)', 'GET', '/assessments/scheduled'),
        makeItem('Get Questions by Type', 'GET', '/assessments/paedagog/question'),
        makeItem('Get Parent Assessments List', 'GET', '/assessments/completed/parent'),
        makeItem('Submit Assessor Assessment', 'POST', '/assessments/1/submit/paedagog_assessor', [], [
            'answers' => [],
        ]),
    ],
];

// 10. Parent / Orang Tua (My Profile & Dashboard)
$collection['item'][] = [
    'name' => '10. Parent (Orang Tua)',
    'item' => [
        makeItem('Parent Dashboard Stats', 'GET', '/my/dashboard/stats'),
        makeItem('Parent Dashboard Chart Data', 'GET', '/my/dashboard/chart'),
        makeItem('Parent Upcoming Schedules', 'GET', '/my/dashboard/upcoming-schedules'),
        makeItem('Parent Profile', 'GET', '/my/profile'),
        makeItem('Update Parent Profile', 'POST', '/my/profile/' . $userId, [], [
            'guardian_name' => 'Ibu Siti',
        ]),
        makeItem('Get My Children List', 'GET', '/my/children'),
        makeItem('Get My Child Detail', 'GET', '/my/children/' . $childId),
        makeItem('Add My Child', 'POST', '/my/children', [], [
            'child_name' => 'Bambang',
            'child_birth_place' => 'Surabaya',
            'child_birth_date' => '2021-01-01',
            'child_gender' => 'laki-laki',
            'child_address' => 'Surabaya',
            'child_complaint' => 'Keluhan',
            'child_service_choice' => 'Wicara',
        ]),
        makeItem('Update My Child', 'PUT', '/my/children/' . $childId, [], [
            'child_name' => 'Bambang Updated',
        ]),
        makeItem('Delete My Child', 'DELETE', '/my/children/' . $childId),
        makeItem('Update Family Identity Data', 'PUT', '/my/identity', [], [
            'father_name' => 'Ayah Budi',
            'mother_name' => 'Ibu Siti',
        ]),
        makeItem('Get Children Assessments List', 'GET', '/my/assessments'),
        makeItem('Get Parent Question by Type', 'GET', '/my/assessments/parent_general/question'),
        makeItem('Submit Parent Assessment', 'POST', '/my/assessments/1/submit/umum_parent', [], [
            'answers' => [],
        ]),
        makeItem('Get Parent Assessment Answers', 'GET', '/my/assessments/1/answer/umum_parent'),
        makeItem('Download Assessment Report File', 'GET', '/my/assessments/1/report-download'),
        makeItem('Get Assessment Detail', 'GET', '/my/assessments/1'),
    ],
];

// Save collection file
$filePath = __DIR__ . '/Puspa_Holistic_ALL_Postman_Collection.json';
file_put_contents($filePath, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "ALL Postman collection generated successfully at: " . $filePath . "\n";
