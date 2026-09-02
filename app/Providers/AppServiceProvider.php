<?php

namespace App\Providers;

use App\Support\KonteksPeriode;
use App\Models\PengaturanSekolah;
use App\Models\TahunAjaran;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // URUTAN SKRIP — jangan dihapus.
        //
        // Livewire membawa Alpine-nya sendiri dan memulainya begitu
        // skripnya jalan. Tanpa 'defer', skrip itu (di ujung <body>)
        // berjalan LEBIH DULU daripada app.js yang dimuat Vite sebagai
        // module di <head> — padahal app.js-lah yang mendaftarkan plugin
        // x-collapse dan mendefinisikan window.barisNilai yang dipakai
        // langsung di dalam x-data. Dengan 'defer', keduanya menjadi
        // skrip tertunda dan dijalankan sesuai urutan di dokumen:
        // app.js dulu, Livewire menyusul.
        Livewire::useScriptTagAttributes(['defer' => true]);

        // Periode yang sedang DILIHAT pengguna dipakai di banyak lembar
        // cetak (kop surat, judul rekap), jadi disediakan ke SEMUA view —
        // bukan cuma layouts.app — dengan alasan yang sama seperti
        // pengaturanSekolahGlobal di bawah: isi @section sudah selesai
        // dirender sebelum composer layout sempat jalan.
        //
        // 'tahunAjaranAktifGlobal' sengaja tetap berisi periode yang
        // BENAR-BENAR BERJALAN (bukan pilihan), supaya halaman yang perlu
        // membedakan keduanya bisa melakukannya.
        View::composer('*', function ($view) {
            $view->with('tahunAjaranAktifGlobal', TahunAjaran::aktif());
            $view->with('periodePilihanGlobal', KonteksPeriode::pilihan());
        });

        // Dipakai di '*' (bukan cuma layouts.app) supaya juga otomatis kebaca
        // di dalam @section('content') milik tiap halaman Cetak — kalau cuma
        // di-compose ke layouts.app saja, isi @section sudah selesai dirender
        // duluan sebelum composer layout itu jalan, jadi variabelnya tidak
        // sempat terlihat di sana. PengaturanSekolah::current() sendiri sudah
        // di-cache per-request, jadi ini tidak menambah query berulang.
        View::composer('*', function ($view) {
            $view->with('pengaturanSekolahGlobal', PengaturanSekolah::current());
        });

        // Batasi laju kirim WhatsApp: maksimal 20 pesan/menit. Fonnte
        // sendiri sanggup jauh lebih cepat (~10 pesan/detik), tapi kita
        // tetap batasi supaya aman dari risiko banned WhatsApp kalau ada
        // banyak siswa Alfa sekaligus dikirim ke queue bersamaan (lihat
        // KirimNotifikasiAlfaWhatsapp::middleware()).
        RateLimiter::for('notifikasi-wa', function () {
            return Limit::perMinute(20);
        });

        // Rate limit login (Bagian keamanan): cegah brute-force password.
        // Dibatasi per kombinasi identitas (email/NIS) + IP, supaya 1 IP
        // tidak bisa mencoba banyak akun sekaligus tanpa batas, sekaligus
        // 1 akun tidak bisa dibrute-force dari banyak IP tanpa batas.
        // Percobaan aktivasi dibatasi: nomor seri 20 karakter memang tidak
        // realistis ditebak, tapi membatasi tetap menutup upaya membanjiri.
        // Penahan laju TERPISAH untuk pengingat guru. Sengaja tidak
        // menumpang 'notifikasi-wa' milik notifikasi Alfa: keduanya memakai
        // perangkat Fonnte yang berbeda, jadi kuota kirimnya juga berbeda,
        // dan antrian pengingat guru yang panjang tidak boleh ikut
        // memperlambat pemberitahuan Alfa kepada orang tua.
        RateLimiter::for('pengingat-guru', function () {
            return Limit::perMinute(20);
        });

        RateLimiter::for('aktivasi', function ($request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('login', function ($request) {
            $key = Str::lower($request->input('email')) . '|' . $request->ip();
            return Limit::perMinute(5)->by($key)->response(function () {
                return back()->withErrors([
                    'email' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam beberapa saat.',
                ]);
            });
        });

        RateLimiter::for('login-ortu', function ($request) {
            $key = Str::lower((string) $request->input('nis')) . '|' . $request->ip();
            return Limit::perMinute(5)->by($key)->response(function () {
                return back()->withErrors([
                    'nis' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam beberapa saat.',
                ]);
            });
        });

        // Minta kode reset = mengirim pesan WhatsApp yang berbiaya, jadi
        // dibatasi lebih ketat daripada login. Batas per jam ada supaya
        // pengulangan pelan-pelan pun tidak bisa menguras kuota Fonnte.
        RateLimiter::for('reset-sandi', function ($request) {
            $key = Str::lower((string) $request->input('email')) . '|' . $request->ip();

            return [
                Limit::perMinute(3)->by($key),
                Limit::perHour(10)->by($key),
            ];
        });
    }
}
