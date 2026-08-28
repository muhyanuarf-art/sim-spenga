<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * PEMBANGKIT SET IKON APLIKASI dari Logo Aplikasi yang diunggah sekolah.
 *
 * Sebelumnya logo yang diunggah cuma dipasang apa adanya sebagai
 * <link rel="icon">. Itu berfungsi seadanya, tapi bukan yang diharapkan
 * peramban & ponsel: tab browser butuh ikon kecil yang tajam, iPhone
 * butuh apple-touch-icon 180x180, dan Android butuh ikon 192/512 lewat
 * web manifest. Logo aslinya (sering 512px atau lebih) kalau dipaksa
 * mengecil jadi 16px oleh peramban hasilnya buram.
 *
 * Kelas ini membuat SATU SET ikon lengkap — sama seperti paket yang
 * dihasilkan generator favicon pada umumnya — setiap kali sekolah
 * mengunggah logo baru:
 *
 *   favicon.ico                  (16, 32, 48 dalam satu berkas)
 *   favicon-96x96.png
 *   apple-touch-icon.png         (180x180)
 *   web-app-manifest-192x192.png
 *   web-app-manifest-512x512.png
 *   favicon.svg                  (hanya bila yang diunggah memang SVG)
 *
 * Semuanya disimpan di storage/app/public/ikon/ dan diganti utuh setiap
 * kali logo baru diunggah, jadi tidak ada berkas lama yang menumpuk.
 *
 * SVG: GD tidak bisa membaca SVG. Kalau sekolah mengunggah SVG, berkasnya
 * disalin apa adanya sebagai favicon.svg — peramban modern sudah
 * mendukungnya — dan set raster dilewati (lihat perbarui()).
 */
class IkonAplikasi
{
    /** Folder penyimpanan, relatif terhadap disk 'public'. */
    public const FOLDER = 'ikon';

    /** Ukuran yang dijejalkan ke dalam favicon.ico. */
    private const UKURAN_ICO = [16, 32, 48];

    /** Berkas PNG yang dibuat: nama berkas => ukuran sisi (piksel). */
    public const BERKAS_PNG = [
        'favicon-96x96.png' => 96,
        'apple-touch-icon.png' => 180,
        'web-app-manifest-192x192.png' => 192,
        'web-app-manifest-512x512.png' => 512,
    ];

    /**
     * Buat ulang seluruh set ikon dari logo yang tersimpan.
     *
     * @param  string|null  $logoPath  path logo di disk 'public'; null = bersihkan saja
     * @return bool  true kalau set raster berhasil dibuat
     */
    public static function perbarui(?string $logoPath): bool
    {
        self::bersihkan();

        if (! $logoPath || ! Storage::disk('public')->exists($logoPath)) {
            return false;
        }

        $isi = Storage::disk('public')->get($logoPath);

        // SVG tidak bisa dibaca GD — disalin apa adanya. Peramban modern
        // memakai favicon.svg ini, dan itu sudah cukup.
        if (self::sepertinyaSvg($logoPath, $isi)) {
            Storage::disk('public')->put(self::FOLDER.'/favicon.svg', $isi);

            return false;
        }

        $sumber = @imagecreatefromstring($isi);
        if (! $sumber) {
            return false;
        }

        foreach (self::BERKAS_PNG as $nama => $ukuran) {
            $png = self::pngUkuran($sumber, $ukuran);
            Storage::disk('public')->put(self::FOLDER.'/'.$nama, $png);
        }

        $potongan = [];
        foreach (self::UKURAN_ICO as $ukuran) {
            $potongan[$ukuran] = self::pngUkuran($sumber, $ukuran);
        }
        Storage::disk('public')->put(self::FOLDER.'/favicon.ico', self::rakitIco($potongan));

        imagedestroy($sumber);

        return true;
    }

    /** Hapus seluruh berkas ikon yang pernah dibuat. */
    public static function bersihkan(): void
    {
        $disk = Storage::disk('public');

        foreach ($disk->files(self::FOLDER) as $berkas) {
            $disk->delete($berkas);
        }
    }

    /** URL sebuah berkas ikon, atau null kalau belum pernah dibuat. */
    public static function url(string $nama): ?string
    {
        $path = self::FOLDER.'/'.$nama;

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        // Ditambah penanda waktu ubah supaya peramban tidak menampilkan
        // ikon lama dari cache setelah sekolah mengganti logonya.
        return asset('storage/'.$path).'?v='.Storage::disk('public')->lastModified($path);
    }

    /** Apakah set ikon sudah pernah dibuat (minimal ada favicon.ico atau favicon.svg). */
    public static function tersedia(): bool
    {
        return self::url('favicon.ico') !== null || self::url('favicon.svg') !== null;
    }

    // =================================================================
    // internal
    // =================================================================

    private static function sepertinyaSvg(string $path, string $isi): bool
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'svg'
            || str_contains(substr($isi, 0, 200), '<svg');
    }

    /**
     * Kecilkan logo ke kotak $ukuran x $ukuran, TANPA memotong dan tanpa
     * memelarkan: gambarnya diletakkan di tengah dengan latar transparan.
     * Logo sekolah jarang benar-benar persegi, dan memaksa rasio berubah
     * membuat lambangnya terlihat penyok.
     */
    private static function pngUkuran($sumber, int $ukuran): string
    {
        $lebarAsal = imagesx($sumber);
        $tinggiAsal = imagesy($sumber);

        $skala = min($ukuran / $lebarAsal, $ukuran / $tinggiAsal);
        $lebarBaru = max(1, (int) round($lebarAsal * $skala));
        $tinggiBaru = max(1, (int) round($tinggiAsal * $skala));

        $kanvas = imagecreatetruecolor($ukuran, $ukuran);
        imagealphablending($kanvas, false);
        imagesavealpha($kanvas, true);
        imagefill($kanvas, 0, 0, imagecolorallocatealpha($kanvas, 0, 0, 0, 127));
        imagealphablending($kanvas, true);

        imagecopyresampled(
            $kanvas, $sumber,
            (int) (($ukuran - $lebarBaru) / 2), (int) (($ukuran - $tinggiBaru) / 2),
            0, 0,
            $lebarBaru, $tinggiBaru,
            $lebarAsal, $tinggiAsal
        );

        ob_start();
        imagepng($kanvas);
        $png = (string) ob_get_clean();
        imagedestroy($kanvas);

        return $png;
    }

    /**
     * Rakit beberapa PNG menjadi satu berkas .ico.
     *
     * Format ICO: 6 byte kepala, lalu 16 byte keterangan per gambar, lalu
     * data gambarnya. Isinya sengaja PNG (bukan BMP) — didukung semua
     * peramban sejak Windows Vista dan jauh lebih ringkas, sekaligus
     * mempertahankan latar transparan.
     *
     * @param  array<int, string>  $potongan  ukuran => data PNG
     */
    private static function rakitIco(array $potongan): string
    {
        $jumlah = count($potongan);

        // ICONDIR: reserved(0) + type(1 = ikon) + jumlah gambar
        $kepala = pack('vvv', 0, 1, $jumlah);

        $keterangan = '';
        $data = '';
        $offset = 6 + ($jumlah * 16); // kepala + seluruh baris keterangan

        foreach ($potongan as $ukuran => $png) {
            $panjang = strlen($png);

            $keterangan .= pack(
                'CCCCvvVV',
                $ukuran >= 256 ? 0 : $ukuran, // 0 berarti 256
                $ukuran >= 256 ? 0 : $ukuran,
                0,   // jumlah warna palet (0 = bukan palet)
                0,   // reserved
                1,   // color planes
                32,  // bit per piksel
                $panjang,
                $offset
            );

            $data .= $png;
            $offset += $panjang;
        }

        return $kepala.$keterangan.$data;
    }
}
