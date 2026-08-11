<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mapping: Guru BK memantau kelas apa saja (input Kurikulum/Admin).
     * Beda dengan guru_mengajar_kelas — tidak ada mata_pelajaran_id, karena
     * Guru BK tidak mengajar mapel, hanya memantau absensi/kehadiran siswa
     * di kelas-kelas yang jadi tanggung jawabnya.
     */
    public function up(): void
    {
        Schema::create('guru_bk_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['guru_id', 'kelas_id', 'tahun_ajaran_id'], 'guru_bk_kelas_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guru_bk_kelas');
    }
};
