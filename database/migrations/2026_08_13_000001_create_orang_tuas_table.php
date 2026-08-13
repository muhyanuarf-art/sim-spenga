<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel akun login orang tua/wali siswa — TERPISAH dari tabel `users`
     * (users khusus staf sekolah: admin, guru, kurikulum, dst).
     *
     * `nis` didenormalisasi dari `siswas.nis` supaya orang tua bisa login
     * langsung pakai NIS anaknya sebagai username (Auth::attempt hanya bisa
     * mencocokkan kolom yang ada di tabel guard-nya sendiri). Nilainya
     * disinkronkan otomatis oleh OrangTuaImport setiap kali import dijalankan.
     */
    public function up(): void
    {
        Schema::create('orang_tuas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->unique()->constrained('siswas')->cascadeOnDelete();
            $table->string('nis')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('password_diubah_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orang_tuas');
    }
};
