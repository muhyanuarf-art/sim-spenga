<?php

namespace App\Support;

use RuntimeException;

/**
 * SURAT AKTIVASI BERTANDA TANGAN.
 *
 * =====================================================================
 * KENAPA BENTUKNYA SURAT, BUKAN JAWABAN "BOLEH / TIDAK"
 * =====================================================================
 * Kalau server hanya menjawab "boleh", jawaban itu bisa dipalsukan siapa
 * pun yang mengendalikan jaringan atau berkas hosts di komputer sekolah.
 * Karena itu server tidak menjawab, melainkan MENERBITKAN SURAT yang
 * ditandatangani kunci rahasia FF Production.
 *
 * Kunci rahasianya tidak pernah meninggalkan server. Aplikasi sekolah
 * hanya membawa KUNCI PUBLIK, yang sama sekali tidak berguna untuk
 * membuat surat palsu. Ini perbedaan mendasar dari cara lama, yang
 * menghitung tanda tangan memakai APP_KEY — rahasia yang justru berada
 * di komputer pelanggan, sehingga bisa dihitung sendiri olehnya.
 *
 * =====================================================================
 * KENAPA UMURNYA PENDEK
 * =====================================================================
 * Surat berlaku beberapa hari saja lalu diperbarui otomatis. Dua
 * akibatnya, keduanya disengaja:
 *
 *   - Mencabut lisensi cukup dengan BERHENTI menerbitkan surat baru.
 *     Tidak perlu daftar pencabutan, tidak perlu aplikasi menanyakan
 *     "apakah saya masih sah" — pertanyaan yang jawabannya justru bisa
 *     diblokir oleh yang ingin membajak.
 *   - Aplikasi yang diputus dari internet mati dengan sendirinya setelah
 *     suratnya kedaluwarsa. Memblokir jaringan tidak membantu pembajak;
 *     ia justru mempercepat matinya.
 *
 * =====================================================================
 * BENTUK SURATNYA
 * =====================================================================
 *   <isi-base64url>.<tandatangan-base64url>
 *
 * Isinya JSON:
 *   v            versi format
 *   lisensi      nomor lisensi di sisi FF Production
 *   sekolah      nama pemegang, ditampilkan di halaman Pengaturan
 *   host         alamat yang diizinkan, atau "*" untuk bebas
 *   sidik        sidik instalasi — mengikat surat ke SATU pemasangan
 *   terbit       waktu terbit (unix)
 *   kedaluwarsa  batas berlaku (unix)
 */
class SuratLisensi
{
    public const VERSI = 1;

    public function __construct(
        public readonly array $isi,
        public readonly string $mentah,
    ) {
    }

    /**
     * TERBITKAN — dipanggil di SERVER FF Production, tidak pernah di
     * aplikasi sekolah (yang di sana tidak punya kunci rahasianya).
     */
    public static function terbitkan(array $isi, string $kunciRahasia): string
    {
        $isi['v'] = self::VERSI;

        $muatan = self::sandiUrl(json_encode($isi, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $tanda = sodium_crypto_sign_detached($muatan, $kunciRahasia);

        return $muatan.'.'.self::sandiUrl($tanda);
    }

    /**
     * VERIFIKASI — dipanggil di aplikasi sekolah.
     *
     * Hanya memeriksa keaslian tanda tangan dan bentuk isinya. Kesegaran
     * (belum kedaluwarsa) dan kecocokan host/sidik diperiksa terpisah
     * lewat masihBerlaku() dan cocokUntuk(), supaya pemanggilnya bisa
     * membedakan "surat palsu" dari "surat asli yang sudah lewat waktu" —
     * dua keadaan yang penanganannya berbeda.
     */
    public static function baca(string $surat, string $kunciPublik): self
    {
        $bagian = explode('.', trim($surat));

        if (count($bagian) !== 2) {
            throw new RuntimeException('Bentuk surat lisensi tidak dikenali.');
        }

        [$muatan, $tanda] = $bagian;

        $tandaMentah = self::bacaSandiUrl($tanda);

        if (strlen($tandaMentah) !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw new RuntimeException('Tanda tangan surat lisensi tidak utuh.');
        }

        if (! sodium_crypto_sign_verify_detached($tandaMentah, $muatan, $kunciPublik)) {
            throw new RuntimeException('Tanda tangan surat lisensi tidak sah.');
        }

        $isi = json_decode(self::bacaSandiUrl($muatan), true);

        if (! is_array($isi) || ($isi['v'] ?? null) !== self::VERSI) {
            throw new RuntimeException('Versi surat lisensi tidak didukung.');
        }

        return new self($isi, trim($surat));
    }

    public function masihBerlaku(?int $sekarang = null): bool
    {
        return (int) ($this->isi['kedaluwarsa'] ?? 0) > ($sekarang ?? time());
    }

    public function kedaluwarsaPada(): int
    {
        return (int) ($this->isi['kedaluwarsa'] ?? 0);
    }

    /** Sisa hari sebelum surat ini habis masa berlakunya. */
    public function sisaHari(?int $sekarang = null): int
    {
        return (int) max(0, ceil(($this->kedaluwarsaPada() - ($sekarang ?? time())) / 86400));
    }

    /**
     * Apakah surat ini memang untuk pemasangan DAN alamat ini?
     *
     * Sidik instalasi menutup penyalinan folder ke server lain; host
     * menutup pemakaian di alamat yang tidak didaftarkan. Keduanya
     * diperiksa dengan hash_equals — bukan karena rahasia, tetapi supaya
     * tidak ada jalur pembanding yang berbeda lamanya.
     */
    public function cocokUntuk(string $sidikInstalasi, string $host): bool
    {
        if (! hash_equals((string) ($this->isi['sidik'] ?? ''), $sidikInstalasi)) {
            return false;
        }

        $hostSurat = (string) ($this->isi['host'] ?? '');

        return $hostSurat === '*' || hash_equals($hostSurat, $host);
    }

    public function sekolah(): string
    {
        return (string) ($this->isi['sekolah'] ?? '');
    }

    public function nomorLisensi(): string
    {
        return (string) ($this->isi['lisensi'] ?? '');
    }

    private static function sandiUrl(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function bacaSandiUrl(string $data): string
    {
        $hasil = base64_decode(strtr($data, '-_', '+/'), true);

        return $hasil === false ? '' : $hasil;
    }
}
