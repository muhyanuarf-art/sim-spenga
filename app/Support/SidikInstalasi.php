<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SIDIK SATU PEMASANGAN.
 *
 * =====================================================================
 * GUNANYA
 * =====================================================================
 * Mengikat surat aktivasi ke SATU pemasangan. Surat yang diterbitkan
 * untuk sekolah A tidak berlaku bila berkasnya disalin ke server lain,
 * karena sidiknya tidak lagi cocok.
 *
 * Nilainya diturunkan dari dua hal:
 *   - APP_KEY pemasangan ini, dan
 *   - `instalasi_id`, satu nilai acak yang dibuat sekali lalu disimpan
 *     di database.
 *
 * =====================================================================
 * SEJUJURNYA: BATASNYA
 * =====================================================================
 * Kalau seseorang menyalin SELURUHNYA — berkas aplikasi, database, dan
 * .env sekaligus — sidiknya ikut tersalin dan tetap cocok. Itu tidak
 * bisa dicegah dari sisi pelanggan, oleh mekanisme mana pun.
 *
 * Yang menutupnya ada di sisi server: dua pemasangan dengan sidik yang
 * sama akan menyapa dari dua alamat IP yang berbeda, dan server FF
 * Production dapat melihatnya lalu menolak menerbitkan surat baru.
 * Karena suratnya berumur pendek, penyalinan itu berhenti sendiri dalam
 * hitungan hari — tanpa perlu ada yang datang memeriksa.
 */
class SidikInstalasi
{
    private const KUNCI = 'instalasi_id';

    private static ?string $cache = null;

    public static function nilai(): string
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        return self::$cache = hash('sha256', config('app.key').'|'.self::instalasiId());
    }

    /**
     * Nilai acak milik pemasangan ini. Dibuat sekali saat pertama
     * dibutuhkan lalu disimpan; sesudah itu tidak pernah berubah, meski
     * aplikasi diperbarui atau servernya dipindah folder.
     *
     * Disimpan di tabel `pengaturan_aplikasis` — bukan di .env — supaya
     * ikut terbawa saat database dipulihkan dari backup. Pemulihan yang
     * benar karena itu tidak menuntut aktivasi ulang.
     */
    private static function instalasiId(): string
    {
        $baris = DB::table('pengaturan_aplikasis')->where('kunci', self::KUNCI)->first();

        if ($baris && $baris->nilai !== '') {
            return $baris->nilai;
        }

        $baru = (string) Str::uuid();

        DB::table('pengaturan_aplikasis')->updateOrInsert(
            ['kunci' => self::KUNCI],
            ['nilai' => $baru, 'created_at' => now(), 'updated_at' => now()],
        );

        return $baru;
    }

    /** Untuk pengujian — memaksa nilainya dihitung ulang. */
    public static function lupakanCache(): void
    {
        self::$cache = null;
    }
}
