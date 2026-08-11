<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menambahkan header supaya browser TIDAK menyimpan/menampilkan versi lama
 * (cache) dari halaman-halaman dinamis seperti Jurnal Kelas, dashboard, dan
 * status Absensi/Jurnal Mengajar.
 *
 * Tanpa ini, ada skenario umum yang bisa membuat data terlihat "belum
 * berubah" padahal di database SUDAH ter-update: misalnya guru menyimpan
 * ulang absensi, lalu menekan tombol Back di browser untuk melihat halaman
 * sebelumnya — beberapa browser menampilkan salinan (cache) halaman itu
 * dari SEBELUM perubahan, bukan mengambil data terbaru dari server.
 */
class NoCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
