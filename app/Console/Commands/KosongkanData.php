<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * KOSONGKAN SELURUH DATA ISIAN, SISAKAN MASTER DATA.
 *
 * =====================================================================
 * UNTUK APA
 * =====================================================================
 * Dipakai sekali saat aplikasi selesai diuji coba dan hendak dipakai
 * sungguhan: seluruh data percobaan (siswa, penempatan kelas, jadwal,
 * absensi, nilai, jurnal, BK, surat, kegiatan) dibuang, sementara daftar
 * acuan yang sudah susah payah disusun — mata pelajaran, jam pelajaran,
 * jenis pelanggaran, jenis surat, kelas, ekstrakurikuler, akun guru,
 * pengaturan sekolah — tetap utuh.
 *
 * =====================================================================
 * CARA MENENTUKAN TABEL SASARAN
 * =====================================================================
 * Yang didaftar manual di sini HANYA tabel yang DIPERTAHANKAN. Sisanya —
 * apa pun isinya, termasuk tabel yang baru ditambahkan lewat migrasi di
 * kemudian hari — otomatis ikut dikosongkan.
 *
 * Arahnya sengaja dibalik begitu: kalau yang didaftar manual adalah tabel
 * yang dihapus, satu tabel baru yang lupa didaftarkan akan diam-diam
 * tertinggal berisi data lama dan tidak ada yang menyadarinya. Dengan arah
 * ini, kesalahan yang mungkin terjadi adalah tabel baru ikut terhapus —
 * dan itu kelihatan langsung di daftar konfirmasi sebelum dieksekusi.
 *
 * =====================================================================
 * PENGAMAN
 * =====================================================================
 * - Cadangan database dibuat otomatis lebih dulu (mysqldump). Bila
 *   mysqldump tidak ketemu, perintah ini BERHENTI, kecuali dipaksa.
 * - Rincian isi tiap tabel ditampilkan, lalu wajib mengetik HAPUS.
 * - TRUNCATE tidak bisa dibatalkan. Cadangan itulah satu-satunya jalan
 *   pulang, jadi jangan dilewati.
 */
class KosongkanData extends Command
{
    protected $signature = 'data:kosongkan
        {--lihat : Hanya tampilkan rencananya, tidak menghapus apa pun}
        {--tanpa-cadangan : Lanjutkan walau cadangan otomatis gagal dibuat}';

    protected $description = 'Kosongkan semua data isian, sisakan master data (mapel, kelas, jam, jenis, akun, pengaturan)';

    /** Tabel yang DIPERTAHANKAN. Selain yang ada di sini, dikosongkan. */
    private const DIPERTAHANKAN = [
        // --- Periode & pengaturan ---
        'tahun_ajarans',
        'pengaturan_sekolahs',
        'pengaturan_penilaians',
        'kktp_tingkats',

        // --- Akun kepegawaian (guru, BK, kurikulum, kesiswaan, admin) ---
        'users',

        // --- Daftar acuan akademik ---
        'mata_pelajarans',
        'jam_pelajarans',
        'kelas',

        // --- Daftar acuan modul ---
        'jenis_pelanggarans',
        'jenis_surats',
        'ekstrakurikulers',

        // --- Milik sistem, jangan disentuh ---
        'migrations',
        'lisensi_aplikasis',
    ];

    /**
     * Tabel bawaan Laravel yang isinya sementara. Dikosongkan juga, tapi
     * dilaporkan terpisah supaya tidak membingungkan: ini bukan "data
     * sekolah", dan mengosongkannya cuma berarti semua orang perlu login
     * lagi.
     */
    private const TEKNIS = [
        'sessions', 'cache', 'cache_locks', 'jobs', 'failed_jobs', 'password_reset_tokens',
    ];

    public function handle(): int
    {
        $namaDb = (string) config('database.connections.mysql.database');

        [$dataSekolah, $teknis, $dipertahankan] = $this->kelompokkanTabel($namaDb);

        $this->tampilkanRencana($dataSekolah, $teknis, $dipertahankan, $namaDb);

        if ($this->option('lihat')) {
            $this->newLine();
            $this->info('Mode lihat saja — tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        $totalBaris = array_sum($dataSekolah);

        if ($totalBaris === 0 && array_sum($teknis) === 0) {
            $this->newLine();
            $this->info('Semua tabel sasaran sudah kosong. Tidak ada yang perlu dilakukan.');

            return self::SUCCESS;
        }

        $berkasCadangan = $this->buatCadangan($namaDb);

        if ($berkasCadangan === null) {
            if (! $this->option('tanpa-cadangan')) {
                $this->newLine();
                $this->error('Cadangan gagal dibuat, jadi penghapusan DIBATALKAN.');
                $this->line('  Buat cadangan manual dulu lewat phpMyAdmin (menu Export), lalu ulangi');
                $this->line('  perintah ini dengan tambahan --tanpa-cadangan.');

                return self::FAILURE;
            }

            $this->warn('Lanjut TANPA cadangan otomatis (--tanpa-cadangan).');
        }

        $this->newLine();
        $this->warn("Tindakan ini mengosongkan {$totalBaris} baris data sekolah dan TIDAK BISA DIBATALKAN.");

        if ($this->ask('Ketik HAPUS untuk melanjutkan') !== 'HAPUS') {
            $this->info('Dibatalkan. Tidak ada yang berubah.');

            return self::SUCCESS;
        }

        $this->kosongkan(array_merge(array_keys($dataSekolah), array_keys($teknis)));

        $this->newLine();
        $this->info('Selesai. Master data tetap utuh:');
        foreach ($dipertahankan as $tabel => $jumlah) {
            $this->line(sprintf('  %-24s %6d baris', $tabel, $jumlah));
        }

        if ($berkasCadangan) {
            $this->newLine();
            $this->line('Cadangan sebelum penghapusan: '.$berkasCadangan);
        }

        $this->newLine();
        $this->line('Sesi login ikut dikosongkan — silakan login ulang di browser.');

        return self::SUCCESS;
    }

    /**
     * @return array{0: array<string,int>, 1: array<string,int>, 2: array<string,int>}
     */
    private function kelompokkanTabel(string $namaDb): array
    {
        $semua = collect(DB::select(
            'SELECT TABLE_NAME AS t FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = "BASE TABLE" ORDER BY TABLE_NAME',
            [$namaDb]
        ))->pluck('t');

        $dataSekolah = [];
        $teknis = [];
        $dipertahankan = [];

        foreach ($semua as $tabel) {
            $jumlah = DB::table($tabel)->count();

            if (in_array($tabel, self::DIPERTAHANKAN, true)) {
                $dipertahankan[$tabel] = $jumlah;
            } elseif (in_array($tabel, self::TEKNIS, true)) {
                $teknis[$tabel] = $jumlah;
            } else {
                $dataSekolah[$tabel] = $jumlah;
            }
        }

        arsort($dataSekolah);

        return [$dataSekolah, $teknis, $dipertahankan];
    }

    private function tampilkanRencana(array $dataSekolah, array $teknis, array $dipertahankan, string $namaDb): void
    {
        $this->newLine();
        $this->line("Database: <options=bold>{$namaDb}</>");

        $this->newLine();
        $this->line('<fg=red;options=bold>AKAN DIKOSONGKAN - data sekolah</>');
        foreach ($dataSekolah as $tabel => $jumlah) {
            $tanda = $jumlah > 0 ? '*' : ' ';
            $this->line(sprintf('  %s %-28s %6d baris', $tanda, $tabel, $jumlah));
        }

        $this->newLine();
        $this->line('<fg=yellow>AKAN DIKOSONGKAN - data sementara sistem</>');
        foreach ($teknis as $tabel => $jumlah) {
            $this->line(sprintf('    %-28s %6d baris', $tabel, $jumlah));
        }

        $this->newLine();
        $this->line('<fg=green;options=bold>DIPERTAHANKAN - master data</>');
        foreach ($dipertahankan as $tabel => $jumlah) {
            $this->line(sprintf('    %-28s %6d baris', $tabel, $jumlah));
        }
    }

    /**
     * Cadangan lewat mysqldump. Mengembalikan path berkasnya, atau null
     * bila gagal (mis. mysqldump tidak ada di server ini).
     */
    private function buatCadangan(string $namaDb): ?string
    {
        $mysqldump = $this->cariMysqldump();

        if ($mysqldump === null) {
            $this->warn('mysqldump tidak ditemukan - cadangan otomatis tidak bisa dibuat.');

            return null;
        }

        $folder = storage_path('app/cadangan');

        if (! is_dir($folder) && ! @mkdir($folder, 0775, true)) {
            $this->warn("Folder cadangan tidak bisa dibuat: {$folder}");

            return null;
        }

        $berkas = $folder.DIRECTORY_SEPARATOR.'sebelum-kosongkan-'.date('Ymd-His').'.sql';

        $perintah = [
            escapeshellarg($mysqldump),
            '--host='.escapeshellarg((string) config('database.connections.mysql.host')),
            '--port='.escapeshellarg((string) config('database.connections.mysql.port')),
            '--user='.escapeshellarg((string) config('database.connections.mysql.username')),
            '--no-tablespaces',
            '--single-transaction',
            escapeshellarg($namaDb),
            '>',
            escapeshellarg($berkas),
        ];

        $sandi = (string) config('database.connections.mysql.password');

        if ($sandi !== '') {
            // Lewat variabel lingkungan, bukan argumen baris perintah:
            // argumen perintah terbaca oleh proses lain di server yang sama.
            putenv('MYSQL_PWD='.$sandi);
        }

        $this->line('Membuat cadangan...');

        $keluaran = [];
        exec(implode(' ', $perintah).' 2>&1', $keluaran, $kode);

        putenv('MYSQL_PWD');

        if ($kode !== 0 || ! is_file($berkas) || filesize($berkas) < 1024) {
            $this->warn('mysqldump gagal: '.implode(' ', array_slice($keluaran, 0, 3)));
            @unlink($berkas);

            return null;
        }

        $this->info('Cadangan tersimpan: '.$berkas.' ('.number_format(filesize($berkas) / 1024, 1).' KB)');

        return $berkas;
    }

    private function cariMysqldump(): ?string
    {
        // Laragon menaruh MySQL di folder berversi, jadi dicari lewat pola.
        $kandidat = array_merge(
            glob('C:/laragon/bin/mysql/*/bin/mysqldump.exe') ?: [],
            glob('C:/xampp/mysql/bin/mysqldump.exe') ?: [],
            ['/usr/bin/mysqldump', '/usr/local/bin/mysqldump']
        );

        foreach ($kandidat as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        // Terakhir: andalkan PATH.
        $keluaran = [];
        exec('mysqldump --version 2>&1', $keluaran, $kode);

        return $kode === 0 ? 'mysqldump' : null;
    }

    /**
     * TRUNCATE semua tabel sasaran dengan pemeriksaan foreign key
     * dimatikan sementara — urutan penghapusan jadi tidak penting, dan
     * nomor ID kembali mulai dari 1 supaya benar-benar seperti baru.
     */
    private function kosongkan(array $tabel): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            $bar = $this->output->createProgressBar(count($tabel));
            $bar->start();

            foreach ($tabel as $t) {
                DB::table($t)->truncate();
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }
}
