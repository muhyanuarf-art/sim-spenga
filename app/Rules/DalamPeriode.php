<?php

namespace App\Rules;

use App\Models\TahunAjaran;
use App\Support\PeriodeAkademik;
use App\Support\RentangPeriode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Aturan validasi: TANGGAL HARUS BERADA DI DALAM PERIODE tempat datanya
 * akan dicatat (Tahun Ajaran + Semester).
 *
 * =====================================================================
 * MASALAH YANG DICEGAH
 * =====================================================================
 * Hampir seluruh pencatatan di aplikasi ini menyimpan
 * `tahun_ajaran_id = TahunAjaran::aktif()->id` — apa pun tanggal yang
 * diketik operator. Tanggalnya sendiri sebelumnya hanya divalidasi
 * ['required','date'], tanpa batas atas maupun bawah.
 *
 * Akibatnya sebuah baris bisa tercatat di Semester Ganjil 2026/2027
 * tetapi bertanggal Maret 2027 (yang jelas Semester Genap), atau salah
 * ketik tahun menjadi 2025. Data seperti itu TERSIMPAN tanpa keluhan,
 * lalu HILANG dari semua laporan — karena setiap laporan menyaring
 * berdasarkan rentang tanggal periodenya (lihat Laporan Akhir Semester,
 * Rekap Absensi Bulanan, Laporan Bulanan BK). Kesalahan ketik satu digit
 * berujung data yang seolah lenyap, dan biasanya baru ketahuan berbulan
 * kemudian saat angka laporan tidak cocok.
 *
 * =====================================================================
 * SIKAP YANG DIAMBIL
 * =====================================================================
 * Aturan ini sengaja TIDAK galak:
 *
 * - Rentangnya memakai App\Support\RentangPeriode yang sama dengan yang
 *   dipakai laporan, jadi "boleh disimpan" dan "muncul di laporan" selalu
 *   sejalan.
 * - Kalau rentang periode tidak bisa ditentukan, tanggal DILOLOSKAN.
 *   Lebih baik tidak memblokir daripada memblokir berdasarkan tebakan.
 * - Pesannya menyebutkan rentang yang berlaku, supaya operator langsung
 *   tahu harus mengisi apa — bukan sekadar "tanggal tidak valid".
 */
class DalamPeriode implements ValidationRule
{
    public function __construct(
        private ?TahunAjaran $periode = null,
        private string $sebutan = 'periode',
    ) {
        $this->periode ??= PeriodeAkademik::aktif();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->periode || $value === null || $value === '') {
            return;
        }

        if (RentangPeriode::memuat($this->periode, (string) $value)) {
            return;
        }

        $label = RentangPeriode::label($this->periode);

        $fail(
            'Tanggal berada di luar '.$this->periode->labelPeriode().'.'
            .($label ? ' Isi tanggal antara '.$label.'.' : '')
            .' Kalau tanggalnya memang benar, berarti '.$this->sebutan
            .' ini milik periode lain — mintalah Kurikulum/Admin mengaktifkan periode yang sesuai.'
        );
    }
}
