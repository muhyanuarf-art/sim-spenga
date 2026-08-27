<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PROGRAM PENGAYAAN DAN PERBAIKAN.
 *
 * Dokumen lanjutan dari Analisis Hasil Tes Sumatif Lingkup Materi: siapa
 * yang harus mengikuti perbaikan (remedial), siapa yang berhak pengayaan,
 * dan bagaimana rencana pelaksanaannya.
 *
 * KENAPA MENUMPANG DI TABEL analisis_sumatifs, BUKAN TABEL BARU
 * =============================================================
 * Identitasnya SAMA PERSIS: satu program = satu (kelas x mapel x periode
 * x lingkup materi), kunci unik yang sama dengan lembar analisis. Dokumen
 * programnya juga memakai ulang kepala lembar analisis apa adanya (Materi
 * Ajar, Banyak Soal, Tanggal Pelaksanaan). Membuat tabel kedua dengan
 * kunci unik yang identik hanya akan menambah satu join tanpa memberi
 * manfaat apa pun.
 *
 * Sama seperti lembar analisis, DAFTAR PESERTA program ini tidak disimpan
 * — selalu diturunkan ulang dari nilai SUM di `nilai_siswas` (siapa di
 * bawah KKTP = perbaikan, siapa di atasnya = pengayaan) dan dari butir
 * soal yang belum dikuasai (App\Support\AnalisisButirSoal). Jadi kalau
 * guru mengoreksi nilai di Daftar Nilai, daftar pesertanya ikut berubah
 * sendiri dan tidak pernah basi.
 *
 * Yang benar-benar perlu disimpan hanya rencana yang diketik guru:
 * bentuk kegiatan dan tanggal pelaksanaannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analisis_sumatifs', function (Blueprint $table) {
            $table->text('bentuk_perbaikan')->nullable()->after('tanggal_pelaksanaan');
            $table->date('tanggal_perbaikan')->nullable()->after('bentuk_perbaikan');
            $table->text('bentuk_pengayaan')->nullable()->after('tanggal_perbaikan');
            $table->date('tanggal_pengayaan')->nullable()->after('bentuk_pengayaan');
        });
    }

    public function down(): void
    {
        Schema::table('analisis_sumatifs', function (Blueprint $table) {
            $table->dropColumn([
                'bentuk_perbaikan', 'tanggal_perbaikan',
                'bentuk_pengayaan', 'tanggal_pengayaan',
            ]);
        });
    }
};
