<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * MEMBUAT SEPASANG KUNCI PENANDATANGAN LISENSI (Ed25519).
 *
 * Dijalankan SEKALI oleh FF Production, di komputer FF Production —
 * bukan di komputer sekolah.
 *
 *   Kunci RAHASIA  → hanya di server ffproduction.com. Inilah yang
 *                    menandatangani surat aktivasi. Bocornya kunci ini
 *                    berarti siapa pun bisa menerbitkan lisensi palsu
 *                    yang tidak bisa dibedakan dari yang asli.
 *   Kunci PUBLIK   → ditanam di setiap aplikasi sekolah. Aman dilihat
 *                    siapa saja; ia hanya bisa MEMERIKSA tanda tangan,
 *                    tidak bisa membuatnya.
 *
 * Kalau kunci rahasia hilang, seluruh sekolah harus dikirimi pembaruan
 * berisi kunci publik yang baru. Simpanlah cadangannya di tempat yang
 * berbeda dari servernya.
 */
class BuatKunciLisensi extends Command
{
    protected $signature = 'lisensi:buat-kunci';

    protected $description = 'Membuat sepasang kunci Ed25519 untuk menandatangani surat lisensi';

    public function handle(): int
    {
        $pasangan = sodium_crypto_sign_keypair();

        $publik = base64_encode(sodium_crypto_sign_publickey($pasangan));
        $rahasia = base64_encode(sodium_crypto_sign_secretkey($pasangan));

        $this->newLine();
        $this->info('Sepasang kunci berhasil dibuat.');
        $this->newLine();

        $this->line('=== DI SERVER ffproduction.com — .env ===');
        $this->line('LISENSI_KUNCI_RAHASIA="'.$rahasia.'"');
        $this->newLine();

        $this->line('=== DI SETIAP APLIKASI SEKOLAH — config/lisensi.php ===');
        $this->line("'kunci_publik' => '".$publik."',");
        $this->newLine();

        $this->warn('Kunci RAHASIA jangan pernah dikirim ke sekolah, ditaruh di repositori,');
        $this->warn('atau ikut dalam berkas backup aplikasi sekolah. Siapa pun yang');
        $this->warn('memegangnya bisa menerbitkan lisensi palsu yang sah menurut aplikasi.');
        $this->newLine();
        $this->line('Simpan cadangannya di tempat TERPISAH dari servernya. Kunci ini hilang');
        $this->line('berarti seluruh sekolah harus dikirimi pembaruan berisi kunci publik baru.');

        return self::SUCCESS;
    }
}
