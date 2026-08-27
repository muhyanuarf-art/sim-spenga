<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KEGIATAN SEKOLAH DI LUAR JAM KBM.
 *
 * Selama ini absensi siswa HANYA bisa diisi lewat jadwal mengajar (guru
 * mapel per jam pelajaran). Padahal banyak hari sekolah yang tidak berisi
 * KBM sama sekali: lomba Agustus, tryout & asesmen sumatif, classmeeting,
 * pesantren Ramadan, dan sebagainya. Pada hari-hari itu tidak ada guru
 * mapel yang mengisi absensi, sehingga kehadiran siswa tidak tercatat dan
 * notifikasi WhatsApp Alfa ke orang tua tidak pernah jalan.
 *
 * Tabel ini menjadwalkan kegiatan tersebut. Yang berhak mengisi absensinya
 * HANYA WALI KELAS (lihat AbsensiKegiatanController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan_sekolahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->restrictOnDelete();
            $table->string('nama');
            // lomba | asesmen | classmeeting | keagamaan | lainnya
            $table->string('jenis', 30)->default('lainnya');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            // Kegiatan bisa berlangsung hanya pada hari tertentu dalam rentang
            // tanggal (mis. lomba tiap Sabtu). Kosong = semua hari dalam rentang.
            $table->json('hari_aktif')->nullable();
            // semua = seluruh kelas aktif, tingkat = per tingkat, kelas = pilih sendiri
            $table->string('cakupan', 20)->default('semua');
            $table->string('tingkat', 10)->nullable();
            $table->text('keterangan')->nullable();
            // Kalau dimatikan, siswa Alfa pada kegiatan ini TIDAK dikirimi
            // WhatsApp (mis. kegiatan opsional / di luar kewajiban hadir).
            $table->boolean('kirim_wa_alfa')->default(true);
            $table->boolean('is_aktif')->default(true);
            $table->foreignId('dibuat_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal_mulai', 'tanggal_selesai']);
            $table->index('tahun_ajaran_id');
        });

        // Dipakai hanya saat cakupan = 'kelas'.
        Schema::create('kegiatan_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_sekolah_id')->constrained('kegiatan_sekolahs')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['kegiatan_sekolah_id', 'kelas_id'], 'kegiatan_kelas_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_kelas');
        Schema::dropIfExists('kegiatan_sekolahs');
    }
};
