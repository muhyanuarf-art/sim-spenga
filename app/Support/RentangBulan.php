<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * PERBAIKAN PERFORMA (permintaan admin — "loading apapun harus sangat
 * cepat") — dipakai untuk menggantikan pola whereMonth()->whereYear()
 * yang ternyata dipakai di 11 FILE BERBEDA di seluruh aplikasi (BK,
 * Rekapitulasi, Laporan Guru, Wali Kelas, Portal Orang Tua, Status
 * WhatsApp Ortu, dst).
 *
 * whereMonth('tanggal', $bulan) & whereYear('tanggal', $tahun) MEMBUNGKUS
 * kolom tanggal dengan fungsi (MONTH(tanggal), YEAR(tanggal)) — ini
 * mencegah MySQL memakai index APA PUN pada kolom itu, jadi setiap
 * halaman yang pakai filter bulan/tahun akan SCAN SELURUH TABEL, makin
 * lambat seiring data bertambah dari bulan ke bulan.
 *
 * Gunakan RentangBulan::dari($tahun, $bulan) lalu whereBetween(), yang
 * hasilnya SAMA PERSIS tapi bisa memakai index.
 */
class RentangBulan
{
    /**
     * @return array{0: Carbon, 1: Carbon} [$awalBulan, $akhirBulan] — rentang 1 bulan penuh (00:00:00 tanggal 1 s.d 23:59:59 tanggal terakhir).
     */
    public static function dari(int $tahun, int $bulan): array
    {
        $awal = Carbon::create($tahun, $bulan, 1)->startOfDay();

        return [$awal, $awal->copy()->endOfMonth()->endOfDay()];
    }
}
