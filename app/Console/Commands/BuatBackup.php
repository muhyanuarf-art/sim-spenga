<?php

namespace App\Console\Commands;

use App\Support\BrankasBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * MEMBUAT BACKUP TERENKRIPSI.
 *
 * Menghasilkan SATU berkas berisi tiga hal yang harus selalu berpasangan:
 *
 *   1. Dump database   — seluruh nilai, absensi, BK, prestasi.
 *   2. storage/app     — sertifikat prestasi, bukti BK, lampiran surat,
 *                        logo sekolah. Sering terlupa, padahal database
 *                        saja tidak cukup: barisnya ada, berkasnya hilang.
 *   3. APP_KEY         — tanpa ini, token WhatsApp dan aktivasi lisensi
 *                        di dalam dump menjadi sampah permanen yang tidak
 *                        bisa didekripsi siapa pun, termasuk Anda.
 *
 * Ketiganya digabung lalu DIENKRIPSI (lihat App\Support\BrankasBackup).
 * Backup tanpa enkripsi tidak pernah dibuat perintah ini — berkas ini
 * setara kunci induk sekolah, bukan arsip biasa.
 *
 * Dipakai:  php artisan backup:buat
 * Membuka:  php artisan backup:buka <berkas>
 */
class BuatBackup extends Command
{
    protected $signature = 'backup:buat {--tujuan= : Folder tujuan, menimpa setelan config/backup.php}';

    protected $description = 'Membuat backup terenkripsi: database + berkas unggahan + APP_KEY';

    public function handle(): int
    {
        $sandi = (string) config('backup.sandi');

        if (trim($sandi) === '') {
            $this->error('BACKUP_SANDI belum diisi di .env.');
            $this->line('');
            $this->line('Backup berisi seluruh data siswa beserta APP_KEY, jadi tidak pernah');
            $this->line('dibuat tanpa enkripsi. Isi BACKUP_SANDI dengan kalimat panjang, lalu');
            $this->line('SIMPAN KALIMAT ITU DI LUAR KOMPUTER INI — tanpa kata sandinya, backup');
            $this->line('tidak bisa dibuka oleh siapa pun, termasuk Anda sendiri.');

            return self::FAILURE;
        }

        $tujuan = (string) ($this->option('tujuan') ?: config('backup.tujuan'));
        File::ensureDirectoryExists($tujuan);

        $kerja = storage_path('app/private/backup-sementara-'.uniqid());
        File::ensureDirectoryExists($kerja);

        try {
            $this->info('1/4 Menyalin database…');
            $this->dumpDatabase($kerja.'/database.sql');

            $this->info('2/4 Mengumpulkan berkas unggahan & APP_KEY…');
            file_put_contents($kerja.'/RAHASIA.txt', $this->isiRahasia());
            file_put_contents($kerja.'/BACA-SAYA.txt', $this->isiPetunjuk());

            $this->info('3/4 Memampatkan…');
            $zip = $kerja.'/isi.zip';
            $this->buatZip($kerja, $zip);

            $this->info('4/4 Mengenkripsi…');
            $nama = 'sim-spenga-'.now()->format('Y-m-d-Hi').'.simbak';
            $akhir = rtrim($tujuan, '\\/').DIRECTORY_SEPARATOR.$nama;
            BrankasBackup::enkripsiBerkas($zip, $akhir, $sandi);

            $mb = round(filesize($akhir) / 1048576, 2);

            $this->newLine();
            $this->info("Selesai: {$akhir} ({$mb} MB)");

            $dibuang = $this->buangYangLama($tujuan);
            if ($dibuang > 0) {
                $this->line("Backup lama dihapus: {$dibuang} berkas (disimpan ".config('backup.simpan').' terakhir).');
            }

            $this->newLine();
            $this->warn('Ingat: backup yang belum pernah dicoba dipulihkan bukan backup, melainkan harapan.');
            $this->line('Sekali waktu, ujilah dengan:  php artisan backup:buka "'.$akhir.'"');

            return self::SUCCESS;
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            // Berkas kerja berisi database polos — dihapus apa pun yang
            // terjadi, termasuk saat gagal di tengah jalan.
            File::deleteDirectory($kerja);
        }
    }

    /**
     * Kata sandi database TIDAK diberikan lewat argumen baris perintah,
     * melainkan lewat berkas sementara. Argumen baris perintah terlihat
     * oleh siapa pun yang menjalankan Task Manager di komputer itu.
     */
    private function dumpDatabase(string $tujuan): void
    {
        $mysqldump = $this->cariMysqldump();

        $cnf = storage_path('app/private/my-'.uniqid().'.cnf');
        file_put_contents($cnf, implode("\n", [
            '[client]',
            'host='.config('database.connections.mysql.host'),
            'port='.config('database.connections.mysql.port'),
            'user='.config('database.connections.mysql.username'),
            'password="'.config('database.connections.mysql.password').'"',
        ]));

        try {
            $proses = new Process([
                $mysqldump,
                '--defaults-extra-file='.$cnf,
                '--single-transaction',
                '--routines',
                '--events',
                '--default-character-set=utf8mb4',
                config('database.connections.mysql.database'),
            ]);

            $proses->setTimeout(600);
            $proses->run();

            if (! $proses->isSuccessful()) {
                throw new RuntimeException('mysqldump gagal: '.trim($proses->getErrorOutput()));
            }

            file_put_contents($tujuan, $proses->getOutput());
        } finally {
            @unlink($cnf);
        }
    }

    private function cariMysqldump(): string
    {
        if ($dari = config('backup.mysqldump')) {
            return $dari;
        }

        foreach (glob('C:\\laragon\\bin\\mysql\\*\\bin\\mysqldump.exe') ?: [] as $calon) {
            return $calon;
        }

        throw new RuntimeException(
            'mysqldump tidak ditemukan. Isi BACKUP_MYSQLDUMP di .env dengan letak lengkapnya.'
        );
    }

    private function isiRahasia(): string
    {
        return implode("\n", [
            'RAHASIA — JANGAN DIBAGIKAN',
            '',
            'APP_KEY='.config('app.key'),
            'DB_DATABASE='.config('database.connections.mysql.database'),
            '',
            'APP_KEY dibutuhkan untuk mendekripsi token WhatsApp dan memvalidasi',
            'aktivasi lisensi di dalam database. Tanpa baris ini, dump database',
            'tetap bisa dipulihkan tetapi kedua hal itu hilang permanen.',
        ])."\n";
    }

    private function isiPetunjuk(): string
    {
        return implode("\n", [
            'BACKUP SIM-SPENGA',
            'Dibuat: '.now()->translatedFormat('l, d F Y H:i'),
            '',
            'ISI:',
            '  database.sql  — seluruh isi database',
            '  storage-app/  — sertifikat prestasi, bukti BK, lampiran surat, logo',
            '  RAHASIA.txt   — APP_KEY',
            '',
            'CARA MEMULIHKAN:',
            '  1. Pasang ulang aplikasi, lalu salin isi RAHASIA.txt ke .env',
            '  2. mysql -u root -p nama_database < database.sql',
            '  3. Salin isi storage-app/ ke storage/app/',
            '  4. php artisan optimize:clear',
            '',
            'Urutannya penting: APP_KEY harus terpasang SEBELUM aplikasi dijalankan,',
            'kalau tidak, nilai terenkripsi di database tidak akan terbaca.',
        ])."\n";
    }

    private function buatZip(string $kerja, string $tujuan): void
    {
        $zip = new ZipArchive;

        if ($zip->open($tujuan, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak bisa membuat berkas zip.');
        }

        foreach (['database.sql', 'RAHASIA.txt', 'BACA-SAYA.txt'] as $berkas) {
            $zip->addFile($kerja.'/'.$berkas, $berkas);
        }

        $asal = storage_path('app');
        if (is_dir($asal)) {
            foreach (File::allFiles($asal) as $f) {
                // Folder kerja backup jangan ikut masuk ke dalam dirinya sendiri.
                if (str_contains($f->getPathname(), 'backup-sementara-')) {
                    continue;
                }

                $zip->addFile($f->getPathname(), 'storage-app/'.$f->getRelativePathname());
            }
        }

        $zip->close();
    }

    private function buangYangLama(string $tujuan): int
    {
        $simpan = max(1, (int) config('backup.simpan', 30));

        $daftar = collect(glob(rtrim($tujuan, '\\/').DIRECTORY_SEPARATOR.'sim-spenga-*.simbak') ?: [])
            ->sortByDesc(fn ($p) => filemtime($p))
            ->values();

        $dibuang = 0;
        foreach ($daftar->slice($simpan) as $lama) {
            @unlink($lama);
            $dibuang++;
        }

        return $dibuang;
    }
}
