<?php

namespace App\Http\Middleware;

use App\Support\Lisensi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menutup SELURUH aplikasi selama nomor seri belum dimasukkan.
 *
 * Dipasang di grup 'web', jadi berlaku untuk semua peran sekaligus portal
 * orang tua — tidak ada satu halaman pun yang bisa dibuka tanpa aktivasi.
 * Yang dikecualikan hanya halaman aktivasi itu sendiri (kalau tidak,
 * pengguna terkunci di halaman buntu) dan pemeriksaan kesehatan /up.
 *
 * Lihat App\Support\Lisensi untuk cara kerja & batas kemampuannya.
 */
class EnsureLisensiAktif
{
    private const DIKECUALIKAN = ['aktivasi.form', 'aktivasi.simpan'];

    public function handle(Request $request, Closure $next): Response
    {
        if (Lisensi::aktif()) {
            return $next($request);
        }

        $nama = $request->route()?->getName();

        if (in_array($nama, self::DIKECUALIKAN, true) || $request->is('up')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Aplikasi belum diaktifkan. Masukkan nomor seri terlebih dahulu.');
        }

        return redirect()->route('aktivasi.form');
    }
}
