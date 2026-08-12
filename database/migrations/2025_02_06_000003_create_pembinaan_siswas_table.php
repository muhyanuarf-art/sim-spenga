<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembinaan_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('kasus_siswa_id')->nullable()->constrained('kasus_siswas')->nullOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();

            $table->date('tanggal');
            // Tahap TIDAK dihitung otomatis mutlak oleh sistem — BK yang
            // memilih (sistem hanya memberi REKOMENDASI berdasar poin aktif,
            // lihat PoinSiswaService::rekomendasiTahap()). Prinsip Bagian 16.
            $table->unsignedTinyInteger('tahap');
            $table->enum('jenis_pembinaan', [
                'Teguran lisan', 'Teguran tertulis', 'Penugasan edukatif',
                'Konseling individu', 'Kontrak perilaku', 'Pemanggilan orang tua',
                'Pembinaan khusus', 'Ruang refleksi', 'Skorsing edukatif', 'Pembinaan lanjutan',
            ]);
            $table->text('catatan_bk');
            $table->text('hasil_pembinaan')->nullable();
            $table->enum('status', ['Direncanakan', 'Berlangsung', 'Selesai', 'Tidak Berhasil'])->default('Direncanakan');
            $table->date('tanggal_evaluasi_berikutnya')->nullable();

            // Khusus jenis_pembinaan = 'Ruang refleksi' (Tahap 5, Bagian 19)
            $table->date('ruang_refleksi_selesai')->nullable();

            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['siswa_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembinaan_siswas');
    }
};
