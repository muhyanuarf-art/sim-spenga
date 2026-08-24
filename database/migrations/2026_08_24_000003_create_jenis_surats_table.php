<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master "Jenis Surat" — dipakai BERSAMA oleh Kesiswaan & BK (mis. Surat
 * Panggilan Orang Tua, Surat Peringatan, Surat Keterangan). Tiap jenis
 * punya template isi dengan placeholder yang otomatis terisi saat surat
 * dibuat (lihat App\Support\SuratMergeField dan SuratController::mergeTemplate()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_surats', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jenis');
            $table->text('template_isi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_surats');
    }
};
