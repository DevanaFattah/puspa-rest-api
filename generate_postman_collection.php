<?php

use App\Models\Child;
use App\Models\Schedule;
use App\Models\Therapist;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$child = Child::first();
$childId = $child ? $child->id : '01M3P024T4DF062NCEJZXFZEH1';

$therapist = Therapist::first();
$therapistId = $therapist ? $therapist->id : '01M3P024T4DF062NCEJZXFZEH2';

$schedule = Schedule::first();
$scheduleId = $schedule ? $schedule->id : '01M3P024T4DF062NCEJZXFZEH3';

$collection = [
    'info' => [
        'name' => 'Puspa Holistic Integrative Care API (v2)',
        'description' => 'Collection API lengkap untuk aplikasi Puspa Holistic Integrative Care, termasuk fitur baru Penjadwalan Terapi & Presensi.',
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
        makeItem('Login Admin', 'POST', '/auth/login', [], [
            'email' => 'admin@puspa.test',
            'password' => 'password',
        ], false),
        makeItem('Register Employee (Admin/Terapist)', 'POST', '/auth/register', [], [
            'name' => 'Dr. Ahmad',
            'email' => 'ahmad@puspa.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'terapis',
        ], false),
        makeItem('Refresh Token', 'POST', '/auth/refresh', [], null, false),
        makeItem('Logout', 'POST', '/auth/logout'),
    ],
];

// 2. Modul Jadwal Terapi (Baru Ditambahkan)
$collection['item'][] = [
    'name' => '2. Modul Jadwal Terapi (Schedules)',
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

// Save collection file
$filePath = __DIR__ . '/Puspa_Holistic_v2_Postman_Collection.json';
file_put_contents($filePath, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "Postman collection regenerated successfully at: " . $filePath . "\n";
