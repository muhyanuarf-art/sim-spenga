<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel master "Kegiatan Ekstrakurikuler" (mis. Pramuka, PMR, Futsal, dll).
 * Dikelola oleh Kesiswaan (lihat EkstrakurikulerController) — langkah
 * pertama dari fitur absensi ekskul: Kesiswaan input nama-nama kegiatan
 * dulu di sini, baru nanti anggota/jadwal/absensi per kegiatan dibangun
 * di atas tabel ini (bukan bagian migrasi ini).
 *
 * `pembina_id` sengaja NULLABLE — kegiatan boleh dicatat dulu meski
 * pembinanya belum ditentukan, dan hanya menampung SATU pembina per baris
 * di tahap awal ini (kalau nanti perlu >1 pembina per kegiatan, itu bisa
 * dipecah ke tabel pivot terpisah tanpa mengubah tabel ini).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekstrakurikulers', function (Blueprint $table) {
            $table->id();
            $table->string('nama_ekstrakurikuler');
            $table->foreignId('pembina_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekstrakurikulers');
    }
};
