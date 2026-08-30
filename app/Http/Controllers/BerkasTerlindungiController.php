<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * MENYAJIKAN BERKAS UNGGAHAN YANG BERSIFAT RAHASIA.
 *
 * =====================================================================
 * MASALAH YANG DIPERBAIKI
 * =====================================================================
 * Semula foto bukti pelanggaran & pembinaan BK dan lampiran surat
 * disimpan di `storage/app/public`, yang disambungkan ke `public/storage`
 * lewat tautan simbolik. Berkas di sana dilayani LANGSUNG oleh peladen web
 * — Laravel tidak pernah ikut campur. Akibatnya:
 *
 *   - tidak ada pemeriksaan login,
 *   - tidak ada pemeriksaan peran,
 *   - tidak ada pemeriksaan lisensi.
 *
 * Siapa pun yang memegang alamatnya bisa membuka foto bukti pelanggaran
 * seorang siswa, dari mana saja, selamanya. Nama berkasnya memang acak 40
 * huruf sehingga tidak bisa ditebak satu per satu, tetapi alamat bocor
 * dengan mudah: riwayat peramban, tangkapan layar yang dibagikan, tautan
 * yang tersalin, atau guru yang sudah pindah tugas tetapi alamatnya masih
 * tersimpan.
 *
 * Untuk catatan kedisiplinan siswa, itu tidak memadai.
 *
 * =====================================================================
 * CARA PERBAIKANNYA
 * =====================================================================
 * Berkas rahasia dipindahkan ke cakram `local` (storage/app/private) yang
 * BERADA DI LUAR jangkauan peladen web, lalu disajikan hanya lewat rute
 * ini — yang berada di dalam grup berautentikasi, sehingga ikut melewati
 * pemeriksaan lisensi dan login seperti halaman lain.
 *
 * Yang TIDAK ikut dipindah: logo sekolah dan ikon aplikasi. Keduanya
 * memang harus bisa dibaca tanpa login — logo tampil di halaman masuk dan
 * favicon diminta peramban sebelum siapa pun sempat login.
 *
 * Nilai kolom `bukti_file` / `path` di database TIDAK berubah sama sekali;
 * yang berganti hanya cakram tempat berkasnya berada.
 */
class BerkasTerlindungiController extends Controller
{
    /**
     * Folder yang boleh dilayani. Daftar putih, bukan daftar hitam:
     * apa pun yang tidak disebut di sini ditolak, sehingga rute ini tidak
     * bisa dipakai membaca berkas lain di dalam storage.
     */
    private const FOLDER_DIIZINKAN = [
        'bk/bukti-pelanggaran',
        'bk/bukti-pembinaan',
        'surat-lampiran',
    ];

    public function tampilkan(Request $request, string $path): StreamedResponse
    {
        // Hanya staf. Portal orang tua tidak pernah menampilkan bukti BK
        // maupun lampiran surat, jadi tidak ada fitur yang hilang.
        abort_unless($request->user(), 403, 'Berkas ini hanya untuk pengguna yang sudah masuk.');

        $path = $this->bersihkan($path);

        abort_unless($this->diizinkan($path), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        // inline supaya foto & PDF terbuka langsung di tab baru, sama
        // seperti perilaku sebelumnya — hanya kini setelah diperiksa.
        return Storage::disk('local')->response(
            $path,
            basename($path),
            [
                // Jangan sampai berkas rahasia tersimpan di cache bersama
                // atau di peramban komputer bersama di ruang guru.
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline'
        );
    }

    /**
     * Buang upaya keluar dari folder yang diizinkan. Meski daftar putih di
     * bawah sudah menutupnya, jalur ini dibersihkan lebih dulu supaya tidak
     * bergantung pada satu lapis saja.
     */
    private function bersihkan(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path) ?? '';

        return ltrim($path, '/');
    }

    private function diizinkan(string $path): bool
    {
        if (str_contains($path, '..')) {
            return false;
        }

        foreach (self::FOLDER_DIIZINKAN as $folder) {
            if (str_starts_with($path, $folder.'/')) {
                return true;
            }
        }

        return false;
    }

    /** Alamat berkas terlindungi — dipakai model lewat accessor-nya. */
    public static function url(?string $path): ?string
    {
        return $path ? route('berkas.lihat', ['path' => $path]) : null;
    }
}
