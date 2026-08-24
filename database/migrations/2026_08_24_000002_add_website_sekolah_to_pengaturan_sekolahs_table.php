<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahan untuk KOP Surat — baris "Website : ... Email : ..." (satu
 * baris, dua-duanya opsional, lihat komponen kop-surat.blade.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_sekolahs', function (Blueprint $table) {
            $table->string('website_sekolah')->nullable()->after('alamat_sekolah');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_sekolahs', function (Blueprint $table) {
            $table->dropColumn('website_sekolah');
        });
    }
};
