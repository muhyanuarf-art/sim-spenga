<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * STEP 5 — wali_kelas_histori (dibuat di STEP 4) sekarang REDUNDAN dan
 * DIHAPUS: sejak `kelas` punya tahun_ajaran_id sendiri (migrasi
 * 2026_08_20_000005), `kelas.wali_kelas_id` SUDAH otomatis terpisah per
 * tahun ajaran (setiap tahun ajaran punya baris kelas sendiri-sendiri) —
 * tidak perlu lagi tabel tambahan untuk mencatat "wali kelas per tahun".
 * Ini konsisten dengan prinsip yang dipegang sejak STEP 1: "jangan
 * menyimpan dua status yang artinya sama".
 *
 * AMAN dihapus: tabel ini baru dibuat 1 langkah sebelumnya (STEP 4),
 * tidak ada tabel lain yang mereferensikannya sebagai foreign key, dan
 * datanya (snapshot wali kelas per tahun ajaran) SUDAH SEPENUHNYA
 * digantikan oleh kelas.wali_kelas_id pada baris kelas per-tahun yang
 * baru (lihat migrasi 2026_08_20_000005 — backfill di migrasi itu
 * memakai wali_kelas_id yang sudah ada di kelas, sumber yang sama
 * persis dengan yang dulu dipakai wali_kelas_histori).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('wali_kelas_histori');
    }

    public function down(): void
    {
        // Sengaja tidak dibuat ulang — fitur ini digantikan sepenuhnya
        // oleh struktur kelas per-tahun-ajaran. Kalau rollback STEP 5
        // diperlukan, jalankan migrasi 2026_08_20_000004 versi lama secara
        // manual untuk membuat ulang tabelnya.
    }
};
