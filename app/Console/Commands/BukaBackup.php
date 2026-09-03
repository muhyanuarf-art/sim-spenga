<?php

namespace App\Console\Commands;

use App\Support\BrankasBackup;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * MEMBUKA BACKUP TERENKRIPSI.
 *
 * Perintah ini ada bukan sebagai pelengkap, melainkan sebagai syarat:
 * backup yang tidak bisa dibuka bukan backup. Sengaja dipisah dari
 * backup:buat supaya bisa dijalankan di komputer LAIN — dan memang di
 * sanalah backup seharusnya sekali waktu diuji, karena menguji di
 * komputer yang sama tidak membuktikan apa-apa tentang keadaan saat
 * komputer itu justru yang rusak.
 */
class BukaBackup extends Command
{
    protected $signature = 'backup:buka
                            {berkas : Letak berkas .simbak}
                            {--tujuan= : Letak berkas .zip hasilnya}';

    protected $description = 'Membuka berkas backup terenkripsi menjadi .zip';

    public function handle(): int
    {
        $berkas = $this->argument('berkas');

        if (! is_file($berkas)) {
            $this->error("Berkas tidak ditemukan: {$berkas}");

            return self::FAILURE;
        }

        $sandi = (string) config('backup.sandi');

        if (trim($sandi) === '') {
            // Di komputer lain, .env-nya tentu belum berisi kata sandi ini —
            // jadi ditanyakan langsung, tanpa ditampilkan di layar.
            $sandi = (string) $this->secret('Kata sandi backup');
        }

        $tujuan = $this->option('tujuan') ?: preg_replace('/\.simbak$/', '', $berkas).'.zip';

        try {
            BrankasBackup::dekripsiBerkas($berkas, $tujuan, $sandi);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $mb = round(filesize($tujuan) / 1048576, 2);

        $this->info("Berhasil dibuka: {$tujuan} ({$mb} MB)");
        $this->newLine();
        $this->warn('Berkas .zip ini TIDAK terenkripsi lagi — berisi seluruh data siswa');
        $this->warn('beserta APP_KEY. Hapus segera setelah selesai dipakai.');

        return self::SUCCESS;
    }
}
