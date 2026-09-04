<?php

namespace App\Support;

use App\Models\LisensiAplikasi;
use Illuminate\Support\Facades\Schema;

/**
 * PEMERIKSAAN LISENSI APLIKASI.
 *
 * =====================================================================
 * CARA KERJA
 * =====================================================================
 * Aplikasi baru bisa dipakai setelah nomor seri yang benar dimasukkan
 * sekali di halaman /aktivasi. Yang disimpan di database bukan nomor
 * serinya, melainkan sidiknya — jadi membaca database pun tidak
 * memberitahu nomor serinya.
 *
 * Nomor seri yang sah dikenali dengan membandingkan sidik SHA-256-nya
 * terhadap nilai di config/lisensi.php. Perbandingannya memakai
 * hash_equals() supaya lama pembandingan tidak membocorkan seberapa
 * dekat tebakan seseorang (timing attack).
 *
 * Aktivasi diikat ke APP_KEY instalasi ini lewat `tanda_tangan`, dan —
 * bila config lisensi.terikat_host aktif — juga ke alamat servernya.
 * Menyalin aplikasi beserta databasenya ke server lain karena itu tidak
 * ikut aktif: di sana APP_KEY & alamatnya berbeda.
 *
 * =====================================================================
 * SEJUJURNYA: INI PENGHALANG, BUKAN GEMBOK
 * =====================================================================
 * Aplikasi ini berjalan di server sekolah, dan seluruh kodenya ada di
 * sana. Siapa pun yang punya akses berkas di server itu secara teknis
 * bisa melepas pemeriksaan ini — begitu sifat semua perangkat lunak yang
 * dipasang di tempat pemakainya, bukan kelemahan khusus aplikasi ini.
 *
 * Yang dijamin mekanisme ini: aplikasi TIDAK BISA dipakai hanya dengan
 * menyalin foldernya, dan setiap pemasangan menuntut nomor seri yang
 * hanya dipegang pemiliknya.
 */
class Lisensi
{
    /** Cache per-request supaya tidak query berulang dalam satu halaman. */
    private static ?bool $cache = null;

    /** Sidik sebuah nomor seri — dipakai saat memeriksa maupun menerbitkan. */
    public static function sidik(string $nomorSeri): string
    {
        return hash('sha256', 'sim-spenga|'.self::normalkan($nomorSeri));
    }

    /** Buang spasi & tanda hubung, samakan jadi huruf besar. */
    public static function normalkan(string $nomorSeri): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $nomorSeri) ?? '');
    }

    /** Apakah nomor seri yang diketik cocok dengan yang sah? */
    public static function cocok(string $nomorSeri): bool
    {
        $harusnya = (string) config('lisensi.hash');

        if ($harusnya === '') {
            return false;
        }

        return hash_equals($harusnya, self::sidik($nomorSeri));
    }

    /** Alamat server saat ini — dipakai mengikat aktivasi. */
    public static function host(): string
    {
        if (! config('lisensi.terikat_host', true)) {
            return 'bebas';
        }

        $host = strtolower((string) (request()?->getHost() ?: (gethostname() ?: 'lokal')));

        // "www." diabaikan supaya sekolah tidak diminta aktivasi ulang hanya
        // karena membuka alamat dengan atau tanpa awalan itu.
        return preg_replace('/^www\./', '', $host);
    }

    /**
     * Tanda tangan aktivasi: mengikat sidik kunci + alamat server dengan
     * APP_KEY instalasi ini.
     */
    public static function tandaTangan(string $kunciHash, string $host): string
    {
        return hash_hmac('sha256', $kunciHash.'|'.$host, (string) config('app.key'));
    }

    /** Apakah aplikasi ini sudah diaktifkan & aktivasinya masih sah? */
    public static function aktif(): bool
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        if (config('lisensi.mode') === 'server') {
            return self::$cache = self::aktifMenurutServer();
        }

        // Sebelum migrasi dijalankan, tabelnya belum ada — jangan sampai
        // pemeriksaan ini justru membuat aplikasi tidak bisa dipasang.
        if (! Schema::hasTable('lisensi_aplikasis')) {
            return self::$cache = false;
        }

        $baris = LisensiAplikasi::query()->latest('id')->first();

        if (! $baris || ! $baris->diaktifkan_at) {
            return self::$cache = false;
        }

        // Kunci yang dipakai saat aktivasi harus masih yang berlaku
        // sekarang (mis. lisensi diterbitkan ulang -> wajib aktivasi lagi).
        if (! hash_equals((string) config('lisensi.hash'), (string) $baris->kunci_hash)) {
            return self::$cache = false;
        }

        $host = self::host();

        if (config('lisensi.terikat_host', true) && ! hash_equals((string) $baris->host, $host)) {
            return self::$cache = false;
        }

        return self::$cache = hash_equals(
            self::tandaTangan((string) $baris->kunci_hash, (string) $baris->host),
            (string) $baris->tanda_tangan
        );
    }

    /**
     * MODE SERVER — keabsahan ditentukan surat dari ffproduction.com.
     *
     * Yang diperiksa hanya surat yang SUDAH TERSIMPAN; tidak ada
     * permintaan jaringan di sini. Menyapa server adalah pekerjaan
     * terpisah yang dijalankan berkala (lihat perintah lisensi:sapa),
     * supaya membuka satu halaman tidak pernah menunggu jaringan.
     *
     * Tiga syarat, ketiganya harus terpenuhi:
     *   1. Tanda tangannya sah menurut kunci publik — dijamin oleh
     *      suratTersimpan(), yang mengembalikan null bila tidak.
     *   2. Belum kedaluwarsa.
     *   3. Memang untuk pemasangan DAN alamat ini.
     *
     * Syarat ketiga yang membuat penyalinan hosting tidak berguna: surat
     * yang ikut tersalin tidak cocok dengan sidik instalasi di tempat
     * barunya.
     */
    private static function aktifMenurutServer(): bool
    {
        if (! Schema::hasTable('pengaturan_aplikasis')) {
            return false;
        }

        $surat = LisensiServer::suratTersimpan();

        if (! $surat || ! $surat->masihBerlaku()) {
            return false;
        }

        return $surat->cocokUntuk(
            SidikInstalasi::nilai(),
            request()?->getHost() ?: (gethostname() ?: 'lokal'),
        );
    }

    /** Simpan aktivasi. Mengembalikan false bila nomor serinya salah. */
    public static function aktifkan(string $nomorSeri, ?string $oleh = null): bool
    {
        if (! self::cocok($nomorSeri)) {
            return false;
        }

        $kunciHash = self::sidik($nomorSeri);
        $host = self::host();

        LisensiAplikasi::query()->delete();
        LisensiAplikasi::create([
            'kunci_hash' => $kunciHash,
            'host' => $host,
            'tanda_tangan' => self::tandaTangan($kunciHash, $host),
            'diaktifkan_at' => now(),
            'diaktifkan_oleh' => $oleh,
        ]);

        self::lupakanCache();

        return true;
    }

    /** Baris aktivasi terakhir — untuk ditampilkan di Pengaturan. */
    public static function catatan(): ?LisensiAplikasi
    {
        if (! Schema::hasTable('lisensi_aplikasis')) {
            return null;
        }

        return LisensiAplikasi::query()->latest('id')->first();
    }

    public static function lupakanCache(): void
    {
        self::$cache = null;
    }
}
