<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PERBAIKAN BUG SERIUS — sebelumnya `ekstrakurikuler_id` di 3 tabel
 * (`ekstrakurikuler_pembinas`, `ekstrakurikuler_siswas`, `absensi_ekskuls`)
 * memakai `cascadeOnDelete()`. Akibatnya menghapus 1 baris `ekstrakurikulers`
 * DIAM-DIAM menghapus SEMUA pembina, anggota, dan seluruh riwayat
 * absensi kegiatan itu sekaligus — padahal
 * `EkstrakurikulerController::destroy()` sudah menyiapkan pesan "tidak
 * dapat dihapus karena masih dipakai di data lain", yang seharusnya
 * MENCEGAH ini, tapi tidak pernah muncul karena constraint database-nya
 * justru mengizinkan (cascade), bukan menolak (restrict).
 *
 * Migrasi ini mengubah FK tsb jadi RESTRICT (perilaku default MySQL/
 * InnoDB kalau tidak diberi ->cascadeOnDelete()): kalau kegiatan MASIH
 * punya anggota/pembina/riwayat absensi, penghapusan akan DITOLAK oleh
 * database (QueryException kode 23000) dan controller menampilkan pesan
 * ramah tsb — Kesiswaan harus keluarkan semua anggota & hapus riwayat
 * dulu (atau nonaktifkan saja kegiatannya lewat "Aktif/Nonaktif")
 * sebelum kegiatan itu benar-benar bisa dihapus.
 *
 * Sekalian juga `absensi_ekskul_pesertas.ekstrakurikuler_pembina_id`
 * (tadinya cascade juga) — supaya 1 baris pembina yang SUDAH punya
 * riwayat absensi juga tidak bisa diam-diam terhapus lewat proses edit
 * kegiatan (lihat perbaikan `simpanPembina()` di
 * EkstrakurikulerController, yang sekarang menangkap kasus ini).
 *
 * Kegiatan yang MEMANG masih kosong (baru dibuat, belum ada anggota/
 * pembina/absensi sama sekali) tetap bisa dihapus normal seperti biasa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ekstrakurikuler_pembinas', function (Blueprint $table) {
            $table->dropForeign(['ekstrakurikuler_id']);
            $table->foreign('ekstrakurikuler_id')->references('id')->on('ekstrakurikulers');
        });

        Schema::table('ekstrakurikuler_siswas', function (Blueprint $table) {
            $table->dropForeign(['ekstrakurikuler_id']);
            $table->foreign('ekstrakurikuler_id')->references('id')->on('ekstrakurikulers');
        });

        Schema::table('absensi_ekskuls', function (Blueprint $table) {
            $table->dropForeign(['ekstrakurikuler_id']);
            $table->foreign('ekstrakurikuler_id')->references('id')->on('ekstrakurikulers');
        });

        Schema::table('absensi_ekskul_pesertas', function (Blueprint $table) {
            $table->dropForeign(['ekstrakurikuler_pembina_id']);
            $table->foreign('ekstrakurikuler_pembina_id')->references('id')->on('ekstrakurikuler_pembinas');
        });
    }

    public function down(): void
    {
        Schema::table('ekstrakurikuler_pembinas', function (Blueprint $table) {
            $table->dropForeign(['ekstrakurikuler_id']);
            $table->foreign('ekstrakurikuler_id')->references('id')->on('ekstrakurikulers')->cascadeOnDelete();
        });

        Schema::table('ekstrakurikuler_siswas', function (Blueprint $table) {
            $table->dropForeign(['ekstrakurikuler_id']);
            $table->foreign('ekstrakurikuler_id')->references('id')->on('ekstrakurikulers')->cascadeOnDelete();
        });

        Schema::table('absensi_ekskuls', function (Blueprint $table) {
            $table->dropForeign(['ekstrakurikuler_id']);
            $table->foreign('ekstrakurikuler_id')->references('id')->on('ekstrakurikulers')->cascadeOnDelete();
        });

        Schema::table('absensi_ekskul_pesertas', function (Blueprint $table) {
            $table->dropForeign(['ekstrakurikuler_pembina_id']);
            $table->foreign('ekstrakurikuler_pembina_id')->references('id')->on('ekstrakurikuler_pembinas')->cascadeOnDelete();
        });
    }
};
