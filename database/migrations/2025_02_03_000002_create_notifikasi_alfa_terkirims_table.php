<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel kecil untuk mencegah notifikasi WA Alfa terkirim DOBEL ke orang
     * tua yang sama pada hari yang sama — misal kalau guru mengedit ulang
     * jurnal/absensi yang sama, atau ada lebih dari 1 guru mapel yang
     * menyimpan absensi hari itu.
     */
    public function up(): void
    {
        Schema::create('notifikasi_alfa_terkirims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->date('tanggal');
            $table->timestamp('dikirim_at')->nullable();
            $table->string('status_kirim')->default('pending'); // pending, terkirim, gagal
            $table->timestamps();

            $table->unique(['siswa_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_alfa_terkirims');
    }
};
