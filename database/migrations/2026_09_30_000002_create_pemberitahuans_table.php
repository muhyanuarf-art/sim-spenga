<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LONCENG PEMBERITAHUAN — laporan yang masuk untuk seseorang.
 *
 * =====================================================================
 * MASALAH YANG DISELESAIKAN
 * =====================================================================
 * Semua pemberitahuan di aplikasi ini sebelumnya bersifat KELUAR
 * (WhatsApp ke guru dan orang tua) atau SEKEJAP (pesan hijau setelah
 * Simpan, hilang begitu pindah halaman).
 *
 * Akibatnya laporan yang masuk tidak pernah sampai. Guru mata pelajaran
 * mencatat kasus seorang siswa; guru BK kelas itu baru tahu kalau
 * kebetulan membuka menu Kasus. Wali kelas mencatat prestasi; Kesiswaan
 * baru tahu kalau kebetulan memeriksa. Tidak ada yang memberi tahu.
 *
 * =====================================================================
 * KENAPA TABEL SENDIRI, BUKAN `notifications` BAWAAN LARAVEL
 * =====================================================================
 * Laravel punya tabel `notifications` dengan kolom `data` berisi JSON.
 * Itu serbaguna, tetapi isinya tidak bisa disaring atau diindeks dengan
 * mudah, dan tiap pembacaan harus membongkar JSON lebih dulu.
 *
 * Yang diperlukan di sini sempit dan jelas: satu baris untuk satu orang,
 * dengan judul, isi singkat, alamat tujuan, dan penanda sudah dibaca.
 * Kolom biasa membuatnya bisa dihitung langsung di database — dan
 * hitungan "berapa yang belum dibaca" itu dijalankan pada SETIAP
 * pemuatan halaman, jadi kemurahannya penting.
 *
 * =====================================================================
 * IKON DAN WARNA TIDAK DISIMPAN DI SINI
 * =====================================================================
 * Keduanya diturunkan dari `jenis` lewat peta di App\Models\Pemberitahuan.
 * Kalau disimpan per baris, mengubah tampilan satu jenis berarti menyunting
 * ribuan baris lama — dan baris lama akan selamanya memakai gaya lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemberitahuans', function (Blueprint $table) {
            $table->id();

            // Pemberitahuan selalu untuk SATU orang. Satu kejadian yang
            // perlu diketahui lima orang menghasilkan lima baris — itu
            // disengaja, supaya "sudah dibaca" benar-benar per orang.
            // Tanpa itu, satu orang membuka dan tanda merahnya hilang
            // untuk semua.
            $table->foreignId('untuk_user_id')->constrained('users')->cascadeOnDelete();

            // Siapa yang memicunya. Boleh kosong untuk pemberitahuan yang
            // dibangkitkan sistem sendiri, bukan oleh orang.
            $table->foreignId('dari_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Penanda jenis kejadian, mis. 'kasus_siswa'. Menentukan ikon
            // dan warnanya, dan memudahkan menyaring kelak.
            $table->string('jenis', 40);

            $table->string('judul');
            $table->string('isi', 300);

            // Alamat yang dibuka saat pemberitahuan diklik. Disimpan jadi
            // jalur siap pakai, bukan nama rute + parameter: halaman tujuan
            // sebuah pemberitahuan lama tidak boleh berubah hanya karena
            // rutenya kelak diubah.
            $table->string('tautan', 500)->nullable();

            $table->timestamp('dibaca_at')->nullable();
            $table->timestamps();

            // Query yang paling sering: "punya siapa, yang belum dibaca,
            // terbaru dulu". Satu indeks untuk ketiganya.
            $table->index(['untuk_user_id', 'dibaca_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemberitahuans');
    }
};
