<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat mutasi kelas siswa per tahun ajaran (hasil proses Kenaikan
     * Kelas). Tabel baru, aditif — tidak menyentuh tabel lain. Baris di
     * sini adalah snapshot histori (kelas_asal_id & kelas_id) sehingga
     * kalau data kelas induk berubah di kemudian hari, riwayat tetap utuh
     * (kolom FK tetap ada untuk drill-down, tapi nama kelas selalu bisa
     * dibaca dari relasi yang masih ada).
     *
     * unique(siswa_id, tahun_ajaran_id) mencegah satu siswa dicatat naik
     * kelas dua kali pada tahun ajaran yang sama.
     */
    public function up(): void
    {
        Schema::create('riwayat_kelas_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();

            $table->foreignId('kelas_asal_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete(); // kelas tujuan/hasil

            $table->text('keterangan')->nullable();
            $table->foreignId('dicatat_oleh_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['siswa_id', 'tahun_ajaran_id'], 'riwayat_kelas_unique_siswa_tahun');
            $table->index(['siswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kelas_siswas');
    }
};
