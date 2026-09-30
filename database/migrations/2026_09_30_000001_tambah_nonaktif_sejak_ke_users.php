<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KAPAN SEBUAH AKUN DINONAKTIFKAN.
 *
 * =====================================================================
 * KENAPA TANGGALNYA PERLU DICATAT
 * =====================================================================
 * `is_active` hanya menjawab "masih boleh masuk atau tidak". Ia tidak
 * menjawab "sejak kapan", dan tanpa itu satu laporan jadi salah.
 *
 * Kasusnya: guru pensiun atau mutasi di tengah semester, lalu Jadwal
 * Pelajarannya dialihkan ke guru pengganti. Rekapitulasi Kepatuhan
 * menghitung "seharusnya" dari jadwal yang berlaku SEKARANG, sehingga:
 *
 *   - guru yang keluar jatuh menjadi 0 dari 0, seolah sepanjang
 *     semester tidak punya jam mengajar sama sekali;
 *   - guru pengganti menanggung sebulan penuh, termasuk pekan-pekan
 *     sebelum ia datang.
 *
 * Dengan tanggal ini, jendela hitung tiap guru bisa ditutup pada hari
 * terakhirnya. Sisa keadilannya diselesaikan dengan cara lain — lihat
 * RekapController, yang menimbang tiap tanggal berdasarkan SIAPA YANG
 * BENAR-BENAR MENULIS jurnalnya, bukan siapa yang memegang jadwalnya
 * hari ini.
 *
 * Dikosongkan kembali saat akun diaktifkan lagi: guru yang sempat
 * dinonaktifkan karena keliru tidak boleh kehilangan jam mengajarnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('nonaktif_sejak')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nonaktif_sejak');
        });
    }
};
