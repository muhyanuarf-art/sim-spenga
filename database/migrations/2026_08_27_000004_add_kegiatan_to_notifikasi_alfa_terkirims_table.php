<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Histori notifikasi WhatsApp Alfa perlu tahu konteksnya: Alfa saat mata
 * pelajaran apa (KBM), atau saat kegiatan sekolah apa (lomba, asesmen,
 * classmeeting, pesantren Ramadan, dst).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifikasi_alfa_terkirims', function (Blueprint $table) {
            $table->foreignId('kegiatan_sekolah_id')->nullable()->after('mata_pelajaran_id')
                ->constrained('kegiatan_sekolahs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notifikasi_alfa_terkirims', function (Blueprint $table) {
            $table->dropForeign(['kegiatan_sekolah_id']);
            $table->dropColumn('kegiatan_sekolah_id');
        });
    }
};
