<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * PENGINGAT JURNAL & ABSENSI KE GURU.
 *
 * Dijalankan tiap 5 menit, bukan tiap jam, supaya jeda "30 menit setelah
 * jam pelajaran selesai" benar-benar terasa 30 menit — bukan 30 sampai 90
 * menit tergantung kapan penjadwal kebetulan berjalan.
 *
 * Menjalankannya sesering itu aman karena perintahnya sendiri yang
 * menentukan apa yang sudah waktunya dikirim, dan indeks unik di tabel
 * `pengingat_jurnals` yang mencegah satu sesi diingatkan dua kali.
 *
 * `withoutOverlapping()` menjaga agar dua proses tidak berjalan bersamaan
 * bila satu putaran kebetulan lambat.
 *
 * AGAR INI BERJALAN, di server harus ada satu penjadwal yang memanggil
 * `php artisan schedule:run` tiap menit, DAN satu pekerja antrian
 * `php artisan queue:work`. Lihat panduan database Bagian pemasangan.
 */
Schedule::command('pengingat:jurnal')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();