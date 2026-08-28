<?php

namespace App\Http\Controllers;

use App\Models\OrangTua;
use App\Models\Siswa;
use Illuminate\Http\Request;

/**
 * AKUN PORTAL ORANG TUA.
 *
 * (2026-08-28) — menu "Akun Orang Tua" yang berdiri sendiri DIHAPUS, dan
 * pengelolaannya dipindah menjadi satu kolom di menu Data Siswa. Alasannya:
 *
 * 1. Tabel `orang_tuas` TIDAK menyimpan data apa pun selain kredensial —
 *    isinya cuma siswa_id, nis (salinan dari siswa), dan password. Nama &
 *    nomor WhatsApp orang tua justru ada di tabel `siswas`. Jadi tidak ada
 *    "data akun" yang perlu dikelola tersendiri; yang ada hanyalah TIGA
 *    tindakan: buatkan akun, reset password, dan hapus akun.
 * 2. Ketiga tindakan itu selalu bermula dari seorang siswa. Menaruhnya di
 *    Data Siswa berarti admin tidak perlu berpindah menu, dan status akun
 *    langsung terlihat di baris siswanya.
 * 3. Menu lamanya RUSAK: rutenya menunjuk method importForm/import/template
 *    yang sudah tidak ada di controller ini, dan view-nya memanggil route
 *    'orangtua-akun.generate' yang tidak pernah didaftarkan — ketiga URL-nya
 *    menghasilkan error 500.
 *
 * Yang TIDAK berubah: cara orang tua login (NIS anak + password) dan
 * seluruh isi Portal Orang Tua.
 */
class OrangTuaController extends Controller
{
    /**
     * Buatkan akun untuk SEMUA siswa aktif yang belum punya — 1 siswa =
     * 1 akun, NIS siswa sebagai nama pengguna, password default. Aman
     * dijalankan berkali-kali (siswa yang sudah punya akun dilewati).
     */
    public function generate(Request $request)
    {
        // Dibatasi siswa periode aktif — akun tidak dibuatkan untuk
        // siswa angkatan lama yang sudah tidak bersekolah di sini.
        $belumPunyaAkun = Siswa::periodeAktif()->where('is_active', true)
            ->whereDoesntHave('orangTua')
            ->get();

        foreach ($belumPunyaAkun as $siswa) {
            OrangTua::create([
                'siswa_id' => $siswa->id,
                'nis' => $siswa->nis,
                'password' => OrangTua::PASSWORD_DEFAULT,
            ]);
        }

        if ($belumPunyaAkun->isEmpty()) {
            return back()->with('success', 'Semua siswa aktif sudah punya akun orang tua — tidak ada akun baru yang dibuat.');
        }

        return back()->with('success', "Berhasil membuat {$belumPunyaAkun->count()} akun orang tua. "
            .'Password awalnya "'.OrangTua::PASSWORD_DEFAULT.'" dan wajib diganti orang tua setelah login pertama.');
    }

    /** Buatkan akun untuk SATU siswa (dipakai dari baris di Data Siswa). */
    public function buatSatu(Siswa $siswa)
    {
        if ($siswa->orangTua) {
            return back()->with('success', "{$siswa->nama} sudah punya akun orang tua.");
        }

        OrangTua::create([
            'siswa_id' => $siswa->id,
            'nis' => $siswa->nis,
            'password' => OrangTua::PASSWORD_DEFAULT,
        ]);

        return back()->with('success', "Akun orang tua untuk {$siswa->nama} dibuat. "
            .'Login memakai NIS '.$siswa->nis.', password "'.OrangTua::PASSWORD_DEFAULT.'".');
    }

    public function resetPassword(OrangTua $orangTua)
    {
        $orangTua->update([
            'password' => OrangTua::PASSWORD_DEFAULT,
            'password_diubah_at' => null,
        ]);

        return back()->with('success', "Password akun orang tua NIS {$orangTua->nis} direset ke \""
            .OrangTua::PASSWORD_DEFAULT.'".');
    }

    public function destroy(OrangTua $orangTua)
    {
        $nis = $orangTua->nis;
        $orangTua->delete();

        return back()->with('success', "Akun orang tua NIS {$nis} dihapus. Orang tua tidak bisa login sampai akunnya dibuat ulang.");
    }
}
