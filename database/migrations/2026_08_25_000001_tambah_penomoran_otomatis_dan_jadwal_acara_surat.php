<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah:
 * - jenis_surats.kode_jenis   : singkatan jenis surat untuk format nomor
 *                                otomatis (mis. "SP" untuk Surat Panggilan).
 * - surats.nomor_urut         : nomor urut (per jenis surat, per tahun)
 *                                yang dipakai membentuk nomor_surat otomatis.
 * - surats.tanggal_acara      : tanggal acara/pemanggilan yang dimaksud di
 *                                dalam isi surat — TERPISAH dari `tanggal`
 *                                (tanggal surat itu sendiri diterbitkan).
 * - surats.waktu_acara        : jam acara/pemanggilan (format "HH:MM").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_surats', function (Blueprint $table) {
            $table->string('kode_jenis', 10)->nullable()->after('nama_jenis');
        });

        Schema::table('surats', function (Blueprint $table) {
            $table->unsignedInteger('nomor_urut')->nullable()->after('nomor_surat');
            $table->date('tanggal_acara')->nullable()->after('tanggal');
            $table->string('waktu_acara', 5)->nullable()->after('tanggal_acara');
        });
    }

    public function down(): void
    {
        Schema::table('jenis_surats', function (Blueprint $table) {
            $table->dropColumn('kode_jenis');
        });

        Schema::table('surats', function (Blueprint $table) {
            $table->dropColumn(['nomor_urut', 'tanggal_acara', 'waktu_acara']);
        });
    }
};
