<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ARSIP SEMESTER — seluruh laporan satu semester dalam satu berkas ZIP.
 *
 * =====================================================================
 * MASALAH YANG DIPERBAIKI
 * =====================================================================
 * Setiap peran punya laporan yang harus dicetak di akhir semester —
 * daftar nilai, rekap absensi, jurnal, catatan BK, prestasi. Selama ini
 * pencetakannya bergantung pada seseorang ingat melakukannya satu per
 * satu, per kelas, per mapel. Yang terlupa baru ketahuan bertahun
 * kemudian, saat asesor akreditasi memintanya.
 *
 * Arsip ini membuatnya sekali jalan: satu tombol, satu ZIP berisi semua.
 *
 * =====================================================================
 * KENAPA PDF, PADAHAL SUDAH ADA BACKUP DATABASE
 * =====================================================================
 * Backup database melindungi datanya; arsip PDF melindungi
 * KETERBACAANNYA. Sepuluh tahun lagi — saat aplikasi ini tidak dipakai
 * lagi, atau sekolah berhenti berlangganan — berkas backup menjadi benda
 * mati yang tidak bisa dibuka siapa pun tanpa aplikasinya. PDF tetap
 * terbuka di mana saja, selamanya.
 *
 * =====================================================================
 * KENAPA ADA STATUS 'kedaluwarsa'
 * =====================================================================
 * Arsip adalah potret pada satu saat. Selama semesternya TERKUNCI,
 * potret itu dijamin tetap sesuai — tidak ada data yang bisa berubah
 * (lihat App\Http\Middleware\EnsurePeriodeTidakTerkunci).
 *
 * Satu-satunya cara data bisa berubah adalah lewat Buka Kunci. Maka di
 * situlah penandanya dipasang: begitu Admin membuka kunci sebuah
 * semester, arsipnya ditandai 'kedaluwarsa'. Tidak perlu memeriksa 26
 * tabel — peristiwa buka-kunci itu sendiri sudah menjadi sinyalnya.
 *
 * Arsip lama TIDAK dihapus saat itu. Bisa jadi berkas itulah yang sudah
 * terlanjur diserahkan ke asesor, dan menghapusnya akan menghilangkan
 * bukti tentang apa yang dulu tercatat. Ia hanya diberi label jujur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arsip_semesters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();

            $table->string('path')->nullable()->comment('letak berkas ZIP di cakram private');
            $table->unsignedBigInteger('ukuran')->nullable();
            $table->unsignedSmallInteger('jumlah_berkas')->nullable();

            // antre  → sedang menunggu / sedang dikerjakan pekerja antrian
            // siap   → selesai, dan datanya masih sesuai
            // kedaluwarsa → semesternya pernah dibuka kunci setelah arsip dibuat
            // gagal  → pembuatannya berhenti karena galat
            $table->enum('status', ['antre', 'siap', 'kedaluwarsa', 'gagal'])->default('antre');

            $table->text('catatan')->nullable()->comment('pesan galat, atau keterangan lain');

            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('selesai_at')->nullable();

            $table->timestamps();

            $table->index(['tahun_ajaran_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arsip_semesters');
    }
};
