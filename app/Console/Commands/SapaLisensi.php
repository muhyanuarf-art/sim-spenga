<?php

namespace App\Console\Commands;

use App\Support\LisensiServer;
use Illuminate\Console\Command;

/**
 * MENYAPA SERVER LISENSI FF PRODUCTION.
 *
 * Dijalankan cron beberapa kali sehari. Tugasnya hanya satu: mengambil
 * surat aktivasi yang baru lalu menyimpannya. Pemeriksaan keabsahan saat
 * halaman dibuka membaca surat tersimpan itu, tanpa menyentuh jaringan
 * sama sekali — sehingga membuka halaman tidak pernah menunggu server.
 *
 * Cron di cPanel Domainesia — setiap 30 menit:
 *   [*]/30 [*] [*] [*] [*]  cd /home/USER/aplikasi && php artisan schedule:run
 *   (ganti [*] dengan tanda bintang; ditulis begini agar tidak menutup
 *    blok komentar ini)
 *
 * Perintah ini sendiri hemat: ia berhenti lebih awal bila jarak sapa
 * belum tercapai, jadi aman dijadwalkan sesering apa pun.
 */
class SapaLisensi extends Command
{
    protected $signature = 'lisensi:sapa {--paksa : Sapa sekarang walau belum waktunya}';

    protected $description = 'Memperbarui surat lisensi dari server FF Production';

    public function handle(): int
    {
        if (config('lisensi.mode') !== 'server') {
            $this->line('Mode lisensi bukan "server" — tidak ada yang perlu disapa.');

            return self::SUCCESS;
        }

        if (! $this->option('paksa') && ! LisensiServer::waktunyaMenyapa()) {
            $this->line('Belum waktunya menyapa.');

            return self::SUCCESS;
        }

        $galat = LisensiServer::sapa();

        if ($galat === null) {
            $surat = LisensiServer::suratTersimpan();

            $this->info('Surat lisensi diperbarui. Berlaku '.($surat?->sisaHari() ?? 0).' hari lagi.');

            return self::SUCCESS;
        }

        // Sengaja TIDAK mengembalikan kode gagal: kegagalan menyapa itu
        // wajar (internet sekolah putus) dan surat lama masih berlaku.
        // Mengembalikan gagal hanya akan membuat cron mengirimi Admin
        // surel setiap kali jaringan tersendat.
        $this->warn('Gagal menyapa: '.$galat);
        $this->line('Surat yang tersimpan tetap dipakai sampai masa berlakunya habis.');

        return self::SUCCESS;
    }
}
