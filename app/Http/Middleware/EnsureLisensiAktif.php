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
    /**
     * Halaman yang tetap boleh dibuka meski lisensi tidak aktif.
     *
     * Dua yang pertama untuk mode 'lokal' (mengetik nomor seri), dua
     * terakhir untuk mode 'server' (halaman terkunci beserta tombol
     * memeriksa ulang). Tanpa pengecualian ini, pengguna terjebak di
     * pengalihan yang berputar tanpa ujung.
     */
    private const DIKECUALIKAN = [
        'aktivasi.form', 'aktivasi.simpan',
        'lisensi.terkunci', 'lisensi.periksa-ulang',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (Lisensi::aktif()) {
            return $next($request);
        }

        $nama = $request->route()?->getName();

        if (in_array($nama, self::DIKECUALIKAN, true) || $request->is('up')) {
            return $next($request);
        }

        $modeServer = config('lisensi.mode') === 'server';

        if ($request->expectsJson()) {
            abort(403, $modeServer
                ? 'Masa aktif aplikasi ini sudah berakhir. Hubungi FF Production.'
                : 'Aplikasi belum diaktifkan. Masukkan nomor seri terlebih dahulu.');
        }

        // Di mode 'server' TIDAK ADA yang bisa diketik siapa pun di
        // sekolah — nomor seri sudah ditinggalkan, dan perpanjangan
        // dikerjakan FF Production dari sisinya. Mengarahkan ke halaman
        // aktivasi di sini hanya menyodorkan isian yang mustahil diisi,
        // lalu membuat guru mengira dirinya yang salah.
        return redirect()->route($modeServer ? 'lisensi.terkunci' : 'aktivasi.form');
    }
}
