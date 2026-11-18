<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PUSPA Application Storage Paths
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'assessment_reports' => env('ASSESSMENT_REPORTS_PATH', 'assessment/reports'),
        'avatars'            => env('AVATARS_PATH', 'avatars'),
        'invoices'           => env('INVOICES_PATH', 'invoices'),
        'payments'           => env('PAYMENTS_PATH', 'payments'),
    ],
];
