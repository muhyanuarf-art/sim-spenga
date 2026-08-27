<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Peringkat kelas berdasarkan rata-rata nilai akhir.
 *
 * Dipakai di DUA halaman — Rekap Nilai Rapor Kelas (NilaiWaliKelasController)
 * dan Laporan Akhir Semester (LaporanSemesterController). Diletakkan di sini
 * supaya keduanya tidak mungkin menghasilkan peringkat yang berbeda untuk
 * kelas & periode yang sama — hal yang langsung terlihat janggal kalau
 * kedua lembar itu dibawa bersamaan ke rapat penerimaan rapor.
 *
 * ATURANNYA:
 * - Nilai rata-rata yang SAMA mendapat peringkat yang SAMA (peringkat
 *   kembar), dan peringkat berikutnya melompat sesuai banyaknya yang
 *   kembar — sama seperti cara sekolah menuliskan peringkat di rapor
 *   (mis. dua siswa peringkat 3, berikutnya langsung peringkat 5).
 * - Siswa yang BELUM punya nilai sama sekali tidak diberi peringkat
 *   (null), bukan ditaruh di urutan terakhir — belum dinilai bukan
 *   berarti nilainya paling rendah.
 */
class PeringkatKelas
{
    /**
     * @param  Collection  $baris  tiap item minimal berisi ['siswa' => Siswa, 'rata' => float|null]
     * @return array<int, int>  siswa_id => peringkat
     */
    public static function dariRataRata(Collection $baris): array
    {
        $urut = $baris
            ->filter(fn ($b) => ($b['rata'] ?? null) !== null)
            ->sortByDesc('rata')
            ->values();

        $peringkat = [];
        $nomor = 0;
        $sebelumnya = null;

        foreach ($urut as $index => $b) {
            if ($sebelumnya === null || $b['rata'] < $sebelumnya) {
                $nomor = $index + 1;
                $sebelumnya = $b['rata'];
            }
            $peringkat[$b['siswa']->id] = $nomor;
        }

        return $peringkat;
    }

    /** Versi praktis: kembalikan koleksi yang sama, ditambah kunci 'peringkat'. */
    public static function bubuhkan(Collection $baris): Collection
    {
        $peringkat = self::dariRataRata($baris);

        return $baris->map(function ($b) use ($peringkat) {
            $b['peringkat'] = $peringkat[$b['siswa']->id] ?? null;

            return $b;
        });
    }
}
