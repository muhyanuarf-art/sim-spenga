<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * MEMAKSA ORANG TUA MENGGANTI KATA SANDI BAWAAN.
 *
 * =====================================================================
 * CELAH YANG DITUTUP
 * =====================================================================
 * Akun portal orang tua dibuat massal dengan kata sandi bawaan yang sama
 * untuk semua orang (OrangTua::PASSWORD_DEFAULT), sedangkan nama
 * penggunanya adalah NIS anak — nomor yang tercetak di rapor, diketahui
 * teman sekelas, dan mudah ditebak karena berurutan.
 *
 * Artinya: selama kata sandinya belum diganti, SIAPA PUN yang tahu NIS
 * seorang siswa bisa masuk ke portal orang tuanya dan membaca nilai,
 * absensi, serta catatan BK anak itu. Membatasi percobaan login (throttle)
 * tidak menolong sama sekali di sini — penebaknya tidak perlu menebak.
 *
 * =====================================================================
 * CARA MENUTUPNYA
 * =====================================================================
 * Selama `password_diubah_at` masih kosong, seluruh halaman portal
 * dialihkan ke form ganti kata sandi. Yang tetap boleh dibuka hanya form
 * itu sendiri, aksi menyimpannya, dan logout — supaya orang tua tidak
 * terkunci di halaman buntu.
 *
 * Akun yang kata sandinya di-reset admin ikut terkena lagi, karena
 * OrangTuaController::resetPassword mengosongkan `password_diubah_at`.
 */
class PaksaGantiPasswordOrangTua
{
    /** Rute yang tetap boleh diakses selama kata sandi masih bawaan. */
    private const DIKECUALIKAN = [
        'orangtua.ganti-password.form',
        'orangtua.ganti-password',
        'orangtua.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $orangTua = Auth::guard('orangtua')->user();

        if (! $orangTua || $orangTua->password_diubah_at !== null) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::DIKECUALIKAN, true)) {
            return $next($request);
        }

        return redirect()->route('orangtua.ganti-password.form')->with(
            'error',
            'Demi keamanan data anak Anda, kata sandi bawaan harus diganti dulu sebelum portal bisa dibuka. '
            .'Nama pengguna portal ini adalah NIS anak Anda — nomor yang cukup banyak diketahui orang, '
            .'jadi kata sandinya tidak boleh dibiarkan bawaan.'
        );
    }
}
