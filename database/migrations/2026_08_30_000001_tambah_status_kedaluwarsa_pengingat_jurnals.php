<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MENAMBAH STATUS 'kedaluwarsa' PADA `pengingat_jurnals`.
 *
 * Sebelum ini ada empat status: pending, terkirim, gagal, dilewati.
 * 'dilewati' dipakai saat guru keburu mengisi jurnalnya sebelum pesan
 * sempat dikirim — kabar baik, dan tidak perlu ditindaklanjuti siapa pun.
 *
 * Sekarang ada sebab KEDUA mengapa sebuah pengingat tidak jadi dikirim:
 * hari mengajarnya sudah lewat sebelum pesannya sempat keluar. Itu BUKAN
 * kabar baik — artinya ada yang tidak beres di server (pekerja antrian
 * mati, atau menumpuk terlalu lama), dan jurnalnya kemungkinan besar
 * memang belum terisi.
 *
 * Keduanya sengaja dibedakan supaya jumlah di halaman Pengaturan bisa
 * dibaca apa adanya: 'dilewati' yang banyak berarti guru rajin, sedangkan
 * 'kedaluwarsa' yang banyak berarti Admin perlu memeriksa servernya.
 * Kalau keduanya digabung, sinyal itu hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE `pengingat_jurnals`
             MODIFY `status_kirim`
             ENUM('pending','terkirim','gagal','dilewati','kedaluwarsa')
             NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        // Baris yang terlanjur berstatus 'kedaluwarsa' dikembalikan menjadi
        // 'dilewati' lebih dulu — tanpa ini, ALTER-nya akan menolak atau
        // mengosongkan nilainya.
        DB::table('pengingat_jurnals')
            ->where('status_kirim', 'kedaluwarsa')
            ->update(['status_kirim' => 'dilewati']);

        DB::statement(
            "ALTER TABLE `pengingat_jurnals`
             MODIFY `status_kirim`
             ENUM('pending','terkirim','gagal','dilewati')
             NOT NULL DEFAULT 'pending'"
        );
    }
};
