<?php

namespace App\Support;

use App\Models\PengaturanSekolah;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;

/**
 * Mengganti placeholder di template Jenis Surat dengan data sebenarnya
 * (mail-merge sederhana). Placeholder yang didukung — dicantumkan juga
 * di halaman Jenis Surat supaya Kesiswaan/BK tahu apa saja yang bisa
 * dipakai saat menulis template:
 *
 *   {nama_siswa}   {nis}   {nisn}   {kelas}   {nama_ortu}
 *   {tanggal}      {no_surat}       {nama_sekolah}
 *   {tanggal_surat_dibuat}          {lokasi_ttd}
 *   {nama_guru_ttd}                 {nip_guru_ttd}
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
        '{tanggal_surat_dibuat}' => 'Tanggal surat (sama dengan {tanggal}) — untuk baris "Kota, tanggal" di penutup surat',
        '{lokasi_ttd}' => 'Kota/lokasi tanda tangan (dari Pengaturan Sekolah)',
        '{nama_guru_ttd}' => 'Nama guru/staf pembuat surat (akun yang sedang login), untuk baris tanda tangan',
        '{nip_guru_ttd}' => 'NIP guru/staf pembuat surat (akun yang sedang login), untuk baris tanda tangan',
    ];

    public static function isi(string $template, Siswa $siswa, string $tanggal, ?string $noSurat, ?User $guru = null): string
    {
        $tanggalFormatted = Carbon::parse($tanggal)->translatedFormat('d F Y');
        $pengaturan = PengaturanSekolah::current();

        $pengganti = [
            '{nama_siswa}' => $siswa->nama,
            '{nis}' => $siswa->nis,
            '{nisn}' => $siswa->nisn ?? '-',
            '{kelas}' => $siswa->kelas->nama_kelas ?? '-',
            '{nama_ortu}' => $siswa->nama_ortu ?? '-',
            '{tanggal}' => $tanggalFormatted,
            '{no_surat}' => $noSurat ?: '-',
            '{nama_sekolah}' => $pengaturan->nama_sekolah ?? '-',
            '{tanggal_surat_dibuat}' => $tanggalFormatted,
            '{lokasi_ttd}' => $pengaturan->lokasiTtd(),
            '{nama_guru_ttd}' => $guru->name ?? '............................',
            '{nip_guru_ttd}' => $guru->nip ?? '............................',
        ];

        return strtr($template, $pengganti);
    }
}
