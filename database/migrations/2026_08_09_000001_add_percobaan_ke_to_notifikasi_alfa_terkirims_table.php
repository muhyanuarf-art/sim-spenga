<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * percobaan_ke: menghitung total percobaan kirim (maks 2, lihat
     * NotifikasiAlfaTerkirim::MAKS_PERCOBAAN) — khusus untuk kegagalan
     * yang sifatnya "nomor kemungkinan bukan WhatsApp", BUKAN kegagalan
     * teknis biasa (itu sudah ditangani retry job bawaan Laravel).
     *
     * keterangan_gagal: pesan/alasan gagal terakhir dari Fonnte, supaya
     * wali kelas tahu kenapa (mis. "target invalid", nomor tidak terdaftar).
     */
    public function up(): void
    {
        Schema::table('notifikasi_alfa_terkirims', function (Blueprint $table) {
            $table->unsignedTinyInteger('percobaan_ke')->default(1)->after('status_kirim');
            $table->text('keterangan_gagal')->nullable()->after('percobaan_ke');
        });
    }

    public function down(): void
    {
        Schema::table('notifikasi_alfa_terkirims', function (Blueprint $table) {
            $table->dropColumn(['percobaan_ke', 'keterangan_gagal']);
        });
    }
};
