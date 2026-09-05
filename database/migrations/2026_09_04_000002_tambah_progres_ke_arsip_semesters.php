<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PENANDA KEMAJUAN PEMBUATAN ARSIP.
 *
 * =====================================================================
 * KENAPA PERLU
 * =====================================================================
 * Membuat arsip memakan belasan detik sampai beberapa menit — merender
 * belasan laporan lewat peramban satu per satu. Sebelum ini, yang
 * dilihat Admin hanyalah tulisan "Arsip dibuat…" yang diam saja, tanpa
 * petunjuk apakah prosesnya berjalan, macet, atau sudah mati.
 *
 * Ketidakpastian itu berbahaya: Admin akan menekan tombolnya berkali-kali
 * karena mengira tidak terjadi apa-apa, atau menutup halaman lalu
 * mengira fiturnya rusak.
 *
 * Dua kolom ini membuat prosesnya terlihat: berapa persen sudah selesai,
 * dan laporan mana yang sedang dikerjakan sekarang.
 *
 * Disimpan di DATABASE, bukan di cache atau sesi, karena yang menulisnya
 * adalah pekerja antrian — proses yang sama sekali terpisah dari
 * peramban Admin. Database adalah satu-satunya tempat yang bisa dilihat
 * keduanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arsip_semesters', function (Blueprint $table) {
            $table->unsignedTinyInteger('progres')->default(0)->after('status');
            $table->string('langkah')->nullable()->after('progres')
                ->comment('laporan yang sedang dikerjakan, ditampilkan ke Admin');
        });
    }

    public function down(): void
    {
        Schema::table('arsip_semesters', function (Blueprint $table) {
            $table->dropColumn(['progres', 'langkah']);
        });
    }
};
