<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perbaikan performa halaman "Status WhatsApp Ortu" (menu Laporan).
 *
 * Tabel ini sebelumnya hanya punya unique(siswa_id, tanggal) — index
 * KOMPOSIT yang kolom pertamanya siswa_id, jadi TIDAK BISA dipakai untuk
 * query yang memfilter tanggal saja tanpa siswa_id (persis pola yang
 * dipakai NotifikasiWhatsappController::index() — filter 1 bulan lintas
 * SEMUA siswa). Akibatnya tiap buka halaman ini, MySQL scan SELURUH
 * tabel. Index baru ini (kolom `tanggal` sendiri) membuat filter rentang
 * tanggal bisa dipakai langsung tanpa full table scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifikasi_alfa_terkirims', function (Blueprint $table) {
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('notifikasi_alfa_terkirims', function (Blueprint $table) {
            $table->dropIndex(['tanggal']);
        });
    }
};
