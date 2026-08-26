<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ROMBAK TOTAL — Modul Surat sekarang KHUSUS untuk keperluan BK
 * (permintaan eksplisit, gambar contoh terlampir). Perubahan:
 *
 * - Hanya Guru BK yang bisa membuat/edit/hapus surat. Kesiswaan,
 *   Kurikulum, Kepala Sekolah cuma baca (tahu surat sudah ada). Master
 *   Jenis Surat tetap dikelola TU.
 * - Nomor surat format BARU (bukan lagi auto-increment per jenis):
 *   "422/{nomor urut MANUAL}/BK/{bulan romawi otomatis}/{tahun otomatis}".
 *   422 & BK tetap/otomatis, nomor urut diisi TANGAN oleh guru BK (WAJIB
 *   diisi) — lihat App\Support\NomorSuratBk.
 * - 3 dari 4 jenis surat BK punya FORM TERSTRUKTUR (bukan template bebas
 *   lagi): Izin Meninggalkan Pelajaran, Keterangan Terlambat, Pernyataan
 *   Pelanggaran Siswa — datanya disimpan di kolom `data_formulir` (json).
 *   Surat Panggilan Orang Tua/Wali TETAP pakai template bebas yang sudah
 *   ada (`isi`), cuma tanda tangannya ditambah jadi 2 (Kepala Sekolah +
 *   Guru BK).
 * - Jenis surat NON-BK yang sempat di-seed sebelumnya (Surat Keterangan
 *   Aktif, Pindah, dst.) DINONAKTIFKAN — bukan dihapus (surat yang
 *   sudah terlanjur dibuat pakai jenis itu, kalau ada, tetap aman/utuh).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_surats', function (Blueprint $table) {
            $table->enum('tipe_formulir', [
                'bebas', 'izin_meninggalkan_pelajaran', 'keterangan_terlambat', 'pernyataan_pelanggaran',
            ])->default('bebas')->after('kategori');
        });

        Schema::table('surats', function (Blueprint $table) {
            $table->json('data_formulir')->nullable()->after('isi');
        });
        // isi jadi boleh kosong — jenis surat form-terstruktur tidak
        // memakainya lagi (datanya di data_formulir), cuma Surat Panggilan
        // (tipe 'bebas') yang masih pakai. Raw SQL, bukan ->change(),
        // karena proyek ini tidak install doctrine/dbal.
        DB::statement('ALTER TABLE surats MODIFY isi LONGTEXT NULL');

        // Nonaktifkan SEMUA jenis surat lama KECUALI Panggilan (SP) —
        // modul ini sekarang khusus BK.
        DB::table('jenis_surats')->where(function ($q) {
            $q->where('kode_jenis', '!=', 'SP')->orWhereNull('kode_jenis');
        })->update(['is_aktif' => false]);
        DB::table('jenis_surats')->where('kode_jenis', 'SP')
            ->update(['is_aktif' => true, 'tipe_formulir' => 'bebas', 'kategori' => 'keluar']);

        // 3 jenis surat BK baru (idempotent — pakai updateOrInsert
        // berdasarkan kode_jenis, aman dijalankan ulang).
        $sekarang = now();
        foreach ([
            [
                'kode_jenis' => 'IMP',
                'nama_jenis' => 'Surat Izin Meninggalkan Pelajaran',
                'tipe_formulir' => 'izin_meninggalkan_pelajaran',
            ],
            [
                'kode_jenis' => 'SKT',
                'nama_jenis' => 'Surat Keterangan Terlambat',
                'tipe_formulir' => 'keterangan_terlambat',
            ],
            [
                'kode_jenis' => 'SPP',
                'nama_jenis' => 'Surat Pernyataan Pelanggaran Siswa',
                'tipe_formulir' => 'pernyataan_pelanggaran',
            ],
        ] as $row) {
            $sudahAda = DB::table('jenis_surats')->where('kode_jenis', $row['kode_jenis'])->exists();
            DB::table('jenis_surats')->updateOrInsert(
                ['kode_jenis' => $row['kode_jenis']],
                [
                    'nama_jenis' => $row['nama_jenis'],
                    'kategori' => 'internal',
                    'tipe_formulir' => $row['tipe_formulir'],
                    'is_aktif' => true,
                    'updated_at' => $sekarang,
                    ...($sudahAda ? [] : ['created_at' => $sekarang]),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::table('surats', function (Blueprint $table) {
            $table->dropColumn('data_formulir');
        });
        DB::statement("ALTER TABLE surats MODIFY isi LONGTEXT NOT NULL");

        Schema::table('jenis_surats', function (Blueprint $table) {
            $table->dropColumn('tipe_formulir');
        });
    }
};
