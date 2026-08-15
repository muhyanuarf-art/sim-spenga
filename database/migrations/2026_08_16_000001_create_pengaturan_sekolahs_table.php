<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel ini sengaja dirancang sebagai SINGLETON (hanya akan ada 1 baris,
        // id = 1) — bukan resource CRUD biasa. Lihat App\Models\PengaturanSekolah::current().
        Schema::create('pengaturan_sekolahs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sekolah')->nullable();
            $table->string('kabupaten_kota')->default('Bumiayu');
            $table->string('provinsi')->default('Jawa Tengah');
            $table->string('nama_kepala_sekolah')->nullable();
            $table->string('nip_kepala_sekolah')->nullable();
            // Override teks lokasi khusus untuk baris tanda tangan (mis. "Kota Bumiayu").
            // Kalau dikosongkan, otomatis pakai isi kabupaten_kota.
            $table->string('format_lokasi_ttd')->nullable();
            $table->timestamps();
        });

        DB::table('pengaturan_sekolahs')->insert([
            'kabupaten_kota' => 'Bumiayu',
            'provinsi' => 'Jawa Tengah',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_sekolahs');
    }
};
