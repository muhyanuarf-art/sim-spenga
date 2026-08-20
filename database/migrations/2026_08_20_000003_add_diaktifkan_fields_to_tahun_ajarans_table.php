<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STEP 3 — Pergantian Semester (Bagian 16: log aktivitas sederhana).
 *
 * ADITIF SAJA: tidak ada kolom lama yang dihapus/diubah. Menambah jejak
 * "siapa & kapan periode ini DIAKTIFKAN", melengkapi terkunci_at/
 * terkunci_oleh_id (kapan DITUTUP, dari STEP 1/2) dan dibuka_at/
 * dibuka_oleh_id (kapan DIBUKA KEMBALI, dari STEP 2). Dengan 3 pasang
 * kolom ini, riwayat lengkap "siapa menutup semester X, kapan; siapa
 * mengaktifkan semester Y, kapan" sudah bisa direkonstruksi tanpa perlu
 * tabel activity log terpisah (sesuai instruksi Bagian 16: "jangan buat
 * sistem logging besar, gunakan mekanisme yang sudah ada / sederhana").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahun_ajarans', function (Blueprint $table) {
            $table->timestamp('diaktifkan_at')->nullable()->after('is_active');
            $table->foreignId('diaktifkan_oleh_id')->nullable()->after('diaktifkan_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tahun_ajarans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diaktifkan_oleh_id');
            $table->dropColumn('diaktifkan_at');
        });
    }
};
