<?php

namespace App\Providers;

use App\Models\TahunAjaran;
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
    }
}
