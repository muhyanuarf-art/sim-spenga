<?php

namespace App\Console\Commands;

use App\Models\Pemberitahuan;
use Illuminate\Console\Command;

/**
 * Buang pemberitahuan yang sudah tua.
 *
 * Pemberitahuan adalah PENANDA, bukan arsip. Datanya sendiri — kasus
 * siswa, prestasi — tetap utuh di menunya masing-masing; yang dibuang
 * di sini hanya penunjuknya. Jadi tidak ada informasi yang hilang.
 *
 * Tanpa pembersihan, tabel ini tumbuh terus: satu kejadian menghasilkan
 * satu baris per penerima, dan sekolah dengan 30 guru bisa mengumpulkan
 * puluhan ribu baris dalam beberapa tahun — padahal yang pernah dibaca
 * orang hanya beberapa hari pertama.
 *
 * Yang BELUM dibaca ikut dibuang bila sudah lewat batas. Pemberitahuan
 * berumur tiga bulan yang belum dibuka sudah tidak berguna bagi
 * siapa pun, dan membiarkannya hanya membuat angka di lonceng terlihat
 * menakutkan tanpa sebab.
 */
class BersihkanPemberitahuan extends Command
{
    protected $signature = 'pemberitahuan:bersihkan
        {--lihat : Hanya tampilkan jumlahnya, tidak menghapus apa pun}';

    protected $description = 'Hapus pemberitahuan yang lebih tua dari '.Pemberitahuan::SIMPAN_HARI.' hari';

    public function handle(): int
    {
        $batas = now()->subDays(Pemberitahuan::SIMPAN_HARI);

        $jumlah = Pemberitahuan::where('created_at', '<', $batas)->count();

        $this->line('Batas: sebelum '.$batas->translatedFormat('d F Y')
            .' ('.Pemberitahuan::SIMPAN_HARI.' hari).');

        if ($jumlah === 0) {
            $this->info('Tidak ada pemberitahuan lama. Tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        if ($this->option('lihat')) {
            $this->line("Ada {$jumlah} baris yang akan dihapus. Mode lihat saja.");

            return self::SUCCESS;
        }

        Pemberitahuan::where('created_at', '<', $batas)->delete();

        $this->info("{$jumlah} pemberitahuan lama dihapus. Data aslinya tidak tersentuh.");

        return self::SUCCESS;
    }
}
