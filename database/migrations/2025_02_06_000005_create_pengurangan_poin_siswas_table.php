<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TRANSAKSI POIN (-). Sama seperti kasus_siswas: TIDAK PERNAH dihapus,
     * cuma bisa dibatalkan (dibatalkan_at dkk) kalau salah input.
     */
    public function up(): void
    {
        Schema::create('pengurangan_poin_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();

            $table->date('tanggal');
            $table->unsignedInteger('jumlah');
            $table->text('alasan');
            $table->text('dasar_rekomendasi')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();

            $table->timestamp('dibatalkan_at')->nullable();
            $table->foreignId('dibatalkan_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('alasan_pembatalan')->nullable();

            $table->timestamps();

            $table->index(['siswa_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengurangan_poin_siswas');
    }
};
