<?php

namespace App\Support;

use App\Models\TahunAjaran;
use Illuminate\Support\Facades\Session;

/**
 * PERIODE YANG SEDANG DILIHAT PENGGUNA.
 *
 * =====================================================================
 * DUA PENGERTIAN "PERIODE" YANG TIDAK BOLEH TERTUKAR
 * =====================================================================
 * 1. PERIODE AKTIF  — Tahun Ajaran + Semester yang sedang BERJALAN di
 *    sekolah. Ditentukan admin lewat tombol "Aktifkan". Satu untuk
 *    seluruh sekolah. Semua PENCATATAN BARU selalu masuk ke sini.
 *    Sumbernya tetap TahunAjaran::aktif() / PeriodeAkademik::aktif().
 *
 * 2. PERIODE PILIHAN — periode yang sedang DILIHAT oleh satu pengguna,
 *    dipilih lewat dropdown di kepala halaman dan disimpan di session
 *    masing-masing. Defaultnya sama dengan periode aktif. Inilah yang
 *    dipakai SELURUH PEMBACAAN data: daftar kelas, siswa, mapel, nilai,
 *    absensi, BK, penugasan wali kelas & pembina, dan seterusnya.
 *
 * =====================================================================
 * KENAPA DIPISAH
 * =====================================================================
 * Sebelum ini, satu-satunya periode yang bisa dilihat adalah periode
 * aktif. Akibatnya wali kelas 7A Semester Ganjil kehilangan seluruh
 * aksesnya begitu Semester Genap diaktifkan dan kelasnya diserahkan ke
 * guru lain — padahal rekap nilai, absensi, dan jurnal Semester Ganjil
 * miliknya masih utuh di database, cuma tidak ada jalan untuk membukanya.
 * Bahkan Admin pun tidak bisa, karena semua halaman laporan mengunci diri
 * ke periode aktif.
 *
 * Dengan pemisahan ini, pengguna cukup memilih periode lama di dropdown
 * dan seluruh halaman menampilkan keadaan periode itu — TERMASUK peran
 * penggunanya sendiri pada periode itu (lihat User::kelasWali() yang
 * membaca penugasan_wali_kelas periode pilihan, bukan periode aktif).
 *
 * =====================================================================
 * PERIODE SELAIN YANG AKTIF SELALU BACA-SAJA
 * =====================================================================
 * bolehTulis() hanya true kalau periode pilihan PERSIS periode aktif dan
 * periode itu tidak terkunci. Penegakannya di middleware 'periode-aktif'
 * (App\Http\Middleware\EnsurePeriodeTidakTerkunci) yang sudah terpasang
 * di seluruh rute tulis, jadi tidak ada satu pun jalur simpan yang bisa
 * menembusnya — termasuk kalau ada yang mengirim POST manual.
 *
 * Ini juga menutup kebingungan lama: menyimpan data saat "sedang melihat"
 * periode lampau dulu akan tetap tercatat di periode aktif (karena semua
 * create memakai TahunAjaran::aktif()), sehingga datanya seolah lenyap.
 */
class KonteksPeriode
{
    private const KUNCI_SESSION = 'periode_dipilih_id';

    /** Cache per-request supaya tidak query berulang dalam satu halaman. */
    private static ?TahunAjaran $cache = null;

    private static bool $sudahDibaca = false;

    /**
     * Periode yang sedang DILIHAT. Default & fallback: periode aktif.
     *
     * Pilihan di session divalidasi ulang setiap request — kalau baris
     * periodenya sudah dihapus admin, pilihan itu dibuang diam-diam dan
     * kembali ke periode aktif, bukan menampilkan halaman error.
     */
    public static function pilihan(): ?TahunAjaran
    {
        if (self::$sudahDibaca) {
            return self::$cache;
        }

        self::$sudahDibaca = true;

        $id = Session::get(self::KUNCI_SESSION);

        if ($id) {
            $periode = TahunAjaran::find($id);

            if ($periode) {
                return self::$cache = $periode;
            }

            Session::forget(self::KUNCI_SESSION);
        }

        return self::$cache = TahunAjaran::aktif();
    }

    /** Ganti periode yang dilihat. Kirim null untuk kembali ke periode aktif. */
    public static function pilih(?TahunAjaran $periode): void
    {
        if (! $periode || $periode->is_active) {
            Session::forget(self::KUNCI_SESSION);
        } else {
            Session::put(self::KUNCI_SESSION, $periode->id);
        }

        self::lupakanCache();
    }

    /** Apakah pengguna sedang melihat periode yang benar-benar berjalan? */
    public static function melihatPeriodeAktif(): bool
    {
        $pilihan = self::pilihan();
        $aktif = TahunAjaran::aktif();

        return $pilihan && $aktif && $pilihan->id === $aktif->id;
    }

    /**
     * Boleh menyimpan/mengubah data sekarang?
     *
     * Syaratnya dua-duanya: sedang melihat periode aktif, DAN periode itu
     * belum ditutup admin.
     */
    public static function bolehTulis(): bool
    {
        return self::melihatPeriodeAktif() && ! TahunAjaran::aktif()?->isTerkunci();
    }

    /** Sedang dalam mode baca-saja karena melihat periode lampau (bukan karena terkunci). */
    public static function modeLihatSaja(): bool
    {
        return self::pilihan() !== null && ! self::melihatPeriodeAktif();
    }

    /**
     * Alasan yang bisa dibaca pengguna kenapa halaman ini baca-saja —
     * null kalau memang boleh menulis.
     */
    public static function alasanBacaSaja(): ?string
    {
        if (self::bolehTulis()) {
            return null;
        }

        $pilihan = self::pilihan();

        if (! $pilihan) {
            return 'Belum ada Tahun Ajaran yang aktif. Hubungi Kurikulum/Admin untuk mengaktifkan periode terlebih dahulu.';
        }

        if (self::modeLihatSaja()) {
            return 'Anda sedang melihat '.$pilihan->labelPeriode().', yang bukan periode berjalan. '
                .'Data hanya dapat dilihat dan dicetak. Untuk mencatat data baru, kembali ke '
                .(TahunAjaran::aktif()?->labelPeriode() ?? 'periode aktif').' lewat pemilih periode di kanan atas.';
        }

        return 'Periode '.$pilihan->labelPeriode().' sudah ditutup dan terkunci. '
            .'Data hanya dapat dilihat dan dicetak. Hubungi Admin bila benar-benar perlu dibuka kembali.';
    }

    /**
     * Daftar periode yang boleh dipilih pengguna ini.
     *
     * Semua peran boleh menengok periode lampau — justru itu tujuannya.
     * Yang membatasi APA yang terlihat di dalamnya tetap aturan peran yang
     * sudah ada (wali kelas hanya kelas perwaliannya PADA PERIODE ITU,
     * guru mapel hanya kelas yang diampunya pada periode itu, dan
     * seterusnya). Periode yang belum pernah berjalan ("Akan Datang")
     * disembunyikan dari guru supaya tidak membingungkan — isinya memang
     * masih kosong dan cuma dipakai admin saat menyiapkan tahun berikutnya.
     */
    public static function daftarPilihan(?string $role = null): \Illuminate\Support\Collection
    {
        $aktif = TahunAjaran::aktif();

        return TahunAjaran::orderByDesc('nama')
            ->orderByRaw("FIELD(semester, 'Genap', 'Ganjil')")
            ->get()
            ->filter(function (TahunAjaran $t) use ($aktif, $role) {
                if ($aktif && $t->id === $aktif->id) {
                    return true;
                }

                if ($t->status === TahunAjaran::STATUS_AKAN_DATANG) {
                    return in_array($role, ['admin', 'kurikulum'], true);
                }

                return true;
            })
            ->values();
    }

    public static function lupakanCache(): void
    {
        self::$cache = null;
        self::$sudahDibaca = false;
    }
}
