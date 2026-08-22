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

    /**
     * Status siswa yang mudah dibaca (dipakai di badge UI) — hindari label negatif (Bagian 26 spec).
     * Statusnya SELALU mengikuti record Pembinaan PALING TERAKHIR (bukan sekadar
     * "apakah pernah ada pembinaan berjalan"), supaya otomatis berubah tiap kali
     * ada laporan/pembinaan baru dicatat oleh BK.
     */
    public function statusSiswa(Siswa $siswa): string
    {
        $poinAktif = $this->poinAktif($siswa);
        if ($poinAktif === 0) {
            return 'Normal';
        }

        $pembinaanTerakhir = PembinaanSiswa::where('siswa_id', $siswa->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->first();

        return match ($pembinaanTerakhir?->status) {
            'Selesai' => 'Selesai',
            'Pembinaan' => 'Dalam Pembinaan',
            default => 'Menunggu Pembinaan', // ada poin aktif, tapi belum pernah dicatat pembinaan
        };
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

    /**
     * PERBAIKAN PERFORMA — sama persis hasilnya dengan memanggil ringkasan()
     * untuk tiap siswa satu-satu, TAPI jumlah query TETAP (sekitar 4 query
     * total) berapa pun banyaknya siswa — bukan lagi ~9 query PER SISWA
     * (N+1). ringkasan() aslinya dipertahankan apa adanya untuk halaman
     * yang memang cuma butuh 1 siswa (profil siswa) — method ini KHUSUS
     * dipakai di halaman yang menampilkan BANYAK siswa sekaligus (Dashboard
     * BK "Pantau Pelanggaran", Monitoring Siswa BK), supaya tidak query
     * berulang-ulang untuk tiap baris.
     *
     * @param  iterable<int>  $siswaIds
     * @return array<int, array>  siswa_id => ringkasan (struktur SAMA seperti ringkasan())
     */
    public function ringkasanBanyak(iterable $siswaIds): array
    {
        $ids = collect($siswaIds)->filter()->unique()->values()->all();
        if (empty($ids)) {
            return [];
        }

        $pelanggaranPerSiswa = KasusSiswa::whereIn('siswa_id', $ids)->aktif()
            ->selectRaw('siswa_id, SUM(poin) as total_poin, COUNT(*) as jumlah')
            ->groupBy('siswa_id')->get()->keyBy('siswa_id');

        $penguranganPerSiswa = PenguranganPoinSiswa::whereIn('siswa_id', $ids)->aktif()
            ->selectRaw('siswa_id, SUM(jumlah) as total_jumlah')
            ->groupBy('siswa_id')->get()->keyBy('siswa_id');

        $jumlahPembinaanPerSiswa = PembinaanSiswa::whereIn('siswa_id', $ids)
            ->selectRaw('siswa_id, COUNT(*) as jumlah')
            ->groupBy('siswa_id')->get()->keyBy('siswa_id');

        // Pembinaan PALING TERAKHIR per siswa (buat tahap_saat_ini & status) —
        // ambil semua baris punya siswa dalam $ids (bukan seluruh sekolah),
        // urutkan, ambil yang pertama per grup di PHP. Aman karena jumlah
        // barisnya sebanding dengan $ids yang diminta, bukan seluruh tabel.
        $pembinaanTerakhirPerSiswa = PembinaanSiswa::whereIn('siswa_id', $ids)
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->get(['siswa_id', 'tahap', 'status'])
            ->unique('siswa_id')->keyBy('siswa_id');

        $hasil = [];
        foreach ($ids as $id) {
            $pelanggaran = (int) ($pelanggaranPerSiswa[$id]->total_poin ?? 0);
            $pengurangan = (int) ($penguranganPerSiswa[$id]->total_jumlah ?? 0);
            $poinAktif = max(0, $pelanggaran - $pengurangan);
            $pembinaanTerakhir = $pembinaanTerakhirPerSiswa[$id] ?? null;

            $status = 'Normal';
            if ($poinAktif > 0) {
                $status = match ($pembinaanTerakhir?->status) {
                    'Selesai' => 'Selesai',
                    'Pembinaan' => 'Dalam Pembinaan',
                    default => 'Menunggu Pembinaan',
                };
            }

            $hasil[$id] = [
                'total_pelanggaran' => $pelanggaran,
                'total_pengurangan' => $pengurangan,
                'poin_aktif' => $poinAktif,
                'rekomendasi_tahap' => $this->rekomendasiTahap($poinAktif),
                'tahap_saat_ini' => $pembinaanTerakhir?->tahap,
                'status' => $status,
                'jumlah_kasus' => (int) ($pelanggaranPerSiswa[$id]->jumlah ?? 0),
                'jumlah_pembinaan' => (int) ($jumlahPembinaanPerSiswa[$id]->jumlah ?? 0),
            ];
        }

        return $hasil;
    }
}
