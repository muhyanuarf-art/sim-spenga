<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perbaikan performa lanjutan (permintaan admin: "loading apapun harus
 * sangat cepat"). 4 tabel ini sebelumnya hanya punya index KOMPOSIT yang
 * kolom pertamanya siswa_id (unique/index(siswa_id, tanggal)) — tidak
 * berguna untuk query yang memfilter tanggal saja lintas SEMUA siswa
 * (persis pola yang dipakai BkKasusController, BkPemanggilanController,
 * BkPembinaanController, BkPenguranganPoinController, BkDashboardController
 * saat admin/BK memfilter berdasarkan bulan/tahun). Index baru di bawah
 * ini (kolom tanggal sendiri) membuat filter rentang tanggal bisa
 * memakai index langsung, sama seperti perbaikan sebelumnya untuk tabel
 * notifikasi_alfa_terkirims.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kasus_siswas', function (Blueprint $table) {
            $table->index('tanggal_kejadian');
        });
        Schema::table('pemanggilan_orangtuas', function (Blueprint $table) {
            $table->index('tanggal');
        });
        Schema::table('pembinaan_siswas', function (Blueprint $table) {
            $table->index('tanggal');
        });
        Schema::table('pengurangan_poin_siswas', function (Blueprint $table) {
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('kasus_siswas', function (Blueprint $table) {
            $table->dropIndex(['tanggal_kejadian']);
        });
        Schema::table('pemanggilan_orangtuas', function (Blueprint $table) {
            $table->dropIndex(['tanggal']);
        });
        Schema::table('pembinaan_siswas', function (Blueprint $table) {
            $table->dropIndex(['tanggal']);
        });
        Schema::table('pengurangan_poin_siswas', function (Blueprint $table) {
            $table->dropIndex(['tanggal']);
        });
    }
};
