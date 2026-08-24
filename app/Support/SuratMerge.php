<?php

namespace App\Support;

use App\Models\PengaturanSekolah;
use App\Models\Siswa;
use Carbon\Carbon;

/**
 * Mengganti placeholder di template Jenis Surat dengan data sebenarnya
 * (mail-merge sederhana). Placeholder yang didukung — dicantumkan juga
 * di halaman Jenis Surat supaya Kesiswaan/BK tahu apa saja yang bisa
 * dipakai saat menulis template:
 *
 *   {nama_siswa}   {nis}   {nisn}   {kelas}   {nama_ortu}
 *   {tanggal}      {no_surat}       {nama_sekolah}
 *
 * Hasil gabungan ini yang DISIMPAN sebagai isi final 1 surat (bukan
 * template mentahnya lagi) — jadi kalau template diedit belakangan,
 * surat yang sudah pernah dibuat tidak ikut berubah.
 */
class SuratMerge
{
    public const DAFTAR_PLACEHOLDER = [
        '{nama_siswa}' => 'Nama siswa',
        '{nis}' => 'NIS siswa',
        '{nisn}' => 'NISN siswa',
        '{kelas}' => 'Nama kelas siswa',
        '{nama_ortu}' => 'Nama orang tua/wali siswa',
        '{tanggal}' => 'Tanggal surat (format: 24 Agustus 2026)',
        '{no_surat}' => 'Nomor surat',
        '{nama_sekolah}' => 'Nama sekolah (dari Pengaturan Sekolah)',
    ];

    public static function isi(string $template, Siswa $siswa, string $tanggal, ?string $noSurat): string
    {
        $pengganti = [
            '{nama_siswa}' => $siswa->nama,
            '{nis}' => $siswa->nis,
            '{nisn}' => $siswa->nisn ?? '-',
            '{kelas}' => $siswa->kelas->nama_kelas ?? '-',
            '{nama_ortu}' => $siswa->nama_ortu ?? '-',
            '{tanggal}' => Carbon::parse($tanggal)->translatedFormat('d F Y'),
            '{no_surat}' => $noSurat ?: '-',
            '{nama_sekolah}' => PengaturanSekolah::current()->nama_sekolah ?? '-',
        ];

        return strtr($template, $pengganti);
    }
}
