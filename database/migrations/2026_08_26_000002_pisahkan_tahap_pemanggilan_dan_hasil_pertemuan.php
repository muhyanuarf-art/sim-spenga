<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PERBAIKAN ALUR — sebelumnya 1 form yang sama meminta "Orang Tua/Wali
 * Hadir?", "Hasil Pertemuan", "Kesepakatan" BERSAMAAN saat pemanggilan
 * baru DIBUAT (surat baru dikirim, pertemuan belum terjadi). Padahal
 * alur sebenarnya 2 tahap terpisah waktu:
 *   1. Pemanggilan dibuat (surat dikirim) — hasil pertemuan BELUM ADA.
 *   2. Setelah pertemuan BENAR-BENAR terjadi, BK baru mengisi hasilnya.
 *
 * Kolom baru `status` membedakan kedua tahap itu. `ortu_hadir` diubah
 * jadi NULLABLE (dulu boolean wajib, default false — jadi tidak bisa
 * membedakan "belum diisi" dari "sudah dicek, ternyata tidak hadir").
 *
 * Data LAMA (dibuat lewat form gabungan sebelum perbaikan ini) dianggap
 * SELESAI (sudah ada hasilnya, karena memang begitu alur lama memaksa
 * diisi sekaligus) — tidak ada yang perlu diisi ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemanggilan_orangtuas', function (Blueprint $table) {
            $table->enum('status', ['menunggu_pertemuan', 'selesai'])
                ->default('menunggu_pertemuan')->after('surat_id');
        });

        // Raw SQL, BUKAN ->change() — proyek ini tidak meng-install
        // doctrine/dbal (paket yang biasa dibutuhkan Laravel untuk
        // memodifikasi kolom lewat Schema builder), jadi diubah langsung
        // lewat SQL supaya tidak perlu tambah dependency baru.
        DB::statement('ALTER TABLE pemanggilan_orangtuas MODIFY ortu_hadir TINYINT(1) NULL DEFAULT NULL');

        DB::table('pemanggilan_orangtuas')->update(['status' => 'selesai']);
    }

    public function down(): void
    {
        Schema::table('pemanggilan_orangtuas', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        DB::table('pemanggilan_orangtuas')->whereNull('ortu_hadir')->update(['ortu_hadir' => false]);
        DB::statement('ALTER TABLE pemanggilan_orangtuas MODIFY ortu_hadir TINYINT(1) NOT NULL DEFAULT 0');
    }
};
