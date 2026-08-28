<?php

namespace App\Http\Middleware;

use App\Support\KonteksPeriode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePeriodeTidakTerkunci
{
    /**
     * Blokir aksi TULIS (POST/PUT/PATCH/DELETE) kalau data tidak boleh
     * disimpan sekarang. Ada DUA sebab, dan keduanya ditangani di sini:
     *
     * 1. Periode yang sedang berjalan sudah DITUTUP & DIKUNCI admin.
     *
     * 2. Pengguna sedang MELIHAT PERIODE LAMPAU lewat pemilih periode di
     *    kepala halaman (lihat App\Support\KonteksPeriode). Tanpa
     *    penjagaan ini, tombol simpan yang masih terlihat di halaman lama
     *    akan menyimpan datanya ke PERIODE AKTIF — karena semua pencatatan
     *    memakai TahunAjaran::aktif() — sehingga datanya seolah lenyap
     *    dari periode yang sedang dibuka pengguna. Justru itu kebingungan
     *    yang paling mahal, jadi ditutup di satu pintu.
     *
     * Rute GET/lihat/cetak tidak pernah dipasangi middleware ini, sehingga
     * seluruh halaman laporan & cetak periode lampau tetap bebas dibuka.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Membaca selalu boleh — termasuk membuka & mencetak periode lampau.
        // Dijaga di sini (bukan cuma dengan memilih rute mana yang dipasangi
        // middleware) supaya middleware ini aman dipasang ke SATU grup rute
        // sekaligus, GET dan POST bercampur, tanpa perlu memilah satu per satu.
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        if (KonteksPeriode::bolehTulis()) {
            return $next($request);
        }

        $pesan = KonteksPeriode::alasanBacaSaja()
            ?? 'Data tidak dapat diubah saat ini.';

        // 423 Locked untuk request yang memang mengharapkan respons API/
        // JSON; selebihnya kembali ke halaman asal dengan pesan ramah.
        if ($request->expectsJson()) {
            abort(423, $pesan);
        }

        return back()->with('error', $pesan);
    }
}
