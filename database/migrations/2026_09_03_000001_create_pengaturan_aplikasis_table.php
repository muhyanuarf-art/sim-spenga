<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PENYIMPANAN KUNCI–NILAI KECIL MILIK PEMASANGAN.
 *
 * Dibuat untuk menampung hal-hal yang harus bertahan selama pemasangan
 * ini hidup, ikut terbawa saat database dipulihkan dari backup, dan
 * tidak masuk akal ditaruh di .env karena bukan setelan yang diketik
 * manusia.
 *
 * Penghuni pertamanya:
 *   instalasi_id  — nilai acak pengenal pemasangan (App\Support\SidikInstalasi)
 *   surat_lisensi — surat aktivasi terakhir dari server FF Production
 *   surat_diperiksa_at — kapan terakhir berhasil menghubungi server
 *
 * Sengaja TIDAK memakai tabel `lisensi_aplikasis` yang sudah ada: tabel
 * itu bentuknya khusus untuk aktivasi cara lama (sidik nomor seri +
 * tanda tangan APP_KEY), dan keduanya akan hidup berdampingan selama
 * masa peralihan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_aplikasis', function (Blueprint $table) {
            $table->id();
            $table->string('kunci')->unique();

            // longText: surat lisensi bertanda tangan bisa beberapa ratus
            // karakter, dan kolom ini sengaja dibuat lapang supaya penghuni
            // berikutnya tidak menuntut migrasi baru.
            $table->longText('nilai')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_aplikasis');
    }
};
