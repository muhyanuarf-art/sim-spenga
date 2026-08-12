<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Perlebar dulu enum-nya (union nilai lama + 'Pembinaan') supaya
        //    'Pembinaan' jadi value yang valid sebelum dipakai di UPDATE di
        //    bawah ini — kalau langsung di-UPDATE sementara enum belum
        //    mengenal 'Pembinaan', MySQL men-truncate nilainya jadi '' dan
        //    gagal di strict mode ("Data truncated for column 'status'").
        DB::statement("ALTER TABLE pembinaan_siswas MODIFY status ENUM('Direncanakan','Berlangsung','Selesai','Tidak Berhasil','Pembinaan') NOT NULL DEFAULT 'Direncanakan'");

        // 2) Petakan data lama ke value baru.
        DB::table('pembinaan_siswas')
            ->whereIn('status', ['Direncanakan', 'Berlangsung', 'Tidak Berhasil'])
            ->update(['status' => 'Pembinaan']);

        // 3) Baru persempit enum ke 2 pilihan final.
        DB::statement("ALTER TABLE pembinaan_siswas MODIFY status ENUM('Pembinaan','Selesai') NOT NULL DEFAULT 'Pembinaan'");
    }

    public function down(): void
    {
        // Perlebar dulu supaya value lama valid lagi sebelum dipetakan balik.
        DB::statement("ALTER TABLE pembinaan_siswas MODIFY status ENUM('Direncanakan','Berlangsung','Selesai','Tidak Berhasil','Pembinaan') NOT NULL DEFAULT 'Direncanakan'");

        DB::table('pembinaan_siswas')
            ->where('status', 'Pembinaan')
            ->update(['status' => 'Berlangsung']);

        DB::statement("ALTER TABLE pembinaan_siswas MODIFY status ENUM('Direncanakan','Berlangsung','Selesai','Tidak Berhasil') NOT NULL DEFAULT 'Direncanakan'");
    }
};
