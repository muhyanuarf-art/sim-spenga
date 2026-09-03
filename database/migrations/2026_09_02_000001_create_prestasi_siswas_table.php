<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRESTASI SISWA.
 *
 * =====================================================================
 * MASALAH YANG DIPERBAIKI
 * =====================================================================
 * Prestasi siswa selama ini tercatat di mana-mana — buku tulis wali
 * kelas, pesan WhatsApp, folder sertifikat yang tercecer — sehingga saat
 * dibutuhkan (laporan sekolah, akreditasi, pendaftaran lomba berikutnya)
 * datanya tidak lengkap. Yang hilang bukan hanya catatannya, tetapi juga
 * sertifikat fisiknya.
 *
 * Akar masalahnya bukan ketiadaan tabel, melainkan JARAK antara yang
 * TAHU dan yang BERTUGAS MENCATAT. Yang tahu seorang siswa juara adalah
 * wali kelasnya; yang bertugas merekap adalah kesiswaan. Selama hanya
 * kesiswaan yang bisa memasukkan data, setiap prestasi harus melewati
 * satu orang perantara — dan di situlah ia hilang.
 *
 * Karena itu tabel ini dirancang untuk DUA penulis:
 *   - Wali kelas mencatat prestasi siswa kelasnya sendiri, saat itu juga.
 *   - Kesiswaan memverifikasi, melengkapi, dan merekap.
 *
 * Dan satu pembaca yang menjadi penagih paling andal: ORANG TUA. Prestasi
 * anaknya tampil di portal orang tua, jadi yang belum tercatat akan
 * ditanyakan sendiri oleh orang tuanya — tanpa perlu ada yang menagih.
 *
 * =====================================================================
 * KENAPA ADA VERIFIKASI
 * =====================================================================
 * Karena penulisnya dua, perlu satu penanda mana yang sudah dipastikan
 * benar oleh kesiswaan — itulah yang boleh dipakai untuk laporan resmi.
 * Verifikasi sengaja dibuat SATU KLIK, bukan formulir tersendiri: kalau
 * memverifikasi lebih repot daripada mengetik ulang, ia tidak akan
 * pernah dipakai.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestasi_siswas', function (Blueprint $table) {
            $table->id();

            // RESTRICT, mengikuti kebijakan yang sama dengan tabel lain
            // yang menunjuk siswa: menghapus siswa yang punya riwayat
            // harus ditolak dan diterangkan, bukan menghapus riwayatnya
            // diam-diam.
            $table->foreignId('siswa_id')->constrained('siswas')->restrictOnDelete();

            // Tahun ajaran saat prestasi diraih. Diisi dari periode yang
            // sedang dilihat pencatatnya, dan bisa dikoreksi — prestasi
            // liburan kadang baru dicatat setelah semester berganti.
            $table->foreignId('tahun_ajaran_id')->nullable()->constrained('tahun_ajarans')->nullOnDelete();

            $table->string('nama');
            $table->enum('bidang', ['akademik', 'non_akademik'])->default('non_akademik');

            // Tingkat & peringkat dipisah karena keduanya ditanyakan
            // terpisah di setiap format laporan sekolah.
            $table->enum('tingkat', [
                'sekolah', 'kecamatan', 'kabupaten', 'provinsi', 'nasional', 'internasional',
            ])->default('kabupaten');

            $table->enum('peringkat', [
                'juara_1', 'juara_2', 'juara_3', 'harapan', 'finalis', 'peserta',
            ])->default('juara_1');

            $table->string('penyelenggara')->nullable();
            $table->date('tanggal');
            $table->text('keterangan')->nullable();

            // Sertifikat disimpan di cakram 'local' (storage/app/private),
            // di luar jangkauan peladen web, dan hanya bisa dibuka lewat
            // BerkasTerlindungiController. Alasannya sama dengan bukti BK:
            // berkas berisi nama siswa tidak boleh terbuka bagi siapa pun
            // yang kebetulan memegang alamatnya.
            $table->string('sertifikat_path')->nullable();

            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('diverifikasi_at')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Daftar prestasi hampir selalu dibaca per siswa atau per
            // tahun ajaran, dan diurutkan menurut tanggal.
            $table->index(['siswa_id', 'tanggal']);
            $table->index(['tahun_ajaran_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestasi_siswas');
    }
};
