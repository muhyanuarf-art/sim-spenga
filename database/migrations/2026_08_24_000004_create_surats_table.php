<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arsip surat — 1 baris = 1 surat yang sudah dibuat (dari Kesiswaan
 * ATAU BK, keduanya tercatat di tabel yang sama supaya saling terlihat).
 * `isi` menyimpan hasil MERGE template (bukan template mentahnya lagi) —
 * jadi kalau template jenis surat diedit di kemudian hari, surat yang
 * sudah dibuat sebelumnya tidak ikut berubah (snapshot, sesuai prinsip
 * "riwayat tidak berubah" yang dipakai di seluruh sistem).
 *
 * FK `jenis_surat_id` & `siswa_id` SENGAJA tidak cascade (default
 * restrict) — konsisten dengan perbaikan bug cascade-delete ekstrakurikuler
 * sebelumnya: kejadian menghapus master data seharusnya tidak diam-diam
 * menghapus riwayat surat yang sudah terbit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_surat_id')->constrained('jenis_surats');
            $table->foreignId('siswa_id')->constrained('siswas');
            $table->string('nomor_surat')->nullable();
            $table->date('tanggal');
            $table->longText('isi');
            $table->text('keterangan')->nullable();
            $table->foreignId('dibuat_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surats');
    }
};
