<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jam_pelajarans', function (Blueprint $table) {
            // Kolom hari ditambahkan dulu sebagai nullable agar tidak gagal jika
            // sudah ada data lama, lalu diisi default 'Senin' untuk baris lama.
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])
                ->nullable()
                ->after('id');
        });

        DB::table('jam_pelajarans')->whereNull('hari')->update(['hari' => 'Senin']);

        Schema::table('jam_pelajarans', function (Blueprint $table) {
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])->nullable(false)->change();

            // Hapus unique lama (hanya jam_ke) lalu buat unique baru (hari + jam_ke)
            $table->dropUnique(['jam_ke']);
            $table->unique(['hari', 'jam_ke'], 'jam_pelajarans_hari_jam_ke_unique');
        });
    }

    public function down(): void
    {
        Schema::table('jam_pelajarans', function (Blueprint $table) {
            $table->dropUnique('jam_pelajarans_hari_jam_ke_unique');
            $table->unique(['jam_ke']);
            $table->dropColumn('hari');
        });
    }
};
