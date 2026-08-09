<?php

namespace App\Providers;

use App\Models\TahunAjaran;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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

        // Batasi laju kirim WhatsApp: maksimal 20 pesan/menit. Fonnte
        // sendiri sanggup jauh lebih cepat (~10 pesan/detik), tapi kita
        // tetap batasi supaya aman dari risiko banned WhatsApp kalau ada
        // banyak siswa Alfa sekaligus dikirim ke queue bersamaan (lihat
        // KirimNotifikasiAlfaWhatsapp::middleware()).
        RateLimiter::for('notifikasi-wa', function () {
            return Limit::perMinute(20);
        });
    }
}
