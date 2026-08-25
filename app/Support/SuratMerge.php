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
 *   {tanggal_acara} {waktu_acara}
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
        '{tanggal}' => 'Tanggal surat dibuat (format: 24 Agustus 2026)',
        '{no_surat}' => 'Nomor surat (otomatis)',
        '{nama_sekolah}' => 'Nama sekolah (dari Pengaturan Sekolah)',
        '{tanggal_acara}' => 'Tanggal acara/pemanggilan yang dimaksud dalam surat (kosong kalau tidak diisi)',
        '{waktu_acara}' => 'Jam acara/pemanggilan (format: 08.00 WIB, kosong kalau tidak diisi)',
    ];

    public static function isi(
        string $template,
        Siswa $siswa,
        string $tanggal,
        ?string $noSurat,
        ?string $tanggalAcara = null,
        ?string $waktuAcara = null
    ): string {
        $pengganti = [
            '{nama_siswa}' => $siswa->nama,
            '{nis}' => $siswa->nis,
            '{nisn}' => $siswa->nisn ?? '-',
            '{kelas}' => $siswa->kelas->nama_kelas ?? '-',
            '{nama_ortu}' => $siswa->nama_ortu ?? '-',
            '{tanggal}' => Carbon::parse($tanggal)->translatedFormat('d F Y'),
            '{no_surat}' => $noSurat ?: '-',
            '{nama_sekolah}' => PengaturanSekolah::current()->nama_sekolah ?? '-',
            '{tanggal_acara}' => $tanggalAcara ? Carbon::parse($tanggalAcara)->translatedFormat('d F Y') : '-',
            '{waktu_acara}' => $waktuAcara ? str_replace(':', '.', $waktuAcara) . ' WIB' : '-',
        ];

        return strtr($template, $pengganti);
    }
}
