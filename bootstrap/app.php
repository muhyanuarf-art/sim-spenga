<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'periode-aktif' => \App\Http\Middleware\EnsurePeriodeTidakTerkunci::class,
            'ortu-ganti-password' => \App\Http\Middleware\PaksaGantiPasswordOrangTua::class,
        ]);
        // Lisensi diperiksa PALING DULU: selama nomor seri belum
        // dimasukkan, tidak ada satu halaman pun yang boleh terbuka —
        // termasuk login staf & portal orang tua.
        $middleware->prependToGroup('web', [
            \App\Http\Middleware\EnsureLisensiAktif::class,
        ]);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\NoCacheHeaders::class,
            \App\Http\Middleware\QueryDebugBadge::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
