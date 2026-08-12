<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master jenis pelanggaran — configurable oleh Guru BK/Admin, TIDAK
     * hardcode di controller. kategori menentukan rentang poin yang valid
     * (divalidasi di form request, lihat App\Support\BkPoinRange).
     */
    public function up(): void
    {
        Schema::create('jenis_pelanggarans', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->enum('kategori', ['Ringan', 'Sedang', 'Berat', 'Sangat Berat']);
            $table->unsignedInteger('poin_default');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_pelanggarans');
    }
};
