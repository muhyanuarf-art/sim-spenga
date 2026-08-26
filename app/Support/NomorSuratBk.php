<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Format nomor surat BK — TIDAK auto-increment seperti App\Support\
 * NomorSurat (yang dipakai fitur surat umum sebelumnya). Sesuai
 * instruksi: "422" & "BK" tetap/otomatis, bulan-romawi & tahun otomatis
 * dari tanggal surat, TAPI nomor urutnya diisi TANGAN oleh guru BK
 * (wajib, tidak dihasilkan sistem) — supaya cocok dengan buku agenda
 * surat fisik yang sudah mereka pakai.
 *
 * Contoh hasil: 422/15/BK/VIII/2026
 */
class NomorSuratBk
{
    private const BULAN_ROMAWI = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    public const PREFIX_TETAP = '422';
    public const KODE_TETAP = 'BK';

    public static function buat(string $nomorUrutManual, string $tanggal): string
    {
        $tanggalObj = Carbon::parse($tanggal);
        $bulanRomawi = self::BULAN_ROMAWI[(int) $tanggalObj->month];

        return sprintf(
            '%s/%s/%s/%s/%d',
            self::PREFIX_TETAP,
            trim($nomorUrutManual),
            self::KODE_TETAP,
            $bulanRomawi,
            $tanggalObj->year
        );
    }

    /** Pratinjau format, dipakai di form sebelum nomor urut diisi. */
    public static function pratinjau(string $tanggal): string
    {
        return self::buat('...', $tanggal);
    }
}
