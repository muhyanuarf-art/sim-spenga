<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * (2026-08-23, revisi) — kolom `pembina_id` di `ekstrakurikulers` cuma
 * menampung 1 pembina & harus user terdaftar di sistem. Ternyata di
 * lapangan 1 kegiatan bisa punya BEBERAPA pembina, dan sebagian pembina
 * bukan staf sekolah (tidak punya akun di sistem). Migrasi ini memecah
 * pembina jadi tabel tersendiri `ekstrakurikuler_pembinas`:
 * - `user_id` diisi kalau pembina itu staf sekolah (guru/guru BK/kesiswaan
 *   yang sudah jadi user sistem).
 * - `nama_eksternal` (+ `kontak_eksternal` opsional) diisi kalau pembina
 *   itu dari LUAR sekolah (tidak punya akun) — jadi TIDAK terikat ke
 *   tabel users.
 * Satu baris hanya salah satu (user_id ATAU nama_eksternal), tidak dua-duanya.
 *
 * Data lama di kolom `pembina_id` (kalau sudah sempat diisi) dipindah dulu
 * ke tabel baru ini sebagai pembina internal, baru kolom lama dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekstrakurikuler_pembinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ekstrakurikuler_id')->constrained('ekstrakurikulers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('nama_eksternal')->nullable();
            $table->string('kontak_eksternal')->nullable();
            $table->timestamps();
        });

        // Backfill: pindahkan pembina_id lama (kalau ada) jadi 1 baris
        // pembina internal, supaya data yang sudah sempat diisi tidak hilang.
        DB::table('ekstrakurikulers')->whereNotNull('pembina_id')->orderBy('id')
            ->each(function ($row) {
                DB::table('ekstrakurikuler_pembinas')->insert([
                    'ekstrakurikuler_id' => $row->id,
                    'user_id' => $row->pembina_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('ekstrakurikulers', function (Blueprint $table) {
            $table->dropForeign(['pembina_id']);
            $table->dropColumn('pembina_id');
        });
    }

    public function down(): void
    {
        Schema::table('ekstrakurikulers', function (Blueprint $table) {
            $table->foreignId('pembina_id')->nullable()->after('nama_ekstrakurikuler')->constrained('users')->nullOnDelete();
        });

        // Backfill mundur: ambil 1 pembina INTERNAL pertama saja per
        // kegiatan (struktur lama cuma muat 1) — pembina eksternal & baris
        // ke-2 dst hilang saat rollback, ini keterbatasan yang disengaja.
        DB::table('ekstrakurikuler_pembinas')->whereNotNull('user_id')->orderBy('id')
            ->get()
            ->unique('ekstrakurikuler_id')
            ->each(function ($row) {
                DB::table('ekstrakurikulers')->where('id', $row->ekstrakurikuler_id)
                    ->update(['pembina_id' => $row->user_id]);
            });

        Schema::dropIfExists('ekstrakurikuler_pembinas');
    }
};
