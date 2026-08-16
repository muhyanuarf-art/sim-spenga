<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom kunci periode ke tabel tahun_ajarans yang sudah ada
     * (aditif, tanpa split tabel). Periode yang dikunci memblokir aksi
     * TULIS (jurnal, absensi, modul BK) selama periode tsb yang AKTIF —
     * lihat App\Http\Middleware\EnsurePeriodeTidakTerkunci.
     */
    public function up(): void
    {
        Schema::table('tahun_ajarans', function (Blueprint $table) {
            $table->boolean('terkunci')->default(false)->after('is_active');
            $table->timestamp('terkunci_at')->nullable()->after('terkunci');
            $table->foreignId('terkunci_oleh_id')->nullable()->after('terkunci_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tahun_ajarans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('terkunci_oleh_id');
            $table->dropColumn(['terkunci', 'terkunci_at']);
        });
    }
};
