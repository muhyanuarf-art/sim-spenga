<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemanggilan_orangtuas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('kasus_siswa_id')->nullable()->constrained('kasus_siswas')->nullOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();

            $table->date('tanggal');
            $table->text('alasan');
            $table->boolean('ortu_hadir')->default(false);
            $table->text('hasil_pertemuan')->nullable();
            $table->text('kesepakatan')->nullable();
            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['siswa_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemanggilan_orangtuas');
    }
};
