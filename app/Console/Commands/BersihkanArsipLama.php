<?php

namespace App\Console\Commands;

use App\Models\ArsipSemester;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * MENGHAPUS BERKAS ARSIP YANG SUDAH LEWAT SATU TAHUN.
 *
 * =====================================================================
 * KENAPA DIHAPUS SAMA SEKALI
 * =====================================================================
 * Tiap semester menambah puluhan MB yang tidak pernah berkurang. Di
 * hosting berkuota, lima tahun bisa menjadi beban yang baru disadari
 * saat unggahan berhenti bekerja karena kuota penuh — dan itu terjadi
 * pada hari yang tidak pernah kita pilih.
 *
 * =====================================================================
 * YANG DIHAPUS HANYA BERKASNYA, BUKAN CATATANNYA
 * =====================================================================
 * Baris di tabel `arsip_semesters` sengaja DIPERTAHANKAN. Ukurannya
 * beberapa puluh byte, dan ia menjawab pertanyaan yang kelak akan
 * muncul: "dulu arsip semester itu pernah dibuat atau tidak, dan kapan?"
 * Menghapus catatannya berarti menghapus jawabannya juga.
 *
 * Karena berkasnya hilang, Admin akan melihat tombol "Buat Arsip" lagi —
 * dan itu memang keadaan yang sebenarnya.
 *
 * =====================================================================
 * PERINGATAN YANG HARUS SAMPAI KE SEKOLAH
 * =====================================================================
 * Arsip ini adalah salinan yang bisa dibaca tanpa aplikasi — nilainya
 * justru muncul bertahun kemudian, saat aplikasinya sudah tidak dipakai.
 * Karena itu sekolah HARUS mengunduh dan menyimpannya sendiri, jangan
 * mengandalkan berkas di server ini bertahan selamanya.
 */
class BersihkanArsipLama extends Command
{
    protected $signature = 'arsip:bersihkan {--dry-run : Tampilkan saja apa yang akan dihapus}';

    protected $description = 'Menghapus berkas arsip semester yang sudah lebih dari '
        .ArsipSemester::SIMPAN_BULAN.' bulan';

    public function handle(): int
    {
        $batas = now()->subMonths(ArsipSemester::SIMPAN_BULAN);

        $daftar = ArsipSemester::with('tahunAjaran')
            ->whereNotNull('path')
            ->where('selesai_at', '<', $batas)
            ->get();

        if ($daftar->isEmpty()) {
            $this->info('Tidak ada arsip yang perlu dibersihkan.');

            return self::SUCCESS;
        }

        $kering = $this->option('dry-run');
        $total = 0;

        foreach ($daftar as $arsip) {
            $label = $arsip->tahunAjaran?->labelPeriode() ?? 'periode terhapus';
            $ukuran = $arsip->ukuranTerbaca();

            if ($kering) {
                $this->line("  akan dihapus: {$label} ({$ukuran}, dibuat "
                    .$arsip->selesai_at->translatedFormat('d F Y').')');

                continue;
            }

            if ($arsip->path) {
                Storage::disk('local')->delete($arsip->path);
            }

            // Berkasnya hilang, catatannya tinggal — lihat penjelasan
            // di docblock kelas ini.
            $arsip->update([
                'path' => null,
                'ukuran' => null,
                'catatan' => 'Berkas dihapus otomatis pada '.now()->translatedFormat('d F Y')
                    .' karena sudah lebih dari '.ArsipSemester::SIMPAN_BULAN.' bulan.',
            ]);

            $this->line("  dihapus: {$label} ({$ukuran})");
            $total++;
        }

        $this->newLine();
        $this->info($kering
            ? $daftar->count().' arsip akan dihapus. Jalankan tanpa --dry-run untuk benar-benar menghapus.'
            : "{$total} berkas arsip dihapus. Catatannya tetap tersimpan.");

        return self::SUCCESS;
    }
}
