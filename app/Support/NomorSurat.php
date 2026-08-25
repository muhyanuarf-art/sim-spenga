<?php

namespace App\Support;

use App\Models\JenisSurat;
use App\Models\Surat;
use Carbon\Carbon;

/**
 * Penomoran surat OTOMATIS — guru/BK tidak perlu mengetik nomor surat
 * sama sekali. Format: {nomor urut 3 digit}/{kode jenis}/{bulan romawi}/{tahun}
 * Contoh: 003/SP/VIII/2026
 *
 * Nomor urut dihitung per Jenis Surat, per tahun terbit surat (kolom
 * `tanggal`, BUKAN tanggal acara/pemanggilan) — jadi tiap jenis surat
 * punya urutannya sendiri dan mulai dari 1 lagi tiap tahun baru,
 * sesuai kebiasaan penomoran surat resmi sekolah.
 */
class NomorSurat
{
    private const BULAN_ROMAWI = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    /**
     * Hitung nomor urut & format nomor surat BERIKUTNYA untuk 1 jenis
     * surat. Dipanggil 2 kali secara wajar: sekali untuk pratinjau di
     * form Buat Surat (bisa berubah kalau ada surat lain masuk duluan),
     * sekali lagi saat benar-benar Simpan (nilai final yang disimpan).
     *
     * @return array{nomor_urut:int, nomor_surat:string}
     */
    public static function berikutnya(JenisSurat $jenisSurat, string $tanggal): array
    {
        $tanggalObj = Carbon::parse($tanggal);
        $tahun = $tanggalObj->year;

        $nomorUrut = (int) Surat::where('jenis_surat_id', $jenisSurat->id)
            ->whereYear('tanggal', $tahun)
            ->max('nomor_urut') + 1;

        $kode = self::kodeJenis($jenisSurat);
        $bulanRomawi = self::BULAN_ROMAWI[(int) $tanggalObj->month];

        $nomorSurat = sprintf('%03d/%s/%s/%d', $nomorUrut, $kode, $bulanRomawi, $tahun);

        return ['nomor_urut' => $nomorUrut, 'nomor_surat' => $nomorSurat];
    }

    /**
     * Sama seperti berikutnya(), tapi untuk PENYIMPANAN FINAL — dibungkus
     * transaksi + row lock (lockForUpdate) supaya 2 surat yang disimpan
     * nyaris bersamaan (jenis surat & tahun yang sama) tidak pernah dapat
     * nomor urut yang sama. Pratinjau di form (berikutnya()) TIDAK pakai
     * lock — cuma perkiraan, wajar kalau meleset kalau ada surat lain
     * masuk duluan sebelum benar-benar Simpan.
     *
     * WAJIB dipanggil di dalam DB::transaction() milik pemanggil.
     *
     * @return array{nomor_urut:int, nomor_surat:string}
     */
    public static function finalisasi(JenisSurat $jenisSurat, string $tanggal): array
    {
        $tanggalObj = Carbon::parse($tanggal);
        $tahun = $tanggalObj->year;

        $nomorUrut = (int) Surat::where('jenis_surat_id', $jenisSurat->id)
            ->whereYear('tanggal', $tahun)
            ->lockForUpdate()
            ->max('nomor_urut') + 1;

        $kode = self::kodeJenis($jenisSurat);
        $bulanRomawi = self::BULAN_ROMAWI[(int) $tanggalObj->month];

        $nomorSurat = sprintf('%03d/%s/%s/%d', $nomorUrut, $kode, $bulanRomawi, $tahun);

        return ['nomor_urut' => $nomorUrut, 'nomor_surat' => $nomorSurat];
    }

    /**
     * Kode jenis dari master Jenis Surat kalau sudah diisi Admin/Kesiswaan,
     * kalau belum diisi otomatis dibentuk dari inisial nama jenis surat
     * (mis. "Surat Panggilan Orang Tua" -> "SP") supaya nomor tetap bisa
     * langsung jalan tanpa harus Admin mengisi dulu.
     */
    public static function kodeJenis(JenisSurat $jenisSurat): string
    {
        if ($jenisSurat->kode_jenis) {
            return strtoupper($jenisSurat->kode_jenis);
        }

        $kata = preg_split('/\s+/', trim($jenisSurat->nama_jenis));
        $inisial = collect($kata)->take(2)->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->implode('');

        return $inisial ?: 'SR';
    }
}
