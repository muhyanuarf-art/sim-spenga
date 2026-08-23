<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Absensi kegiatan ekstrakurikuler — 2 tabel:
 *
 * 1. `absensi_ekskuls` — 1 baris per SESI (1 kegiatan, 1 tanggal). Dicatat
 *    siapa yang mengisi (`dicatat_oleh_id`) — sesuai aturan: yang mengisi
 *    HANYA pembina dari sekolah (atau Kesiswaan/Admin mewakili), tidak
 *    pernah pembina luar sekolah (mereka tidak punya akun sistem).
 *
 * 2. `absensi_ekskul_pesertas` — 1 baris per PESERTA per sesi. Peserta
 *    yang diabsen ada 2 jenis, dan hanya SATU dari dua kolom ini yang
 *    terisi per baris (ditegakkan di level aplikasi, bukan constraint DB):
 *    - `siswa_id`                  : peserta adalah siswa anggota kegiatan.
 *    - `ekstrakurikuler_pembina_id`: peserta adalah PEMBINA kegiatan ini
 *      (baik dari sekolah maupun luar sekolah — tabel
 *      `ekstrakurikuler_pembinas` sudah menaungi keduanya, lihat migrasi
 *      2026_08_23_000003). Sesuai aturan: pembina JUGA diabsen
 *      kehadirannya, cuma tidak bisa mengisi form ini sendiri kalau dia
 *      dari luar sekolah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_ekskuls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ekstrakurikuler_id')->constrained('ekstrakurikulers')->cascadeOnDelete();
            $table->date('tanggal');
            $table->foreignId('dicatat_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kegiatan')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['ekstrakurikuler_id', 'tanggal']);
        });

        Schema::create('absensi_ekskul_pesertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('absensi_ekskul_id')->constrained('absensi_ekskuls')->cascadeOnDelete();
            $table->foreignId('siswa_id')->nullable()->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('ekstrakurikuler_pembina_id')->nullable()->constrained('ekstrakurikuler_pembinas')->cascadeOnDelete();
            $table->enum('status', ['Hadir', 'Sakit', 'Izin', 'Alfa']);
            $table->timestamps();

            $table->unique(['absensi_ekskul_id', 'siswa_id'], 'absensi_ekskul_peserta_siswa_unique');
            $table->unique(['absensi_ekskul_id', 'ekstrakurikuler_pembina_id'], 'absensi_ekskul_peserta_pembina_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_ekskul_pesertas');
        Schema::dropIfExists('absensi_ekskuls');
    }
};
