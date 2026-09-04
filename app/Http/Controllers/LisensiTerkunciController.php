<?php

namespace App\Http\Controllers;

use App\Support\Lisensi;
use App\Support\LisensiServer;
use Illuminate\Http\Request;

/**
 * HALAMAN SAAT MASA AKTIF BERAKHIR — khusus mode 'server'.
 *
 * =====================================================================
 * KENAPA HALAMAN TERSENDIRI
 * =====================================================================
 * Di mode 'lokal', aplikasi yang belum aktif mengarahkan ke halaman
 * Aktivasi supaya sekolah mengetik nomor serinya. Di mode 'server' itu
 * keliru: nomor seri sudah ditinggalkan, dan perpanjangan sepenuhnya
 * dikerjakan FF Production dari sisinya.
 *
 * Menyodorkan isian yang mustahil diisi bukan sekadar tidak berguna —
 * ia membuat guru mengira dirinya yang salah, lalu mencoba mengetik
 * apa saja. Halaman ini karena itu TIDAK punya satu pun isian. Yang ada
 * hanya keterangan keadaannya dan satu tombol.
 *
 * =====================================================================
 * KENAPA ADA TOMBOL "PERIKSA ULANG"
 * =====================================================================
 * Sesudah FF Production memperpanjang, aplikasi membuka sendiri pada
 * sapaan berkala berikutnya — paling lama beberapa jam. Tombol ini
 * memaksa sapaan itu terjadi sekarang, sehingga sekolah yang sudah
 * membayar tidak perlu menunggu tanpa kepastian sambil menatap layar
 * yang sama.
 */
class LisensiTerkunciController extends Controller
{
    public function tampil()
    {
        // Di mode 'lokal' halaman ini tidak punya arti apa-apa.
        if (config('lisensi.mode') !== 'server') {
            return redirect()->route('aktivasi.form');
        }

        // Sudah aktif lagi — mungkin baru saja diperpanjang, atau
        // pengguna membuka alamat ini secara manual.
        if (Lisensi::aktif()) {
            return redirect()->route('dashboard');
        }

        $surat = LisensiServer::suratTersimpan();
        $disapa = LisensiServer::disapaTerakhir();

        return view('lisensi.terkunci', [
            'sekolah' => $surat?->sekolah() ?: config('lisensi.pemegang'),
            'berakhirPada' => $surat?->kedaluwarsaPada(),
            'disapaTerakhir' => $disapa,
            'alasan' => LisensiServer::galatTerakhir(),
        ]);
    }

    /**
     * Sapa server sekarang juga, lalu kembali.
     *
     * Sengaja TIDAK menampilkan galat teknis apa adanya kepada pengguna:
     * yang berguna baginya hanyalah "sudah terbuka" atau "belum".
     */
    public function periksaUlang(Request $request)
    {
        if (config('lisensi.mode') !== 'server') {
            return redirect()->route('aktivasi.form');
        }

        LisensiServer::sapa();
        Lisensi::lupakanCache();

        if (Lisensi::aktif()) {
            return redirect()->route('dashboard')
                ->with('success', 'Masa aktif sudah diperbarui. Aplikasi dapat dipakai kembali.');
        }

        return redirect()->route('lisensi.terkunci')
            ->with('error', 'Masa aktif masih belum diperbarui. Bila Anda baru saja '
                .'menghubungi FF Production, tunggu beberapa menit lalu coba lagi.');
    }
}
