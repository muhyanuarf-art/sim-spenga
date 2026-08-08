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

        // Batasi laju kirim WhatsApp: maksimal 20 pesan/menit, supaya tidak
        // melanggar rate limit WhatsApp Cloud API saat banyak siswa Alfa
        // sekaligus dikirim ke queue (lihat KirimNotifikasiAlfaJob::middleware()).
        RateLimiter::for('whatsapp-kirim', function () {
            return Limit::perMinute(20);
        });
    }
}
