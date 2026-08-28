<?php

use App\Support\NomorSuratBk;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PERBAIKAN: nomor surat kembar masih bisa tersimpan.
 *
 * Sebelum ini tidak ada satu pun penjaga keunikan nomor surat — tidak di
 * validasi controller, tidak pula di database. Akibatnya dua surat berbeda
 * bisa memakai nomor yang sama persis, dan itu fatal untuk buku agenda
 * surat: nomor surat adalah identitas surat di arsip.
 *
 * Perbaikannya dua lapis (pola yang sama dengan modul lain di aplikasi ini):
 *
 * 1. VALIDASI di SuratController — memberi pesan yang jelas dalam bahasa
 *    Indonesia, menyebutkan surat mana yang sudah memakai nomor itu.
 * 2. UNIQUE INDEX di sini — penjaga terakhir, supaya nomor kembar tetap
 *    mustahil tersimpan walau lewat jalur lain (import, tinker, dua
 *    permintaan bersamaan).
 *
 * Yang diberi unique index adalah kolom BARU `nomor_kunci`, bukan
 * `nomor_surat` itu sendiri. Alasannya: guru menulis nomor urut dengan
 * tangan, dan "422/001/BK/VIII/2026" dengan "422/1/BK/VIII/2026" adalah
 * NOMOR YANG SAMA di buku agenda meskipun berbeda sebagai teks. Unique
 * index pada teks mentah akan meloloskannya. `nomor_kunci` menyimpan
 * bentuk yang sudah dinormalkan (lihat App\Support\NomorSuratBk::kunci()),
 * sementara `nomor_surat` tetap apa adanya seperti yang diketik guru —
 * itulah yang tercetak di surat.
 *
 * Kolom sengaja NULLABLE: surat yang belum bernomor tetap boleh disimpan,
 * dan MySQL mengizinkan banyak baris NULL pada unique index.
 *
 * CATATAN kalau migrasi ini GAGAL: berarti di database sudah ADA nomor
 * kembar dari sebelum perbaikan. Migrasi sengaja BERHENTI dan menyebutkan
 * nomor mana yang bentrok, BUKAN memperbaikinya diam-diam — mengubah atau
 * menghapus nomor surat yang sudah terlanjur diarsipkan harus keputusan
 * manusia, bukan keputusan program. Perbaiki lewat menu Surat (ubah nomor
 * salah satunya atau hapus surat yang kembar), lalu jalankan lagi
 * `php artisan migrate`. Migrasi ini aman diulang.
 */
return new class extends Migration
{
    private const NAMA_INDEX = 'surats_nomor_kunci_unique';

    public function up(): void
    {
        if (! Schema::hasColumn('surats', 'nomor_kunci')) {
            Schema::table('surats', function (Blueprint $table) {
                $table->string('nomor_kunci', 150)->nullable()->after('nomor_surat');
            });
        }

        $this->isiUlangKunci();
        $this->pastikanTidakAdaKembar();

        if (! $this->indexSudahAda()) {
            Schema::table('surats', function (Blueprint $table) {
                $table->unique('nomor_kunci', self::NAMA_INDEX);
            });
        }
    }

    public function down(): void
    {
        if ($this->indexSudahAda()) {
            Schema::table('surats', function (Blueprint $table) {
                $table->dropUnique(self::NAMA_INDEX);
            });
        }

        if (Schema::hasColumn('surats', 'nomor_kunci')) {
            Schema::table('surats', function (Blueprint $table) {
                $table->dropColumn('nomor_kunci');
            });
        }
    }

    /** Hitung kunci untuk seluruh surat yang sudah ada. */
    private function isiUlangKunci(): void
    {
        DB::table('surats')->orderBy('id')->select('id', 'nomor_surat')->chunk(500, function ($daftar) {
            foreach ($daftar as $surat) {
                DB::table('surats')->where('id', $surat->id)->update([
                    'nomor_kunci' => NomorSuratBk::kunci($surat->nomor_surat),
                ]);
            }
        });
    }

    /** Berhenti dengan penjelasan yang jelas kalau masih ada nomor kembar. */
    private function pastikanTidakAdaKembar(): void
    {
        $kembar = DB::table('surats')
            ->select('nomor_kunci')
            ->whereNotNull('nomor_kunci')
            ->groupBy('nomor_kunci')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('nomor_kunci');

        if ($kembar->isEmpty()) {
            return;
        }

        $rincian = DB::table('surats')
            ->whereIn('nomor_kunci', $kembar)
            ->orderBy('nomor_kunci')->orderBy('id')
            ->get(['id', 'nomor_surat', 'tanggal', 'siswa_id'])
            ->map(fn ($s) => "    - Surat #{$s->id} | {$s->nomor_surat} | tanggal ".substr((string) $s->tanggal, 0, 10))
            ->implode(PHP_EOL);

        throw new RuntimeException(
            PHP_EOL
            ."Migrasi dihentikan: masih ada NOMOR SURAT KEMBAR di database, sehingga".PHP_EOL
            ."unique index belum bisa dipasang.".PHP_EOL.PHP_EOL
            .$rincian.PHP_EOL.PHP_EOL
            ."Perbaiki dulu lewat menu Surat — ubah nomor salah satunya, atau hapus".PHP_EOL
            ."surat yang memang kembar — lalu jalankan lagi: php artisan migrate".PHP_EOL
            ."(Nomor surat sengaja TIDAK diubah otomatis: itu identitas surat di arsip.)".PHP_EOL
        );
    }

    private function indexSudahAda(): bool
    {
        return collect(DB::select('SHOW INDEX FROM surats'))
            ->contains(fn ($i) => $i->Key_name === self::NAMA_INDEX);
    }
};
