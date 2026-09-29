<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KALENDER LIBUR — tanggal-tanggal yang tidak ada KBM.
 *
 * =====================================================================
 * MASALAH YANG DISELESAIKAN
 * =====================================================================
 * Aplikasi tidak pernah tahu kapan sekolah libur. Akibatnya dua hal,
 * dan keduanya merugikan guru:
 *
 * 1. Pengingat WhatsApp tetap terkirim pada hari libur. Jadwal hari Rabu
 *    tetap ada meski Rabu itu libur nasional, jurnalnya tentu kosong,
 *    dan setiap guru yang mengajar hari Rabu ditagih atas sesuatu yang
 *    memang tidak terjadi. Paling parah saat libur semester: semester
 *    baru ditutup Admin setelah rapot dibagi, sedangkan liburnya sudah
 *    berjalan — pengingat mengalir tiap hari ke seluruh guru.
 *
 * 2. Rekapitulasi Kepatuhan menghitung hari libur sebagai jurnal yang
 *    tidak diisi, sehingga angka kepatuhan terlihat lebih buruk daripada
 *    kenyataannya.
 *
 * Sebelum ini satu-satunya jalan adalah mencatat liburnya sebagai
 * Kegiatan Sekolah — bekerja, tetapi maknanya salah: wali kelas jadi
 * mengira ada Absensi Kegiatan yang perlu diisi.
 *
 * =====================================================================
 * KENAPA TIDAK DIIKATKAN KE TAHUN AJARAN
 * =====================================================================
 * Hampir semua tabel di aplikasi ini bertahun-ajaran. Tabel ini sengaja
 * TIDAK, dan itu keputusan yang diambil sadar.
 *
 * Pertanyaan yang dijawab tabel ini hanya satu: "tanggal ini libur atau
 * tidak?" Jawabannya tidak boleh bergantung pada periode mana yang
 * sedang aktif atau sedang dilihat seseorang. Kalau diikatkan, muncul
 * satu cara gagal yang senyap: libur tercatat di periode yang keliru,
 * lalu pengingat tetap terkirim dan tidak ada yang tahu sebabnya.
 *
 * Lagi pula libur semester justru jatuh DI ANTARA dua periode — tidak
 * benar-benar milik keduanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hari_liburs', function (Blueprint $table) {
            $table->id();

            // Libur sehari ditulis dengan mulai = selesai. Satu baris untuk
            // satu rentang, bukan satu baris per tanggal: "Libur Idul Fitri
            // 18-26 Maret" lebih mudah dibaca dan diperbaiki daripada
            // sembilan baris terpisah yang harus dihapus satu per satu.
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');

            $table->string('keterangan');

            $table->foreignId('dibuat_oleh')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Pencarian selalu berbentuk "tanggal X ada di antara mulai dan
            // selesai", dan dijalankan tiap 5 menit oleh penjadwal.
            $table->index(['tanggal_mulai', 'tanggal_selesai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hari_liburs');
    }
};
