<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Peran baru: Wakil Kepala Sekolah.
 *
 * Haknya SAMA PERSIS dengan Kepala Sekolah — melihat dan mencetak
 * seluruh laporan, tanpa satu pun jalur tulis. Yang membedakan hanya
 * sebutannya di layar dan di daftar pengguna.
 *
 * Persamaan itu TIDAK dituliskan ulang di puluhan daftar peran yang
 * tersebar di rute, menu, dan controller. Cukup satu baris di
 * App\Models\User::PERAN_SETARA, dan seluruh pemeriksaan hak akses
 * membacanya lewat peranAkses(). Alasannya soal cara gagal: kalau
 * persamaan ini disalin ke ~20 daftar, satu daftar yang terlewat
 * menghasilkan 403 di satu halaman saja — galat yang hanya ketahuan
 * kalau ada yang kebetulan membuka halaman itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','kepala_sekolah','wakil_kepala_sekolah','kurikulum','guru','guru_bk','kesiswaan','tu') NOT NULL DEFAULT 'guru'");
    }

    public function down(): void
    {
        // Akun wakil diturunkan jadi 'guru' lebih dulu, supaya tidak ada
        // baris yang tersangkut pada nilai enum yang hendak dibuang.
        DB::table('users')->where('role', 'wakil_kepala_sekolah')->update(['role' => 'guru']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','kepala_sekolah','kurikulum','guru','guru_bk','kesiswaan','tu') NOT NULL DEFAULT 'guru'");
    }
};
