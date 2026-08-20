<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * STEP 1 — Fondasi Tahun Ajaran, Semester, Periode Aktif.
 *
 * Perubahan ini ADITIF SAJA: tidak ada kolom lama yang dihapus/diubah tipe,
 * tidak ada data yang hilang. Tabel tahun_ajarans tetap 1 baris = kombinasi
 * (nama tahun ajaran + semester) — struktur yang sudah ada dipertahankan
 * karena sudah aman dipakai oleh 8 tabel anak & 15 controller (lihat audit
 * STEP 1). Yang ditambahkan hanyalah:
 *   - tanggal_mulai / tanggal_selesai   (Bagian 6)
 *   - status (akan_datang|aktif|selesai) sebagai representasi eksplisit
 *     dari siklus hidup periode di level TAMPILAN/ADMIN (Bagian 3 & 4).
 *     Kolom 'terkunci' yang sudah ada TIDAK digabung ke dalam status ini —
 *     lock tetap flag terpisah seperti sebelumnya (mekanisme lock adalah
 *     lingkup STEP 2, tidak diubah di sini).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahun_ajarans', function (Blueprint $table) {
            $table->date('tanggal_mulai')->nullable()->after('semester');
            $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
            $table->string('status', 20)->default('akan_datang')->after('is_active');
        });

        // Backfill aman untuk data lama: baris yang sedang aktif -> 'aktif',
        // sisanya default 'akan_datang' (admin bisa koreksi manual ke
        // 'selesai' lewat halaman edit untuk periode yang sudah lewat).
        DB::table('tahun_ajarans')->where('is_active', true)->update(['status' => 'aktif']);
    }

    public function down(): void
    {
        Schema::table('tahun_ajarans', function (Blueprint $table) {
            $table->dropColumn(['tanggal_mulai', 'tanggal_selesai', 'status']);
        });
    }
};
