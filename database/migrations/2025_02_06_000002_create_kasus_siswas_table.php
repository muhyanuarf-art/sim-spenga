<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat kasus/pelanggaran siswa — TRANSAKSI POIN (+). Baris di sini
     * TIDAK PERNAH dihapus (prinsip Bagian 29 spec). Kalau ada kesalahan
     * input, gunakan mekanisme pembatalan (dibatalkan_at dkk) — bukan
     * delete — supaya riwayat & audit trail tetap utuh.
     *
     * kategori & poin di-SNAPSHOT ke tabel ini (bukan hanya join ke
     * jenis_pelanggarans) supaya kalau master datanya nanti diubah,
     * riwayat historis tidak ikut berubah.
     */
    public function up(): void
    {
        Schema::create('kasus_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete(); // snapshot kelas saat kejadian
            $table->foreignId('jenis_pelanggaran_id')->nullable()->constrained('jenis_pelanggarans')->nullOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();

            $table->date('tanggal_kejadian');
            $table->string('nama_pelanggaran'); // snapshot nama, tetap terbaca walau master diubah/nonaktif
            $table->enum('kategori', ['Ringan', 'Sedang', 'Berat', 'Sangat Berat']);
            $table->unsignedInteger('poin');
            $table->text('kronologi');
            $table->foreignId('guru_pelapor_id')->constrained('users')->cascadeOnDelete();
            $table->text('bukti_catatan')->nullable();

            $table->enum('status', ['Baru', 'Diproses', 'Dalam Pembinaan', 'Selesai'])->default('Baru');

            // Mekanisme koreksi TANPA hapus (Bagian 21 & 29 spec).
            $table->timestamp('dibatalkan_at')->nullable();
            $table->foreignId('dibatalkan_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('alasan_pembatalan')->nullable();

            $table->timestamps();

            $table->index(['siswa_id', 'tanggal_kejadian']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kasus_siswas');
    }
};
