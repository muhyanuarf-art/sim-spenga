<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LOGO / IKON APLIKASI SIM.
 *
 * Berbeda dengan `logo_kiri_path` & `logo_kanan_path` yang sudah ada:
 * kedua logo itu KHUSUS untuk KOP Surat, jadi hanya muncul saat dokumen
 * dicetak dan tidak pernah tampil di layar sehari-hari.
 *
 * Kolom ini untuk identitas aplikasinya sendiri — yang tampil di pojok
 * kiri atas sidebar (selama ini masih kotak bertuliskan "SP" yang
 * ditulis mati di dalam kode), di halaman login guru & orang tua, serta
 * sebagai favicon di tab browser. Sekolah bisa menggantinya sendiri tanpa
 * perlu mengubah kode.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_sekolahs', function (Blueprint $table) {
            $table->string('logo_aplikasi_path')->nullable()->after('logo_kanan_path');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_sekolahs', function (Blueprint $table) {
            $table->dropColumn('logo_aplikasi_path');
        });
    }
};
