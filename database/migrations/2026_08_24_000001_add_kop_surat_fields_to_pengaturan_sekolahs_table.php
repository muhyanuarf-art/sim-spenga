<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahan untuk KOP Surat lengkap (dicetak saja, tidak tampil di layar
 * — lihat komponen App\Views\components\kop-surat.blade.php). Semua
 * kolom baru NULLABLE / opsional — kalau tidak diisi, baris terkait
 * di KOP Surat sederhananya tidak ditampilkan (bukan tampil kosong).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_sekolahs', function (Blueprint $table) {
            $table->string('pemerintah_daerah')->nullable()->after('nama_sekolah');
            $table->string('instansi_induk')->nullable()->after('pemerintah_daerah');
            $table->string('unit_kerja')->nullable()->after('instansi_induk');
            $table->string('kecamatan')->nullable()->after('nama_sekolah');
            $table->string('alamat_sekolah')->nullable()->after('kecamatan');
            $table->string('email_sekolah')->nullable()->after('alamat_sekolah');
            $table->string('logo_kiri_path')->nullable()->after('email_sekolah');
            $table->string('logo_kanan_path')->nullable()->after('logo_kiri_path');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_sekolahs', function (Blueprint $table) {
            $table->dropColumn([
                'pemerintah_daerah', 'instansi_induk', 'unit_kerja',
                'kecamatan', 'alamat_sekolah', 'email_sekolah',
                'logo_kiri_path', 'logo_kanan_path',
            ]);
        });
    }
};
