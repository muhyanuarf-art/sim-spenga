<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anggota (siswa) tiap kegiatan ekstrakurikuler. Sengaja TIDAK terikat
 * kelas atau tahun ajaran di tabel ini — siswa lintas kelas/angkatan bisa
 * jadi anggota kegiatan yang sama, dan 1 siswa boleh ikut lebih dari 1
 * kegiatan (unique-nya per pasangan ekskul+siswa, bukan per siswa).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekstrakurikuler_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ekstrakurikuler_id')->constrained('ekstrakurikulers')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->date('tanggal_gabung')->nullable();
            $table->timestamps();

            $table->unique(['ekstrakurikuler_id', 'siswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekstrakurikuler_siswas');
    }
};
