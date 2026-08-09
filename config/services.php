<?php

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Gateway WhatsApp untuk notifikasi Alfa ke orang tua. Contoh di sini
    // pakai Fonnte (https://fonnte.com) karena API-nya sederhana & populer
    // di Indonesia. Kalau pakai provider lain (Wablas, Woowa, dsb), cukup
    // sesuaikan URL & format request di app/Jobs/KirimNotifikasiAlfaWhatsapp.php.
    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
        'url' => env('FONNTE_URL', 'https://api.fonnte.com/send'),
    ],
];
