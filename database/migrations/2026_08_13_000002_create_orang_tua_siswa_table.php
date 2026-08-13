<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pivot akun Orang Tua (users.role = 'orang_tua') ⇄ Siswa.
     * 1 akun orang tua bisa ditautkan ke lebih dari 1 anak (kakak-adik
     * dalam satu sekolah), dan secara teknis 1 siswa juga bisa punya lebih
     * dari 1 akun penjemput/wali yang ditautkan (ayah & ibu login terpisah).
     */
    public function up(): void
    {
        Schema::create('orang_tua_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->string('hubungan')->nullable(); // mis. "Ayah", "Ibu", "Wali"
            $table->timestamps();

            $table->unique(['user_id', 'siswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orang_tua_siswa');
    }
};
