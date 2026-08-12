<?php

namespace App\Services;

use App\Models\KasusSiswa;
use App\Models\PembinaanSiswa;
use App\Models\PenguranganPoinSiswa;
use App\Models\Siswa;

/**
 * Satu-satunya tempat perhitungan poin siswa & rekomendasi tahap pembinaan.
 * Jangan taruh logika ini di Controller/Blade — selalu lewat service ini,
 * supaya rumusnya konsisten di semua halaman (profil siswa, dashboard,
 * validasi pengurangan poin, dll).
 *
 * PRINSIP (Bagian 15 & 16 spec):
 * - Poin aktif = SUM(poin kasus AKTIF) - SUM(poin pengurangan AKTIF), minimal 0.
 * - "Aktif" berarti belum dibatalkan (dibatalkan_at IS NULL) — baris yang
 *   sudah dibatalkan tetap ADA di database (riwayat/audit trail), hanya
 *   tidak lagi dihitung ke saldo.
 * - Tahap pembinaan mengikuti POIN AKTIF, bukan total historis pelanggaran.
 * - Sistem HANYA memberi REKOMENDASI tahap (1-5, dari rentang poin).
 *   Tahap 6 & 7 serta keputusan akhir SELALU manual (dipilih BK saat
 *   mencatat pembinaan) — sistem tidak pernah otomatis menjatuhkan sanksi
 *   berat sendiri.
 */
class PoinSiswaService
{
    /** Rentang poin per kategori pelanggaran (Bagian 3 spec). */
    public const RENTANG_KATEGORI = [
        'Ringan' => [5, 15],
        'Sedang' => [16, 50],
        'Berat' => [51, 75],
        'Sangat Berat' => [76, 100],
    ];

    /** Rentang poin aktif per rekomendasi tahap 1-5 (Bagian 4 spec). */
    public const RENTANG_TAHAP = [
        1 => [5, 15],
        2 => [16, 30],
        3 => [31, 50],
        4 => [51, 75],
        5 => [76, PHP_INT_MAX],
    ];

    public function totalPelanggaran(Siswa $siswa): int
    {
        return (int) KasusSiswa::where('siswa_id', $siswa->id)->aktif()->sum('poin');
    }

    public function totalPengurangan(Siswa $siswa): int
    {
        return (int) PenguranganPoinSiswa::where('siswa_id', $siswa->id)->aktif()->sum('jumlah');
    }

    public function poinAktif(Siswa $siswa): int
    {
        return max(0, $this->totalPelanggaran($siswa) - $this->totalPengurangan($siswa));
    }

    /**
     * Rekomendasi tahap 1-5 berdasarkan poin aktif saat ini. Null kalau
     * poin aktif di bawah 5 (belum masuk kriteria tahap manapun).
     * Tahap 6 & 7 TIDAK pernah direkomendasikan otomatis — itu keputusan
     * manusia (lihat catatan class di atas).
     */
    public function rekomendasiTahap(int $poinAktif): ?int
    {
        if ($poinAktif < 5) {
            return null;
        }
        foreach (self::RENTANG_TAHAP as $tahap => [$min, $max]) {
            if ($poinAktif >= $min && $poinAktif <= $max) {
                return $tahap;
            }
        }
        return 5; // >75 tetap rekomendasi tahap 5 (bukan otomatis 6/7)
    }

    /** Tahap yang SEDANG TERCATAT untuk siswa (dari pembinaan terakhir), bukan sekadar rekomendasi. */
    public function tahapSaatIni(Siswa $siswa): ?int
    {
        return PembinaanSiswa::where('siswa_id', $siswa->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->value('tahap');
    }

    /** Status siswa yang mudah dibaca (dipakai di badge UI) — hindari label negatif (Bagian 26 spec). */
    public function statusSiswa(Siswa $siswa): string
    {
        $poinAktif = $this->poinAktif($siswa);
        if ($poinAktif === 0) {
            return 'Normal';
        }

        $pembinaanBerjalan = PembinaanSiswa::where('siswa_id', $siswa->id)
            ->whereIn('status', ['Direncanakan', 'Berlangsung'])
            ->exists();

        return $pembinaanBerjalan ? 'Dalam Pembinaan' : 'Perlu Tindak Lanjut';
    }

    public function validasiPoinSesuaiKategori(string $kategori, int $poin): bool
    {
        [$min, $max] = self::RENTANG_KATEGORI[$kategori] ?? [0, 0];
        return $poin >= $min && $poin <= $max;
    }

    /** Ringkasan lengkap untuk halaman profil siswa (Bagian 14 & 26 spec). */
    public function ringkasan(Siswa $siswa): array
    {
        $pelanggaran = $this->totalPelanggaran($siswa);
        $pengurangan = $this->totalPengurangan($siswa);
        $poinAktif = max(0, $pelanggaran - $pengurangan);

        return [
            'total_pelanggaran' => $pelanggaran,
            'total_pengurangan' => $pengurangan,
            'poin_aktif' => $poinAktif,
            'rekomendasi_tahap' => $this->rekomendasiTahap($poinAktif),
            'tahap_saat_ini' => $this->tahapSaatIni($siswa),
            'status' => $this->statusSiswa($siswa),
            'jumlah_kasus' => KasusSiswa::where('siswa_id', $siswa->id)->aktif()->count(),
            'jumlah_pembinaan' => PembinaanSiswa::where('siswa_id', $siswa->id)->count(),
        ];
    }
}
