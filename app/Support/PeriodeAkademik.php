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
    /**
     * Periode yang sedang DILIHAT pengguna — default & fallback-nya periode
     * yang benar-benar berjalan.
     *
     * (2026-08-28) Sejak ada pemilih periode di kepala halaman, seluruh
     * PEMBACAAN memakai periode pilihan supaya halaman laporan bisa
     * menampilkan semester lampau apa adanya — termasuk peran pengguna
     * pada semester itu. Lihat App\Support\KonteksPeriode.
     *
     * PENCATATAN BARU tetap memakai periode yang benar-benar berjalan
     * (aktifSebenarnya() di bawah), dan menyimpan saat sedang menengok
     * periode lampau sudah diblokir middleware 'periode-aktif'.
     */
    public static function aktif(): ?TahunAjaran
    {
        return KonteksPeriode::pilihan();
    }

    /** Periode yang benar-benar BERJALAN — dipakai saat menyimpan & mengunci. */
    public static function aktifSebenarnya(): ?TahunAjaran
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

    /**
     * Apakah data TIDAK boleh ditulis sekarang.
     *
     * Memakai periode yang benar-benar berjalan, bukan periode pilihan:
     * pertanyaannya "boleh menyimpan atau tidak", dan penyimpanan selalu
     * masuk ke periode berjalan. Kalau yang ditanyakan justru "kenapa
     * halaman ini baca-saja", pakai KonteksPeriode::alasanBacaSaja() yang
     * juga memperhitungkan mode menengok periode lampau.
     */
    public static function terkunci(): bool
    {
        $periode = static::aktifSebenarnya();

        return $periode ? $periode->isTerkunci() : false;
    }

    /**
     * STEP 2 Bagian 3 & 8 — Satu pintu untuk memblokir CREATE/UPDATE/DELETE
     * pada data transaksi yang PERIODE-NYA SENDIRI sudah terkunci.
     *
     * Ini BEDA dari middleware 'periode-aktif' (EnsurePeriodeTidakTerkunci):
     * middleware itu hanya mengecek apakah periode yang SEDANG AKTIF
     * terkunci — cocok untuk aksi CREATE baru yang selalu masuk ke periode
     * aktif (mis. tambah mapping guru mengajar, tambah jadwal). Tapi untuk
     * UPDATE/DELETE/aksi status pada baris yang SUDAH ADA, baris itu bisa
     * saja bukan milik periode yang sedang aktif — jadi pengecekannya
     * harus berdasarkan periode milik baris itu sendiri (lewat relasi),
     * bukan periode aktif secara global. Panggil ini di awal method
     * update()/destroy()/dsb SEBELUM melakukan perubahan apa pun.
     *
     * abort(423 Locked) supaya bisa ditangkap resources/views/errors/423.blade.php
     * (pesan ramah, bukan 403 mentah — Bagian 12).
     */
    public static function pastikanTidakTerkunci(?TahunAjaran $periode): void
    {
        if ($periode && $periode->isTerkunci()) {
            abort(423, "Periode {$periode->labelPeriode()} sudah ditutup dan terkunci. Data pada periode ini tidak dapat diubah. Hubungi Admin untuk membuka kunci jika benar-benar diperlukan.");
        }
    }
}
