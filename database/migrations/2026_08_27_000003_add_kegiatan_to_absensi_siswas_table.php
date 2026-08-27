<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyambungkan absensi kegiatan ke tabel absensi siswa yang SUDAH ADA.
 *
 * Keputusan penting: absensi kegiatan TIDAK dibuatkan tabel rincian
 * sendiri, melainkan menumpang di absensi_siswas. Alasannya, seluruh
 * laporan yang sudah berjalan (Rekap Absensi Kelas, Rekapitulasi,
 * dashboard, portal orang tua, notifikasi WhatsApp Alfa) membaca dari
 * tabel ini. Dengan menumpang, kehadiran pada hari kegiatan langsung
 * ikut terhitung di semua tempat itu tanpa satu pun laporan perlu
 * ditulis ulang.
 *
 * Konsekuensinya jurnal_mengajar_id harus boleh kosong: baris absensi
 * kegiatan tidak berasal dari jurnal mengajar mana pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi_siswas', function (Blueprint $table) {
            $table->dropForeign(['jurnal_mengajar_id']);
        });

        Schema::table('absensi_siswas', function (Blueprint $table) {
            $table->foreignId('jurnal_mengajar_id')->nullable()->change();
        });

        Schema::table('absensi_siswas', function (Blueprint $table) {
            $table->foreign('jurnal_mengajar_id')->references('id')->on('jurnal_mengajars')->cascadeOnDelete();

            $table->foreignId('absensi_kegiatan_id')->nullable()->after('jurnal_mengajar_id')
                ->constrained('absensi_kegiatans')->cascadeOnDelete();

            // 'kbm' = dari jurnal mengajar guru mapel, 'kegiatan' = dari
            // absensi kegiatan sekolah oleh wali kelas.
            $table->string('sumber', 15)->default('kbm')->after('absensi_kegiatan_id');

            // Satu siswa hanya boleh punya 1 baris per absensi kegiatan
            // (setara absensi_unique_jurnal_siswa untuk jalur KBM).
            $table->unique(['absensi_kegiatan_id', 'siswa_id'], 'absensi_unique_kegiatan_siswa');
        });
    }

    public function down(): void
    {
        Schema::table('absensi_siswas', function (Blueprint $table) {
            $table->dropUnique('absensi_unique_kegiatan_siswa');
            $table->dropForeign(['absensi_kegiatan_id']);
            $table->dropColumn(['absensi_kegiatan_id', 'sumber']);
        });

        // Kembalikan jurnal_mengajar_id jadi wajib. Baris absensi kegiatan
        // (yang jurnal_mengajar_id-nya kosong) dihapus lebih dulu supaya
        // perubahan kolom ini tidak gagal.
        \Illuminate\Support\Facades\DB::table('absensi_siswas')->whereNull('jurnal_mengajar_id')->delete();

        Schema::table('absensi_siswas', function (Blueprint $table) {
            $table->dropForeign(['jurnal_mengajar_id']);
        });
        Schema::table('absensi_siswas', function (Blueprint $table) {
            $table->foreignId('jurnal_mengajar_id')->nullable(false)->change();
        });
        Schema::table('absensi_siswas', function (Blueprint $table) {
            $table->foreign('jurnal_mengajar_id')->references('id')->on('jurnal_mengajars')->cascadeOnDelete();
        });
    }
};
