<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Bersihkan dulu baris users.role yang nilainya BUKAN salah satu
        // role yang akan diizinkan di bawah. Ini menangani sisa data lama
        // (mis. role 'orang_tua' dari migration yang sudah dihapus dari
        // kode, tapi datanya di database belum ikut dibersihkan) — MySQL
        // menolak ALTER ENUM kalau masih ada baris memakai value yang mau
        // dibuang dari daftar (error 1265: Data truncated).
        DB::table('users')
            ->whereNotIn('role', ['admin', 'kepala_sekolah', 'kurikulum', 'guru', 'guru_bk', 'kesiswaan'])
            ->update(['role' => 'guru']);

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','kepala_sekolah','kurikulum','guru','guru_bk','kesiswaan') NOT NULL DEFAULT 'guru'");
    }

    public function down(): void
    {
        // Ubah dulu user kesiswaan (kalau ada) jadi 'guru' supaya tidak nyangkut
        // di value yang mau dihapus dari enum.
        DB::table('users')->where('role', 'kesiswaan')->update(['role' => 'guru']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','kepala_sekolah','kurikulum','guru','guru_bk') NOT NULL DEFAULT 'guru'");
    }
};
