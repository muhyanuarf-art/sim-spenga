<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','kepala_sekolah','kurikulum','guru','guru_bk','orang_tua') NOT NULL DEFAULT 'guru'");
    }

    public function down(): void
    {
        // Ubah dulu user orang_tua (kalau ada) jadi 'guru' supaya tidak nyangkut
        // di value yang mau dihapus dari enum.
        DB::table('users')->where('role', 'orang_tua')->update(['role' => 'guru']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','kepala_sekolah','kurikulum','guru','guru_bk') NOT NULL DEFAULT 'guru'");
    }
};
