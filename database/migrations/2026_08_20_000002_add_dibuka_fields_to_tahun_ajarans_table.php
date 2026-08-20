<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STEP 2 — Penutupan & Penguncian Semester (Bagian 10).
 *
 * ADITIF SAJA: tidak ada kolom lama yang dihapus/diubah. Menambah jejak
 * "siapa & kapan membuka kembali periode yang sudah terkunci", terpisah
 * dari terkunci_at/terkunci_oleh_id yang sudah ada (yang mencatat kapan
 * DIKUNCI, bukan kapan DIBUKA). Sengaja hanya 2 kolom timestamp+FK —
 * tidak membuat tabel audit log terpisah (Bagian 10: "jangan membuat
 * sistem audit yang berlebihan").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahun_ajarans', function (Blueprint $table) {
            $table->timestamp('dibuka_at')->nullable()->after('terkunci_oleh_id');
            $table->foreignId('dibuka_oleh_id')->nullable()->after('dibuka_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tahun_ajarans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dibuka_oleh_id');
            $table->dropColumn('dibuka_at');
        });
    }
};
