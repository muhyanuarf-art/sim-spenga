<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Kepala" absensi kegiatan — setara JurnalMengajar untuk KBM, tapi
 * per KEGIATAN + KELAS + TANGGAL, dan diisi oleh WALI KELAS.
 *
 * Rincian per siswanya tetap disimpan di tabel absensi_siswas yang sudah
 * ada (lihat migrasi berikutnya), supaya seluruh laporan yang sudah
 * berjalan — Rekap Absensi Kelas, Rekapitulasi, dashboard, portal orang
 * tua, dan notifikasi WhatsApp Alfa — otomatis ikut menghitung kehadiran
 * pada hari kegiatan tanpa perlu ditulis ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_kegiatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_sekolah_id')->constrained('kegiatan_sekolahs')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->date('tanggal');
            $table->foreignId('diisi_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->unsignedInteger('jumlah_hadir')->default(0);
            $table->unsignedInteger('jumlah_sakit')->default(0);
            $table->unsignedInteger('jumlah_izin')->default(0);
            $table->unsignedInteger('jumlah_alfa')->default(0);
            $table->timestamps();

            $table->unique(['kegiatan_sekolah_id', 'kelas_id', 'tanggal'], 'absensi_kegiatan_unik');
            $table->index(['kelas_id', 'tanggal']);
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_kegiatans');
    }
};
