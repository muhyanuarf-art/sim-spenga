<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Evaluasi harian untuk pembinaan jenis "Ruang refleksi" (maks 7 hari, Bagian 19). */
    public function up(): void
    {
        Schema::create('evaluasi_pembinaans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembinaan_siswa_id')->constrained('pembinaan_siswas')->cascadeOnDelete();
            $table->unsignedTinyInteger('hari_ke');
            $table->date('tanggal');
            $table->enum('kondisi', ['Baik', 'Perlu Perhatian', 'Kurang Baik']);
            $table->text('catatan');
            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['pembinaan_siswa_id', 'hari_ke']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasi_pembinaans');
    }
};
