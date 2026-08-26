<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integrasi: Pemanggilan Orang Tua (BK) sekarang menaut ke Surat resmi
 * dari Manajemen Surat (jenis "Surat Panggilan Orang Tua"), bukan lagi
 * upload file bukti mandiri (`bukti_file`) yang terpisah dari sistem
 * persuratan. `surat_id` NULLABLE — pemanggilan lama (sebelum integrasi
 * ini) tetap valid tanpa surat terkait, dan `bukti_file` KOLOM LAMA TIDAK
 * DIHAPUS (data lama tidak hilang), cuma tidak dipakai lagi di form baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemanggilan_orangtuas', function (Blueprint $table) {
            $table->foreignId('surat_id')->nullable()->after('kasus_siswa_id')->constrained('surats');
        });
    }

    public function down(): void
    {
        Schema::table('pemanggilan_orangtuas', function (Blueprint $table) {
            $table->dropForeign(['surat_id']);
            $table->dropColumn('surat_id');
        });
    }
};
