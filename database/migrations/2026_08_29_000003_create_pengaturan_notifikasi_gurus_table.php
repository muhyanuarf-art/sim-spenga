<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PENGATURAN PENGINGAT JURNAL & ABSENSI UNTUK GURU.
 *
 * Dibuat sebagai tabel SENDIRI, bukan menambah kolom ke
 * `pengaturan_sekolahs`, karena dua alasan:
 *
 * 1. `pengaturan_sekolahs` berisi identitas sekolah yang dipakai di semua
 *    dokumen cetak dan dibaca lewat view composer global di setiap
 *    halaman. Menambahi tabel itu dengan token rahasia berarti token ikut
 *    terbawa ke seluruh tampilan, padahal tidak pernah dibutuhkan di sana.
 * 2. Fitur ini bisa dimatikan atau dihapus tanpa menyentuh pengaturan
 *    sekolah yang sudah berjalan.
 *
 * Barisnya tunggal (singleton), sama seperti `pengaturan_sekolahs`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_notifikasi_gurus', function (Blueprint $table) {
            $table->id();

            // Saklar utama. Sengaja BAWAANNYA MATI: sekolah harus mengisi
            // token perangkat kedua lebih dulu, baru menyalakannya sendiri.
            $table->boolean('aktif')->default(false);

            // Berapa menit setelah jam pelajaran selesai barulah pengingat
            // dikirim. Bawaan 30 menit sesuai permintaan sekolah.
            $table->unsignedSmallInteger('jeda_menit')->default(30);

            // Token PERANGKAT KEDUA di Fonnte — nomor kepala sekolah.
            // Disimpan terenkripsi (lihat cast di model), jadi membaca
            // database langsung tidak memberikan tokennya.
            //
            // Perangkat PERTAMA (nomor sekolah, untuk notifikasi Alfa ke
            // orang tua) tetap dibaca dari .env lewat services.fonnte.token
            // dan TIDAK disentuh fitur ini sama sekali.
            $table->text('fonnte_token')->nullable();

            // Jendela waktu yang sopan untuk mengirim. Pengingat untuk jam
            // pelajaran terakhir yang jatuh di luar jendela ini akan
            // menunggu sampai jendela berikutnya, bukan hilang.
            $table->time('jam_mulai_kirim')->default('06:30:00');
            $table->time('jam_akhir_kirim')->default('18:00:00');

            // Naskah pesan yang boleh diubah admin. Kosong berarti memakai
            // naskah bawaan di dalam kode.
            $table->text('template_pesan')->nullable();

            $table->timestamps();
        });

        // Baris tunggalnya langsung dibuat supaya halaman Pengaturan tidak
        // perlu menangani keadaan "belum ada baris".
        DB::table('pengaturan_notifikasi_gurus')->insert([
            'aktif' => false,
            'jeda_menit' => 30,
            'jam_mulai_kirim' => '06:30:00',
            'jam_akhir_kirim' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_notifikasi_gurus');
    }
};
