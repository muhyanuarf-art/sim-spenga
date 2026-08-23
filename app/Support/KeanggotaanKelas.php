<?php

namespace App\Support;

use App\Models\Kelas;
use App\Models\RiwayatKelasSiswa;
use App\Models\Siswa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * (2026-08-23) — Perbaikan celah "form isi absensi tanggal lampau
 * kehilangan siswa yang sudah pindah kelas".
 *
 * Sebelumnya form isi Jurnal/Absensi (MengajarController::form & store)
 * mengambil daftar siswa dari `$kelas->siswas()` — yaitu siswa yang
 * kelas_id-nya SAAT INI = kelas tsb. Ini benar untuk mengisi absensi
 * TANGGAL HARI INI, tapi salah untuk mengisi/mengedit absensi TANGGAL
 * LAMPAU (guru telat isi): kalau seorang siswa sudah pindah kelas
 * setelah tanggal itu, dia tidak akan muncul lagi di form kelas asalnya
 * untuk tanggal sebelum dia pindah — padahal saat itu dia memang masih
 * anggota kelas itu.
 *
 * Kelas laporan (WaliKelasController::absensiBulanan, LaporanGuruController
 * ::absensiMapel) sudah diperbaiki lebih dulu, tapi laporan bisa memakai
 * snapshot `absensi_siswas.kelas_id` yang SUDAH tersimpan. Form pengisian
 * belum punya baris absensi sama sekali untuk sesi itu (justru itu yang
 * mau diisi), jadi tidak ada snapshot untuk disandarkan. Solusinya:
 * rekonstruksi keanggotaan kelas pada tanggal tsb dari riwayat mutasi
 * (`riwayat_kelas_siswas`).
 */
class KeanggotaanKelas
{
    /**
     * Daftar siswa yang merupakan anggota $kelas pada $tanggal (tanggal
     * efektif, berdasarkan riwayat mutasi kelas), diurutkan berdasarkan nama.
     *
     * Aturan penentuan kelas efektif seorang siswa pada $tanggal:
     * - Ambil baris riwayat_kelas_siswas milik siswa itu dengan
     *   tanggal_mutasi <= $tanggal, urutkan, ambil yang PALING AKHIR
     *   (paling dekat ke $tanggal) — itulah kelas siswa pada tanggal itu.
     * - Kalau siswa itu SAMA SEKALI tidak punya baris riwayat (data lama
     *   dari sebelum fitur riwayat kelas ada), dianggap sudah berada di
     *   kelas_id-nya SEKARANG sejak awal (tidak ada informasi mutasi untuk
     *   disandarkan).
     * - Kalau siswa PUNYA riwayat tapi baris PALING AWAL justru SESUDAH
     *   $tanggal (mis. siswa ditambah manual lewat "Tambah Siswa" — yang
     *   TIDAK membuat baris riwayat awal — lalu baru punya 1 baris riwayat
     *   saat "Pindah Kelas" dipakai): baris paling awal itu SELALU punya
     *   `kelas_asal_id` (kelas sebelum mutasi itu terjadi) kalau jenisnya
     *   pindah_kelas/kenaikan_kelas. Kelas itulah yang dianggap sebagai
     *   kelas siswa sebelum tanggal mutasi paling awal. Kalau baris paling
     *   awal itu jenis awal_masuk (kelas_asal_id null — benar-benar siswa
     *   baru), berarti sebelum tanggal itu siswa memang belum tercatat di
     *   kelas manapun oleh sistem.
     */
    public static function anggotaPadaTanggal(Kelas $kelas, string $tanggal): Collection
    {
        $tanggal = Carbon::parse($tanggal)->toDateString();

        // Kandidat: siapa saja yang PERNAH atau SEDANG berkaitan dengan
        // kelas ini — supaya tidak perlu memindai riwayat SELURUH siswa
        // sekolah, cukup yang relevan dengan kelas ini saja.
        //
        // (2026-08-23, revisi) — sebelumnya kandidat riwayat hanya dicari
        // lewat `kelas_id` (kelas TUJUAN pada baris riwayat itu). Untuk
        // KELAS ASAL, ini salah: baris riwayat "pindah_kelas" mencatat
        // kelas asal di kolom `kelas_asal_id`, BUKAN `kelas_id` (`kelas_id`
        // di baris itu berisi kelas TUJUAN). Akibatnya siswa yang baris
        // riwayat SATU-SATUNYA adalah baris pindah itu (kelas_id = kelas
        // tujuan) tidak pernah lolos jadi kandidat saat kelas yang dicek
        // adalah kelas ASAL-nya — jadi dia hilang total dari daftar,
        // bahkan untuk tanggal SEBELUM dia pindah. Sekarang kandidat juga
        // menjaring siswa lewat `kelas_asal_id`.
        $idSekarang = Siswa::where('kelas_id', $kelas->id)->where('is_active', true)->pluck('id');
        $idPernahDiRiwayat = RiwayatKelasSiswa::where('kelas_id', $kelas->id)
            ->orWhere('kelas_asal_id', $kelas->id)
            ->pluck('siswa_id');
        $idKandidat = $idSekarang->merge($idPernahDiRiwayat)->unique()->values();

        if ($idKandidat->isEmpty()) {
            return collect();
        }

        $riwayatPerSiswa = RiwayatKelasSiswa::whereIn('siswa_id', $idKandidat)
            ->whereNotNull('tanggal_mutasi')
            ->orderBy('tanggal_mutasi')
            ->orderBy('id')
            ->get()
            ->groupBy('siswa_id');

        $kelasSekarangPerSiswa = Siswa::whereIn('id', $idKandidat)->pluck('kelas_id', 'id');

        $idAnggota = $idKandidat->filter(function ($siswaId) use ($riwayatPerSiswa, $kelasSekarangPerSiswa, $kelas, $tanggal) {
            $riwayat = $riwayatPerSiswa->get($siswaId, collect());

            if ($riwayat->isEmpty()) {
                // Tidak ada riwayat sama sekali — anggap sudah di kelas
                // saat ini sejak awal.
                return ($kelasSekarangPerSiswa[$siswaId] ?? null) === $kelas->id;
            }

            $efektif = $riwayat->filter(
                fn ($r) => $r->tanggal_mutasi->toDateString() <= $tanggal
            )->last();

            if ($efektif) {
                return $efektif->kelas_id === $kelas->id;
            }

            // Semua baris riwayat justru SESUDAH $tanggal. Sebelum tanggal
            // mutasi PALING AWAL, siswa ada di kelas_asal_id baris itu
            // (kalau ada) — ini menutup celah siswa yang baru punya baris
            // riwayat pertama kali saat "Pindah Kelas" dipakai (baris
            // riwayat awal_masuk-nya tidak pernah dibuat).
            $paling_awal = $riwayat->first();
            if ($paling_awal->kelas_asal_id !== null) {
                return $paling_awal->kelas_asal_id === $kelas->id;
            }

            // Baris paling awal jenis awal_masuk (kelas_asal_id null) —
            // sebelum tanggal itu siswa memang belum tercatat di kelas manapun.
            return false;
        });

        return Siswa::whereIn('id', $idAnggota)->orderBy('nama')->get();
    }
}
