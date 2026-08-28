<?php

namespace App\Http\Controllers;

use App\Models\PengaturanSekolah;
use App\Support\IkonAplikasi;
use Illuminate\Support\Facades\Storage;

/**
 * Menyajikan dua berkas ikon yang TIDAK bisa sekadar ditaruh sebagai file
 * statis, karena isinya bergantung pada Logo Aplikasi & Nama Sekolah yang
 * bisa diganti sendiri lewat menu Pengaturan Sekolah:
 *
 * 1. /favicon.ico — peramban SELALU memintanya sendiri di akar situs,
 *    bahkan tanpa <link>. Kalau tidak dilayani, log aplikasi terisi 404
 *    dan sebagian peramban menampilkan ikon kosong.
 * 2. /site.webmanifest — dibangkitkan agar nama aplikasi dan ikonnya
 *    otomatis mengikuti sekolah yang memakai, tanpa perlu mengedit berkas.
 *
 * Keduanya terbuka tanpa login: ikon dan manifest memang diminta peramban
 * sebelum pengguna sempat masuk (mis. di halaman login).
 */
class IkonAplikasiController extends Controller
{
    public function favicon()
    {
        $path = IkonAplikasi::FOLDER.'/favicon.ico';

        abort_unless(Storage::disk('public')->exists($path), 404);

        return response(Storage::disk('public')->get($path), 200, [
            'Content-Type' => 'image/x-icon',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function manifest()
    {
        $sekolah = PengaturanSekolah::current();
        $nama = $sekolah->nama_sekolah ?: 'SIM-SPENGA';

        $ikon = [];
        foreach (['web-app-manifest-192x192.png' => 192, 'web-app-manifest-512x512.png' => 512] as $berkas => $ukuran) {
            if ($url = IkonAplikasi::url($berkas)) {
                $ikon[] = [
                    'src' => $url,
                    'sizes' => "{$ukuran}x{$ukuran}",
                    'type' => 'image/png',
                    // 'maskable' supaya Android boleh memangkas tepinya jadi
                    // bentuk ikon khas peranti tanpa memotong lambang sekolah
                    // (ikon dibuat dengan ruang kosong di sekeliling logo).
                    'purpose' => 'maskable',
                ];
            }
        }

        return response()->json([
            'name' => $nama,
            'short_name' => $sekolah->inisialAplikasi(),
            'description' => 'Sistem Informasi Manajemen '.$nama,
            'icons' => $ikon,
            'theme_color' => '#1c68f2',
            'background_color' => '#ffffff',
            'display' => 'standalone',
            'start_url' => url('/'),
            'lang' => 'id',
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }
}
