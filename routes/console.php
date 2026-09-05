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
 * `php artisan schedule:run` tiap menit, DAN satu pekerja antrian:
 *
 *   php artisan queue:work --queue=arsip,notifikasi,pengingat-guru,default
 *
 * Daftar antrian itu WAJIB disebutkan. `queue:work` tanpa argumen hanya
 * melayani antrian `default`, sehingga pengingat WhatsApp dan pembuatan
 * arsip semester akan menunggu selamanya tanpa pesan galat apa pun —
 * gejalanya "kok lama sekali", bukan "gagal".
 *
 * SETIAP KALI KODE DIPERBARUI, PEKERJA HARUS DIMUAT ULANG:
 *
 *   php artisan queue:restart
 *
 * `queue:work` adalah proses yang hidup terus. Ia memuat kode SEKALI saat
 * dijalankan, lalu memakai salinan itu untuk semua pekerjaan berikutnya —
 * pembaruan kode tidak pernah sampai kepadanya. Gejalanya menyesatkan
 * karena tidak ada galat sama sekali: pekerjaannya tetap selesai, hanya
 * dikerjakan oleh versi lama. Arsip semester pernah keluar tanpa KOP surat
 * persis karena ini — pekerjanya masih menjalankan versi sebelum pencetak
 * peramban ada.
 *
 * Lihat panduan database Bagian pemasangan.
 */
Schedule::command('pengingat:jurnal')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
/**
 * Membersihkan berkas arsip semester yang sudah lewat satu tahun.
 *
 * Sebulan sekali sudah cukup — arsip tidak menumpuk secepat itu, dan
 * menjalankannya lebih sering hanya membuang putaran tanpa hasil.
 *
 * Yang dihapus HANYA berkasnya; catatan bahwa arsip itu pernah dibuat
 * tetap tersimpan. Lihat App\Console\Commands\BersihkanArsipLama.
 */
Schedule::command('arsip:bersihkan')
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping()
    ->runInBackground();
