<?php

namespace App\Support;

use App\Models\TahunAjaran;

/**
 * Satu sumber utama untuk mengetahui Periode Akademik Aktif
 * (Tahun Ajaran + Semester yang sedang berjalan).
 *
 * Semua kode baru WAJIB memanggil PeriodeAkademik::aktif() (bukan
 * query TahunAjaran::where(...) sendiri-sendiri), supaya definisi
 * "periode aktif" hanya ada di satu tempat.
 *
 * Kode lama yang sudah memanggil TahunAjaran::aktif() langsung tidak
 * diubah di STEP 1 ini (untuk menghindari risiko route/relasi error di
 * modul yang sudah berjalan) — tapi secara logika sudah konsisten dengan
 * service ini karena PeriodeAkademik::aktif() hanya mendelegasikan ke
 * method yang sama.
 */
class PeriodeAkademik
{
    /** Tahun Ajaran + Semester yang sedang AKTIF, atau null jika belum ada. */
    public static function aktif(): ?TahunAjaran
    {
        return TahunAjaran::aktif();
    }

    /** Apakah ada periode aktif saat ini. */
    public static function ada(): bool
    {
        return static::aktif() !== null;
    }

    /** Label lengkap periode aktif, mis. "Tahun Ajaran 2026/2027 — Semester Ganjil". */
    public static function labelAktif(): string
    {
        $periode = static::aktif();

        return $periode ? $periode->labelPeriode() : 'Belum ada periode aktif';
    }

    /** Apakah periode aktif saat ini sedang terkunci untuk aksi tulis. */
    public static function terkunci(): bool
    {
        $periode = static::aktif();

        return $periode ? $periode->isTerkunci() : false;
    }
}
