<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Role baru: TU (Tata Usaha) — akses ke Manajemen Surat (sama seperti
 * Kesiswaan/Guru BK: kelola Jenis Surat, buat/edit/hapus Surat, kirim
 * disposisi), TANPA akses ke modul BK/Kesiswaan lainnya (Kasus,
 * Pembinaan, Ekstrakurikuler, dst — itu tetap khusus role masing-masing).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','kepala_sekolah','kurikulum','guru','guru_bk','kesiswaan','tu') NOT NULL DEFAULT 'guru'");
    }

    public function down(): void
    {
        // Ubah dulu user TU (kalau ada) jadi 'guru' supaya tidak nyangkut
        // di value yang mau dihapus dari enum.
        DB::table('users')->where('role', 'tu')->update(['role' => 'guru']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','kepala_sekolah','kurikulum','guru','guru_bk','kesiswaan') NOT NULL DEFAULT 'guru'");
    }
};
