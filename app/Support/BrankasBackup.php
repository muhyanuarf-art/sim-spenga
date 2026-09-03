<?php

namespace App\Support;

use RuntimeException;

/**
 * ENKRIPSI BERKAS BACKUP.
 *
 * =====================================================================
 * ANCAMAN YANG DILAWAN
 * =====================================================================
 * Bukan peretas yang sudah menguasai server — kalau ia sudah di dalam,
 * enkripsi backup tidak menolong apa pun karena kata sandinya pun ada di
 * sana. Yang dilawan adalah ancaman yang JAUH lebih mungkin terjadi pada
 * sebuah sekolah:
 *
 *   - flashdisk backup tertinggal atau hilang,
 *   - akun Google Drive tempat backup disinkronkan kebobolan,
 *   - hard disk lama dibuang atau dijual tanpa dihapus benar,
 *   - teknisi menyalin folder backup "untuk jaga-jaga".
 *
 * Dalam semua keadaan itu yang berpindah tangan hanyalah BERKASNYA,
 * sementara kata sandinya tidak ikut — sehingga isinya tetap tertutup.
 *
 * =====================================================================
 * BENTUK BERKASNYA
 * =====================================================================
 *   [10] "SIMSPENGA1"  penanda + versi format
 *   [16] garam         acak, untuk menurunkan kunci
 *   [16] iv            acak
 *   [32] hmac          SHA-256 atas garam|iv|sandi-terenkripsi
 *   [..] sandi-terenkripsi
 *
 * Dua kunci diturunkan terpisah dari kata sandi (PBKDF2, 210.000 putaran):
 * satu untuk mengenkripsi, satu untuk HMAC. Memakai satu kunci untuk dua
 * keperluan adalah kekeliruan klasik yang melemahkan keduanya.
 *
 * HMAC dihitung ATAS HASIL ENKRIPSI (encrypt-then-MAC) dan diperiksa
 * SEBELUM dekripsi. Dengan begitu berkas yang rusak atau diubah orang
 * ditolak lebih dulu, bukan setelah isinya terlanjur diproses.
 */
class BrankasBackup
{
    private const PENANDA = 'SIMSPENGA1';
    private const PUTARAN = 210000;
    private const SANDI_ALGO = 'aes-256-cbc';

    /**
     * Batas ukuran yang diproses sekaligus di memori. Data sekolah ini
     * sekarang ~5 MB, jadi jauh di bawahnya. Kalau suatu saat terlampaui,
     * lebih baik berhenti dengan pesan jelas daripada kehabisan memori di
     * tengah jalan dan meninggalkan berkas backup yang separuh jadi.
     */
    private const BATAS_BYTE = 256 * 1024 * 1024;

    public static function enkripsiBerkas(string $sumber, string $tujuan, string $sandi): void
    {
        self::pastikanSandiLayak($sandi);
        self::pastikanMuat($sumber);

        $isi = file_get_contents($sumber);
        if ($isi === false) {
            throw new RuntimeException("Tidak bisa membaca {$sumber}.");
        }

        $garam = random_bytes(16);
        $iv = random_bytes(16);
        [$kunciSandi, $kunciMac] = self::turunkanKunci($sandi, $garam);

        $terenkripsi = openssl_encrypt($isi, self::SANDI_ALGO, $kunciSandi, OPENSSL_RAW_DATA, $iv);
        if ($terenkripsi === false) {
            throw new RuntimeException('Enkripsi gagal.');
        }

        $hmac = hash_hmac('sha256', $garam.$iv.$terenkripsi, $kunciMac, true);

        file_put_contents($tujuan, self::PENANDA.$garam.$iv.$hmac.$terenkripsi);
    }

    public static function dekripsiBerkas(string $sumber, string $tujuan, string $sandi): void
    {
        self::pastikanMuat($sumber);

        $isi = file_get_contents($sumber);
        if ($isi === false || strlen($isi) < 74) {
            throw new RuntimeException('Berkas backup tidak lengkap atau rusak.');
        }

        if (substr($isi, 0, 10) !== self::PENANDA) {
            throw new RuntimeException('Ini bukan berkas backup SIM-SPENGA.');
        }

        $garam = substr($isi, 10, 16);
        $iv = substr($isi, 26, 16);
        $hmac = substr($isi, 42, 32);
        $terenkripsi = substr($isi, 74);

        [$kunciSandi, $kunciMac] = self::turunkanKunci($sandi, $garam);

        // Diperiksa SEBELUM didekripsi, dan dengan hash_equals supaya lama
        // pembandingan tidak membocorkan seberapa dekat tebakan seseorang.
        if (! hash_equals(hash_hmac('sha256', $garam.$iv.$terenkripsi, $kunciMac, true), $hmac)) {
            throw new RuntimeException(
                'Kata sandi backup salah, atau berkasnya sudah rusak/diubah. '
                .'Isinya tidak dibuka sama sekali.'
            );
        }

        $asli = openssl_decrypt($terenkripsi, self::SANDI_ALGO, $kunciSandi, OPENSSL_RAW_DATA, $iv);
        if ($asli === false) {
            throw new RuntimeException('Dekripsi gagal meski tanda tangannya cocok.');
        }

        file_put_contents($tujuan, $asli);
    }

    /** @return array{0: string, 1: string} kunci enkripsi & kunci HMAC */
    private static function turunkanKunci(string $sandi, string $garam): array
    {
        $bahan = hash_pbkdf2('sha256', $sandi, $garam, self::PUTARAN, 64, true);

        return [substr($bahan, 0, 32), substr($bahan, 32, 32)];
    }

    private static function pastikanSandiLayak(string $sandi): void
    {
        if (strlen(trim($sandi)) < 12) {
            throw new RuntimeException(
                'Kata sandi backup terlalu pendek (minimal 12 karakter). '
                .'Isi BACKUP_SANDI di .env dengan kalimat panjang yang mudah Anda ingat '
                .'tetapi sulit ditebak.'
            );
        }
    }

    private static function pastikanMuat(string $berkas): void
    {
        $ukuran = filesize($berkas);

        if ($ukuran !== false && $ukuran > self::BATAS_BYTE) {
            throw new RuntimeException(
                'Berkas melebihi 256 MB sehingga tidak diproses sekaligus. '
                .'Hubungi pengembang untuk mengubah cara backup menjadi bertahap.'
            );
        }
    }
}
