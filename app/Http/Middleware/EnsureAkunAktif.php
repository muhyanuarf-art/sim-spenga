<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * AKUN NONAKTIF DIPUTUS SAAT ITU JUGA, BUKAN SAAT LOGIN BERIKUTNYA.
 *
 * =====================================================================
 * LUBANG YANG DITUTUP
 * =====================================================================
 * `is_active` sudah diperiksa di dua pintu masuk — LoginController dan
 * AplikasiMobileController. Keduanya benar, tetapi keduanya hanya
 * berlaku PADA SAAT seseorang menekan tombol Masuk.
 *
 * Guru yang SEDANG masuk ketika akunnya dinonaktifkan tidak melewati
 * pintu itu lagi. Sesinya tetap hidup sampai kedaluwarsa — bawaannya 8
 * jam menganggur, dan hitungan itu diperbarui setiap kali ia mengklik
 * apa pun. Lebih jauh lagi: kalau ia pernah mencentang "Ingat saya di
 * perangkat ini", cookie remember-nya akan MEMBUAT SESI BARU sendiri
 * tanpa pernah menyentuh halaman login — jadi akun yang sudah
 * dinonaktifkan bisa terus dipakai tanpa batas waktu.
 *
 * Justru itulah bentuk yang paling mungkin terjadi pada kasus guru
 * pensiun: akunnya dinonaktifkan pada hari terakhir, sementara di
 * komputer ruang guru sesinya masih terbuka.
 *
 * Middleware ini memeriksa di SETIAP permintaan, sehingga penonaktifan
 * berlaku pada klik berikutnya — bukan besok pagi.
 *
 * =====================================================================
 * KENAPA DIPASANG DI GRUP 'web', BUKAN DI RUTE TERTENTU
 * =====================================================================
 * Supaya tidak ada satu pun rute yang bisa lupa dipasangi. Kalau belum
 * ada yang masuk, permintaan diteruskan apa adanya — jadi halaman login,
 * aktivasi, dan portal orang tua tidak terpengaruh sama sekali.
 */
class EnsureAkunAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Belum masuk, atau yang masuk adalah orang tua (guard terpisah,
        // tidak punya kolom is_active) — bukan urusan middleware ini.
        if (! $user || ! array_key_exists('is_active', $user->getAttributes())) {
            return $next($request);
        }

        if ($user->is_active) {
            return $next($request);
        }

        $pesan = 'Akun Anda telah dinonaktifkan. Hubungi Admin sekolah.';

        // Sesi DAN cookie "Ingat saya" harus sama-sama diputus. Tanpa
        // logout() yang membersihkan keduanya, cookie remember akan
        // membangun sesi baru pada permintaan berikutnya dan pengguna
        // seolah tidak pernah dikeluarkan.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            abort(403, $pesan);
        }

        return redirect()->route('login')->withErrors(['email' => $pesan]);
    }
}
