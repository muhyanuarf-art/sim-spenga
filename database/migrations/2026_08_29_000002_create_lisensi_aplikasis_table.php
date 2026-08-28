<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CATATAN AKTIVASI LISENSI.
 *
 * Satu baris saja untuk seluruh aplikasi. Isinya bukan nomor serinya,
 * melainkan bukti bahwa nomor seri yang benar pernah dimasukkan di server
 * ini: sidiknya, kapan diaktifkan, oleh siapa, dan di alamat mana.
 *
 * `tanda_tangan` mengikat ketiganya dengan APP_KEY instalasi ini, sehingga
 * baris aktivasi tidak bisa dipindah-tempel begitu saja ke database lain —
 * di sana APP_KEY-nya berbeda dan tanda tangannya tidak akan cocok.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lisensi_aplikasis', function (Blueprint $t) {
            $t->id();
            $t->string('kunci_hash', 64);
            $t->string('host')->nullable();
            $t->string('tanda_tangan', 64);
            $t->timestamp('diaktifkan_at')->nullable();
            $t->string('diaktifkan_oleh')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lisensi_aplikasis');
    }
};
