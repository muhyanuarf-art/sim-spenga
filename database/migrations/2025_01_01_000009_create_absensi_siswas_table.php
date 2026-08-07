<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Absensi per siswa per pertemuan (jam pelajaran, tanggal, mapel)
        Schema::create('absensi_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurnal_mengajar_id')->constrained('jurnal_mengajars')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('status', ['Hadir', 'Sakit', 'Izin', 'Alfa'])->default('Hadir');
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->unique(['jurnal_mengajar_id', 'siswa_id'], 'absensi_unique_jurnal_siswa');
            $table->index(['siswa_id', 'tanggal']);
            $table->index(['kelas_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_siswas');
    }
};
