<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Petakan dulu data lama supaya tidak ada baris jadi tidak valid
        // sesaat sebelum enum diubah.
        DB::table('pembinaan_siswas')
            ->whereIn('status', ['Direncanakan', 'Berlangsung', 'Tidak Berhasil'])
            ->update(['status' => 'Pembinaan']);

        DB::statement("ALTER TABLE pembinaan_siswas MODIFY status ENUM('Pembinaan','Selesai') NOT NULL DEFAULT 'Pembinaan'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE pembinaan_siswas MODIFY status ENUM('Direncanakan','Berlangsung','Selesai','Tidak Berhasil') NOT NULL DEFAULT 'Direncanakan'");
    }
};
