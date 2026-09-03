<?php

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'orangtua' => [
            'driver' => 'session',
            'provider' => 'orangtuas',
        ],
    ],

    /*
     * Driver 'eloquent-tersidik' adalah Eloquent bawaan Laravel dengan SATU
     * perubahan: remember_token disimpan sebagai SIDIK, bukan nilai polos.
     * Tanpa itu, salinan database beserta APP_KEY sudah cukup untuk merakit
     * cookie "Ingat saya" milik akun mana pun dan masuk tanpa kata sandi.
     * Penjelasan lengkap ada di App\Auth\PenyediaPenggunaTokenTersidik;
     * pendaftarannya di App\Providers\AppServiceProvider::boot().
     */
    'providers' => [
        'users' => [
            'driver' => 'eloquent-tersidik',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],

        'orangtuas' => [
            'driver' => 'eloquent-tersidik',
            'model' => App\Models\OrangTua::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
