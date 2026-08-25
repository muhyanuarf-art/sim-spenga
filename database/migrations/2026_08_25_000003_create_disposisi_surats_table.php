<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Disposisi surat — alur kirim/teruskan surat antar user (Kepala
 * Sekolah -> Kesiswaan -> BK -> Wali Kelas, dst, bebas siapa ke siapa).
 * 1 surat bisa punya banyak baris disposisi (riwayat penerusan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposisi_surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_id')->constrained('surats')->cascadeOnDelete();
            $table->foreignId('dari_user_id')->constrained('users');
            $table->foreignId('kepada_user_id')->constrained('users');
            $table->text('instruksi')->nullable();
            $table->date('batas_waktu')->nullable();
            $table->enum('status', ['menunggu', 'dibaca', 'diproses', 'selesai', 'ditolak'])->default('menunggu');
            $table->text('catatan_penyelesaian')->nullable();
            $table->timestamp('dibaca_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposisi_surats');
    }
};
