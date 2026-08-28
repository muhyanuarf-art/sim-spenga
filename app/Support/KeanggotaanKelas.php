<?php

namespace App\Support;

use App\Models\AnggotaKelas;
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

        // (2026-08-29) Ditulis ulang setelah keanggotaan kelas pindah ke
        // tabel anggota_kelas dan kelas menjadi milik SATU SEMESTER.
        //
        // Sumber kebenarannya sekarang anggota_kelas — daftar itu SUDAH
        // benar untuk semester ini. Riwayat dipakai hanya untuk MENGGESER
        // daftar tersebut ke tanggal yang diminta, dua penyesuaian saja:
        //
        //   1. KELUARKAN anggota sekarang yang baru MASUK kelas ini SESUDAH
        //      $tanggal — pada tanggal itu dia masih di kelas lain.
        //   2. MASUKKAN KEMBALI siswa yang KELUAR dari kelas ini sesudah
        //      $tanggal — pada tanggal itu dia masih anggota di sini.
        //
        // Cara lama merekonstruksi dari nol lewat riwayat, dan itu pecah
        // begitu tiap semester punya baris kelasnya sendiri: baris riwayat
        // menunjuk kelas semester lain, sehingga SELURUH anggota terbuang
        // dan form absensi tampil kosong.
        $idAnggota = AnggotaKelas::where('kelas_id', $kelas->id)->pluck('siswa_id')->all();

        // Hanya riwayat yang menyangkut kelas ini — mutasi antar kelas lain
        // tidak mengubah keanggotaan kelas ini.
        $riwayat = RiwayatKelasSiswa::whereNotNull('tanggal_mutasi')
            ->where(function ($q) use ($kelas) {
                $q->where('kelas_id', $kelas->id)->orWhere('kelas_asal_id', $kelas->id);
            })
            // MUNDUR: daftar sekarang dibatalkan satu per satu dari mutasi
            // paling akhir ke belakang. Urutan maju salah kalau ada dua mutasi
            // di tanggal yang sama (mis. siswa keluar lalu kembali ke kelas
            // yang sama pada hari itu) — hasilnya bisa terbalik.
            ->orderByDesc('tanggal_mutasi')->orderByDesc('id')
            ->get();

        foreach ($riwayat as $r) {
            $tglMutasi = $r->tanggal_mutasi->toDateString();

            if ($tglMutasi <= $tanggal) {
                continue; // sudah terjadi sebelum tanggal ini — daftar sekarang sudah benar
            }

            // Hanya PINDAH KELAS di tengah semester yang benar-benar
            // menggeser keanggotaan. Baris "awal masuk" & "kenaikan kelas"
            // adalah catatan administratif — tanggalnya tanggal PENDATAAN,
            // bukan tanggal siswa mulai duduk di kelas itu. Dulu keduanya
            // ikut dihitung, sehingga form absensi untuk tanggal sebelum
            // pendataan tampil nyaris kosong.
            if ((int) $r->kelas_id === (int) $kelas->id
                && $r->jenis === RiwayatKelasSiswa::JENIS_PINDAH_KELAS) {
                $idAnggota = array_values(array_diff($idAnggota, [$r->siswa_id]));
            }

            if ((int) $r->kelas_asal_id === (int) $kelas->id) {
                $idAnggota[] = $r->siswa_id;
            }
        }

        $idAnggota = collect($idAnggota)->unique()->values();
        return Siswa::whereIn('id', $idAnggota)->where('is_active', true)->orderBy('nama')->get();
    }
}
