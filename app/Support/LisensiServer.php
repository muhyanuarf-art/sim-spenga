<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * HUBUNGAN DENGAN SERVER LISENSI FF PRODUCTION.
 *
 * =====================================================================
 * CARA KERJANYA
 * =====================================================================
 * Aplikasi menyapa ffproduction.com secara berkala memakai kredensial
 * yang ditempelkan FF Production ke .env saat memasang — bukan sesuatu
 * yang pernah diketik orang sekolah. Server menjawab dengan SURAT
 * AKTIVASI bertanda tangan yang berlaku singkat, lalu surat itu disimpan
 * di database.
 *
 * Selama surat yang tersimpan masih berlaku, aplikasi berjalan TANPA
 * menyentuh internet sama sekali. Jadi gangguan jaringan sesaat — atau
 * gangguan pada server FF Production sendiri — tidak terasa oleh
 * siapa pun di sekolah.
 *
 * =====================================================================
 * KENAPA SURATNYA DISIMPAN, BUKAN DITANYA TIAP KALI
 * =====================================================================
 * Menanyakan ke server pada setiap permintaan halaman berarti
 * menggantungkan seluruh sekolah pelanggan pada kesempurnaan
 * ffproduction.com: server tersendat dua jam = semua sekolah berhenti
 * bekerja serentak. Menyimpan surat berumur 24 jam membuat gangguan
 * singkat tak terasa, sementara langganan yang habis tetap menutup
 * aplikasi dalam sehari.
 */
class LisensiServer
{
    private const KUNCI_SURAT = 'surat_lisensi';
    private const KUNCI_DISAPA = 'lisensi_disapa_at';
    private const KUNCI_GALAT = 'lisensi_galat_terakhir';

    /**
     * Sapa server dan simpan suratnya.
     *
     * Mengembalikan null bila berhasil, atau kalimat alasan bila gagal.
     * Kegagalan JARINGAN dan penolakan SERVER sengaja dibedakan oleh
     * pemanggilnya: yang pertama wajar terjadi dan tidak perlu
     * mengganggu siapa pun, yang kedua berarti ada yang harus diurus.
     */
    public static function sapa(): ?string
    {
        $kode = (string) config('lisensi.kode');
        $token = (string) config('lisensi.token');

        if ($kode === '' || $token === '') {
            return 'LISENSI_KODE / LISENSI_TOKEN belum diisi di .env pemasangan ini.';
        }

        try {
            $jawab = Http::timeout(20)
                ->acceptJson()
                ->post(rtrim((string) config('lisensi.server'), '/').'/api/lisensi/sapa', [
                    'kode' => $kode,
                    'token' => $token,
                    'sidik' => SidikInstalasi::nilai(),
                    'host' => request()?->getHost() ?: (gethostname() ?: 'lokal'),
                    'versi' => (string) config('app.version', '1.0'),
                ]);
        } catch (ConnectionException $e) {
            self::simpan(self::KUNCI_GALAT, 'jaringan: '.$e->getMessage());

            // Bukan penolakan — surat yang tersimpan tetap berlaku sampai
            // waktunya habis, jadi tidak ada yang perlu dikabarkan.
            return 'Tidak dapat menghubungi server lisensi.';
        }

        $isi = $jawab->json() ?? [];

        if (! $jawab->successful() || ($isi['ok'] ?? false) !== true) {
            $alasan = (string) ($isi['pesan'] ?? 'Server lisensi menolak permintaan.');

            self::simpan(self::KUNCI_GALAT, $alasan);
            Log::warning('Sapaan lisensi ditolak.', ['alasan' => $alasan]);

            return $alasan;
        }

        // Surat diperiksa DULU sebelum disimpan. Menyimpan surat yang
        // ternyata tidak sah hanya akan membuat aplikasi mengunci diri
        // dengan alasan yang membingungkan.
        try {
            $surat = SuratLisensi::baca((string) $isi['surat'], self::kunciPublik());
        } catch (RuntimeException $e) {
            self::simpan(self::KUNCI_GALAT, 'surat tidak sah: '.$e->getMessage());

            return 'Surat lisensi dari server tidak sah. Hubungi FF Production.';
        }

        self::simpan(self::KUNCI_SURAT, $surat->mentah);
        self::simpan(self::KUNCI_DISAPA, (string) now()->timestamp);
        self::simpan(self::KUNCI_GALAT, '');

        return null;
    }

    /** Surat tersimpan yang masih sah tanda tangannya, atau null. */
    public static function suratTersimpan(): ?SuratLisensi
    {
        $mentah = self::baca(self::KUNCI_SURAT);

        if ($mentah === null || $mentah === '') {
            return null;
        }

        try {
            return SuratLisensi::baca($mentah, self::kunciPublik());
        } catch (RuntimeException) {
            // Surat rusak atau kunci publiknya berganti — diperlakukan
            // seperti belum pernah ada.
            return null;
        }
    }

    public static function galatTerakhir(): ?string
    {
        $g = self::baca(self::KUNCI_GALAT);

        return $g === '' ? null : $g;
    }

    public static function disapaTerakhir(): ?int
    {
        $t = self::baca(self::KUNCI_DISAPA);

        return $t === null || $t === '' ? null : (int) $t;
    }

    /** Sudah waktunya menyapa lagi? */
    public static function waktunyaMenyapa(): bool
    {
        $terakhir = self::disapaTerakhir();

        if ($terakhir === null) {
            return true;
        }

        return (time() - $terakhir) >= (int) config('lisensi.jarak_sapa_jam', 6) * 3600;
    }

    private static function kunciPublik(): string
    {
        $kunci = base64_decode((string) config('lisensi.kunci_publik'), true);

        if ($kunci === false || strlen($kunci) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new RuntimeException('LISENSI_KUNCI_PUBLIK belum diisi atau tidak sah.');
        }

        return $kunci;
    }

    private static function simpan(string $kunci, string $nilai): void
    {
        DB::table('pengaturan_aplikasis')->updateOrInsert(
            ['kunci' => $kunci],
            ['nilai' => $nilai, 'updated_at' => now(), 'created_at' => now()],
        );
    }

    private static function baca(string $kunci): ?string
    {
        return DB::table('pengaturan_aplikasis')->where('kunci', $kunci)->value('nilai');
    }
}
