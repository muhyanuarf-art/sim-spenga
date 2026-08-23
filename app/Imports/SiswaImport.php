<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\RiwayatKelasSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

/**
 * Format kolom excel (header baris 1):
 * nis | nisn | nama | nama_ortu | no_wa_ortu | jenis_kelamin | kode_kelas
 *
 * (Revisi permintaan admin) — fitur "Kenaikan Kelas" sudah dihapus. Sekolah
 * ini memindahkan siswa antar kelas/tahun ajaran LEWAT IMPORT EXCEL ini,
 * bukan lewat proses kenaikan kelas manual. Supaya histori kelas siswa
 * (Riwayat Kelas) TETAP tersimpan & bisa dilihat admin maupun orang tua,
 * import ini SEKARANG OTOMATIS mencatat baris riwayat_kelas_siswas setiap
 * kali kelas_id seorang siswa berubah — persis prinsip yang sama seperti
 * dulu dilakukan manual lewat menu Kenaikan Kelas, hanya pemicunya sekarang
 * import Excel.
 */
class SiswaImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    /** @var array<int,string> NIS yang dilewati karena kode_kelas tidak ditemukan. */
    public array $dilewatiKelasTidakDitemukan = [];

    public function model(array $row)
    {
        // STEP 5 — kelas sekarang per tahun ajaran; siswa diimpor selalu ke
        // kelas pada TAHUN AJARAN AKTIF (import data siswa adalah operasi
        // "saat ini", bukan histori).
        $tahunAjaranAktif = TahunAjaran::aktif();
        $kelas = Kelas::aktif()->where('nama_kelas', trim($row['kode_kelas']))->first();
        if (! $kelas) {
            $this->dilewatiKelasTidakDitemukan[] = trim($row['nis'] ?? '');
            return null;
        }

        $nis = trim($row['nis']);
        $siswaLama = Siswa::where('nis', $nis)->first();
        $kelasAsalId = $siswaLama?->kelas_id; // null kalau siswa baru sama sekali

        $siswa = Siswa::updateOrCreate(
            ['nis' => $nis],
            [
                'nisn' => $row['nisn'] ?? null,
                'nama' => trim($row['nama']),
                'nama_ortu' => isset($row['nama_ortu']) ? trim($row['nama_ortu']) : null,
                'no_wa_ortu' => isset($row['no_wa_ortu']) ? trim((string) $row['no_wa_ortu']) : null,
                'jenis_kelamin' => strtoupper(trim($row['jenis_kelamin'])) === 'P' ? 'P' : 'L',
                'kelas_id' => $kelas->id,
                'is_active' => true,
            ]
        );

        // Catat Riwayat Kelas untuk Tahun Ajaran aktif SAAT INI — baik untuk
        // siswa baru (kelas_asal_id = null, "pertama kali masuk") maupun
        // siswa lama yang pindah kelas/naik kelas lewat import (ganti tahun
        // ajaran). Konvensi tahun_ajaran_id SELALU baris Semester Ganjil
        // (sama seperti sebelumnya di menu Kenaikan Kelas).
        //
        // (2026-08-23) — jenis dibedakan dari 'pindah_kelas' (mutasi di
        // tengah tahun ajaran berjalan, lihat SiswaController::pindahKelas())
        // supaya keduanya bisa hidup berdampingan di baris yang berbeda pada
        // tahun ajaran yang sama. firstOrCreate DI-SCOPE juga dengan jenis
        // supaya tetap mencegah dobel kalau siswa yang sama diimpor
        // berkali-kali di tahun ajaran yang sama (perilaku lama tetap sama —
        // hanya baris "kenaikan_kelas"/"awal_masuk" pertama yang tercatat,
        // import ulang berikutnya tidak menimpanya).
        if ($tahunAjaranAktif) {
            $tahunAjaranGanjil = TahunAjaran::where('nama', $tahunAjaranAktif->nama)->where('semester', 'Ganjil')->first();
            if ($tahunAjaranGanjil) {
                RiwayatKelasSiswa::firstOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $tahunAjaranGanjil->id,
                        'jenis' => $kelasAsalId === null ? RiwayatKelasSiswa::JENIS_AWAL_MASUK : RiwayatKelasSiswa::JENIS_KENAIKAN_KELAS,
                    ],
                    [
                        'kelas_asal_id' => $kelasAsalId,
                        'kelas_id' => $kelas->id,
                        'tanggal_mutasi' => now()->toDateString(),
                        'keterangan' => 'Dicatat otomatis dari Import Excel Data Siswa.',
                        'dicatat_oleh_id' => auth()->id(),
                    ]
                );
            }
        }

        return $siswa;
    }
}
