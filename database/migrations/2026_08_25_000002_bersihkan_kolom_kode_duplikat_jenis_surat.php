<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pembersihan — migrasi tahap 1 saya (`2026_08_24_000005`) menambah
 * `jenis_surats.kode`, tapi pengembangan lanjutan (`2026_08_25_000001`,
 * di luar sesi ini) menambah `jenis_surats.kode_jenis` untuk keperluan
 * yang sama (kode dipakai format Nomor Surat otomatis) — dan `kode_jenis`
 * itu yang dipakai di seluruh kode (NomorSurat, view). Kolom `kode` saya
 * jadi duplikat tak terpakai. Dihapus supaya tidak membingungkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_surats', function (Blueprint $table) {
            $table->dropColumn('kode');
        });
    }

    public function down(): void
    {
        Schema::table('jenis_surats', function (Blueprint $table) {
            $table->string('kode')->nullable()->after('nama_jenis');
        });
    }
};
