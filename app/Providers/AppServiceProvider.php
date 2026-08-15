<?php

namespace App\Providers;

use App\Models\PengaturanSekolah;
use App\Models\TahunAjaran;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Sediakan Tahun Ajaran aktif ke layout utama, agar bisa ditampilkan
        // di Top Bar Header tanpa perlu di-passing manual dari tiap controller.
        View::composer('layouts.app', function ($view) {
            $view->with('tahunAjaranAktifGlobal', TahunAjaran::aktif());
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
    }
}
