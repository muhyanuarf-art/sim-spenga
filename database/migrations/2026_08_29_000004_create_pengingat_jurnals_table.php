<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CATATAN PENGINGAT JURNAL & ABSENSI YANG SUDAH DIPROSES.
 *
 * Fungsinya persis seperti `notifikasi_alfa_terkirims` bagi notifikasi
 * Alfa: menjadi bukti "sesi ini sudah pernah diingatkan", sehingga guru
 * tidak dikirimi pesan yang sama berulang kali setiap kali penjadwal
 * berjalan. Sekaligus menjadi riwayat yang bisa dilihat admin.
 *
 * Satu baris = satu SESI MENGAJAR pada satu tanggal. Sesi di sini artinya
 * sama dengan yang dilihat guru di layar: beberapa jam pelajaran berurutan
 * pada kelas & mata pelajaran yang sama dihitung satu sesi, jadi guru yang
 * mengajar 3 jam berturut-turut menerima satu pesan, bukan tiga.
 *
 * Kunci sesinya adalah `jadwal_pelajaran_id` JAM PERTAMA pada sesi itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengingat_jurnals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('guru_id')->constrained('users')->restrictOnDelete();

            // Jam pertama sesi — sekaligus kunci anti-kirim-ganda.
            $table->foreignId('jadwal_pelajaran_id')->constrained('jadwal_pelajarans')->cascadeOnDelete();

            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->restrictOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->restrictOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajarans')->restrictOnDelete();

            $table->date('tanggal');
            $table->unsignedTinyInteger('jam_ke_awal');
            $table->unsignedTinyInteger('jam_ke_akhir');

            // pending  : sudah dicatat, pesannya sedang menunggu antrian
            // terkirim : Fonnte menyatakan pesannya diterima
            // gagal    : gagal permanen (nomor guru bermasalah / token salah)
            // dilewati : guru keburu mengisi sebelum pesannya benar-benar
            //            dikirim — tidak jadi diganggu
            $table->enum('status_kirim', ['pending', 'terkirim', 'gagal', 'dilewati'])->default('pending');

            $table->unsignedTinyInteger('percobaan_ke')->default(1);
            $table->string('keterangan_gagal')->nullable();
            $table->timestamp('dikirim_at')->nullable();

            $table->timestamps();

            // Inti pengaman anti-ganda: satu sesi pada satu tanggal hanya
            // boleh punya satu baris. Penjadwal boleh berjalan tiap 5 menit
            // tanpa risiko mengirim ulang.
            $table->unique(['jadwal_pelajaran_id', 'tanggal'], 'pengingat_sesi_unik');

            // Halaman riwayat menyaring per bulan; index ini yang membuatnya
            // tetap cepat setelah tabelnya berisi data bertahun-tahun.
            $table->index('tanggal');
            $table->index(['guru_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengingat_jurnals');
    }
};
