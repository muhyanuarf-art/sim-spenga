<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','kepala_sekolah','kurikulum','guru','guru_bk') NOT NULL DEFAULT 'guru'");
    }

    public function down(): void
    {
        // Ubah dulu user guru_bk (kalau ada) jadi 'guru' supaya tidak nyangkut
        // di value yang mau dihapus dari enum.
        DB::table('users')->where('role', 'guru_bk')->update(['role' => 'guru']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','kepala_sekolah','kurikulum','guru') NOT NULL DEFAULT 'guru'");
    }
};
