<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jurnal mengajar guru, diisi guru mapel setiap kali selesai mengajar
        Schema::create('jurnal_mengajars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_pelajaran_id')->nullable()->constrained('jadwal_pelajarans')->nullOnDelete();
            $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajarans')->cascadeOnDelete();
            $table->foreignId('jam_pelajaran_id')->constrained('jam_pelajarans')->cascadeOnDelete();
            $table->date('tanggal');
            $table->text('materi');
            $table->text('kegiatan')->nullable();
            $table->unsignedSmallInteger('jumlah_hadir')->default(0);
            $table->unsignedSmallInteger('jumlah_sakit')->default(0);
            $table->unsignedSmallInteger('jumlah_izin')->default(0);
            $table->unsignedSmallInteger('jumlah_alfa')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->unique(['jadwal_pelajaran_id', 'tanggal'], 'jurnal_unique_slot_tanggal');
            $table->index(['kelas_id', 'tanggal']);
            $table->index(['guru_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_mengajars');
    }
};
