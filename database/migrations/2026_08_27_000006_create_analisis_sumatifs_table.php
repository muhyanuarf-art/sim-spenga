<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ANALISIS HASIL TES SUMATIF LINGKUP MATERI.
 *
 * Lembar analisis butir soal yang dibuat guru mata pelajaran SETELAH
 * melaksanakan tes sumatif lingkup materi (ulangan harian). Satu lembar =
 * satu Lingkup Materi pada satu kelas × mapel × periode — jadi kalau guru
 * sudah mengisi nilai sampai Sumatif ke-3, otomatis ada 3 lembar analisis.
 *
 * KENAPA TABEL INI HANYA BERISI SEDIKIT KOLOM
 * ============================================
 * Nilai per butir soal (nomor 1–20) SENGAJA TIDAK disimpan di database.
 * Angka-angka itu diturunkan (dihitung ulang) dari nilai SUM yang sudah
 * ada di `nilai_siswas.sumatif_lm`, memakai sebaran yang deterministik —
 * lihat App\Support\AnalisisButirSoal. Alasannya:
 *
 * 1. Jumlah skor tiap siswa WAJIB sama persis dengan nilai sumatif yang
 *    sudah diinput di Daftar Nilai (ini permintaan eksplisit sekolah).
 *    Kalau skor butir disimpan terpisah, keduanya bisa berbeda begitu
 *    guru mengoreksi nilai di Daftar Nilai — dan dokumen jadi
 *    bertentangan dengan rapor.
 * 2. Karena diturunkan, koreksi nilai di Daftar Nilai otomatis
 *    tercermin di lembar analisis tanpa perlu diisi ulang.
 *
 * Yang BENAR-BENAR perlu disimpan hanyalah keterangan yang tidak bisa
 * disimpulkan dari nilai: Materi Ajar (diketik guru), banyaknya butir
 * soal, dan tanggal pelaksanaan tes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analisis_sumatifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajarans')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();
            // Lingkup Materi ke berapa (1..jumlah_lm pada pengaturan penilaian).
            $table->unsignedTinyInteger('lingkup_materi');

            // Diisi sendiri oleh guru mata pelajaran — dikosongkan by default.
            $table->string('materi_ajar')->nullable();
            $table->unsignedTinyInteger('jumlah_soal')->default(20);
            $table->date('tanggal_pelaksanaan')->nullable();

            $table->foreignId('diperbarui_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['kelas_id', 'mata_pelajaran_id', 'tahun_ajaran_id', 'lingkup_materi'],
                'analisis_sumatif_unik'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analisis_sumatifs');
    }
};
