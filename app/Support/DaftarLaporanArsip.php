<?php

namespace App\Support;

/**
 * DAFTAR LAPORAN YANG MASUK ARSIP SEMESTER.
 *
 * =====================================================================
 * SATU SUMBER KEBENARAN
 * =====================================================================
 * Menambah laporan ke arsip cukup menambah satu baris di sini. Tidak
 * ada template PDF yang perlu ditulis, tidak ada kueri yang perlu
 * digandakan — pembuat arsip merender RUTE ASLINYA, halaman yang sama
 * persis dengan yang dilihat guru di layar.
 *
 * Itu keputusan yang disengaja. Menyalin logika laporan ke template PDF
 * tersendiri berarti dua tempat yang harus ikut berubah setiap kali
 * rumus nilai atau aturan periode disesuaikan — dan tempat kedua itulah
 * yang akan tertinggal, lalu menghasilkan arsip yang angkanya berbeda
 * dengan layar tanpa ada yang menyadarinya.
 *
 * =====================================================================
 * BENTUK SATU BARIS
 * =====================================================================
 *   peran   folder tujuan di dalam ZIP
 *   judul   nama berkas PDF-nya
 *   route   nama rute Laravel yang dirender
 *   per     'kelas'  → diulang untuk tiap kelas, semuanya digabung
 *                      menjadi SATU PDF dengan tiap kelas sebagai bab
 *           null     → dirender sekali saja
 *   query   parameter tambahan (opsional)
 */
class DaftarLaporanArsip
{
    /** @return array<int, array{peran:string, judul:string, route:string, per:?string, query?:array}> */
    public static function semua(): array
    {
        return [
            // ===== WALI KELAS =====
            [
                'peran' => 'wali-kelas',
                'judul' => 'Laporan Akhir Semester',
                'route' => 'nilai.laporan-semester',
                'per' => 'kelas',
            ],
            [
                'peran' => 'wali-kelas',
                'judul' => 'Nilai Rapor Kelas',
                'route' => 'nilai.rekap-kelas',
                'per' => 'kelas',
            ],
            [
                'peran' => 'wali-kelas',
                'judul' => 'Nilai per Mata Pelajaran',
                'route' => 'nilai.per-mapel',
                'per' => 'kelas',
            ],
            [
                'peran' => 'wali-kelas',
                'judul' => 'Jurnal Mengajar Kelas',
                'route' => 'walikelas.jurnal-kelas',
                'per' => 'kelas',
            ],

            // ===== KURIKULUM =====
            [
                'peran' => 'kurikulum',
                'judul' => 'Monitoring Input Nilai',
                'route' => 'nilai.monitoring',
                'per' => null,
            ],
            [
                'peran' => 'kurikulum',
                'judul' => 'Jurnal Mengajar Guru',
                'route' => 'laporan.jurnal-guru',
                'per' => null,
            ],
            [
                'peran' => 'kurikulum',
                'judul' => 'Kehadiran Mengajar Guru',
                'route' => 'laporan.absensi-guru',
                'per' => null,
            ],

            // ===== BIMBINGAN KONSELING =====
            [
                'peran' => 'bk',
                'judul' => 'Ringkasan BK',
                'route' => 'bk.dashboard',
                'per' => null,
            ],
            [
                'peran' => 'bk',
                'judul' => 'Buku Catatan BK',
                'route' => 'bk.kasus.index',
                'per' => null,
            ],
            [
                'peran' => 'bk',
                'judul' => 'Laporan Bulanan BK',
                'route' => 'bk.laporan-bulanan',
                'per' => null,
            ],

            // ===== KESISWAAN =====
            [
                'peran' => 'kesiswaan',
                'judul' => 'Prestasi Siswa',
                'route' => 'prestasi.index',
                'per' => null,
            ],
            [
                'peran' => 'kesiswaan',
                'judul' => 'Kegiatan Sekolah',
                'route' => 'kegiatan.index',
                'per' => null,
            ],
            [
                'peran' => 'kesiswaan',
                'judul' => 'Ekstrakurikuler',
                'route' => 'ekstrakurikuler.index',
                'per' => null,
            ],
        ];
    }

    /** Nama folder yang enak dibaca, dipakai sebagai judul di ringkasan. */
    public static function labelPeran(string $peran): string
    {
        return [
            'wali-kelas' => 'Wali Kelas',
            'kurikulum' => 'Kurikulum',
            'bk' => 'Bimbingan Konseling',
            'kesiswaan' => 'Kesiswaan',
        ][$peran] ?? $peran;
    }
}
