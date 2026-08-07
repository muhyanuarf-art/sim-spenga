<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mapping: guru mengajar mapel apa di kelas apa (input Kurikulum, manual/import excel)
        Schema::create('guru_mengajar_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajarans')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['guru_id', 'kelas_id', 'mata_pelajaran_id', 'tahun_ajaran_id'], 'guru_kelas_mapel_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guru_mengajar_kelas');
    }
};
