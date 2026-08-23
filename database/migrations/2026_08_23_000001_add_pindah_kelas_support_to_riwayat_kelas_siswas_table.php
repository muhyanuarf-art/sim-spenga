<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sebelumnya riwayat_kelas_siswas dikunci unique(siswa_id, tahun_ajaran_id) —
 * cocok untuk "kenaikan kelas" (1x per tahun ajaran), TAPI tidak bisa
 * menampung siswa yang PINDAH KELAS DI TENGAH TAHUN AJARAN YANG SAMA
 * (mis. Juli-Agustus di 7A, September pindah ke 7B — masih tahun ajaran
 * yang sama, jadi baris riwayat kedua akan ditolak unique constraint-nya).
 *
 * Migrasi ini:
 * 1. Menghapus unique constraint tsb (boleh banyak baris per tahun ajaran).
 * 2. Menambah kolom `jenis` untuk membedakan asal baris:
 *    - 'awal_masuk'    : siswa baru pertama kali masuk (kelas_asal_id null)
 *    - 'kenaikan_kelas': hasil Import Excel Data Siswa (ganti tahun ajaran)
 *    - 'pindah_kelas'  : mutasi kelas di tengah tahun ajaran (menu baru di
 *                        Data Siswa, atau otomatis lewat form Edit Data Siswa)
 * 3. Menambah kolom `tanggal_mutasi` supaya tanggal EFEKTIF pindah bisa
 *    diisi manual (beda dengan created_at = tanggal data ini diinput),
 *    dipakai untuk urutan tampil di halaman Riwayat Kelas.
 * 4. Backfill data lama: jenis diisi dari kelas_asal_id (null => awal_masuk,
 *    selain itu => kenaikan_kelas), tanggal_mutasi diisi dari created_at.
 *
 * Data lama TIDAK dihapus/diubah maknanya — hanya ditambah kolom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_kelas_siswas', function (Blueprint $table) {
            $table->dropUnique('riwayat_kelas_unique_siswa_tahun');
            $table->string('jenis', 30)->nullable()->after('kelas_id');
            $table->date('tanggal_mutasi')->nullable()->after('jenis');
        });

        DB::table('riwayat_kelas_siswas')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('riwayat_kelas_siswas')->where('id', $row->id)->update([
                    'jenis' => $row->kelas_asal_id === null ? 'awal_masuk' : 'kenaikan_kelas',
                    'tanggal_mutasi' => \Illuminate\Support\Carbon::parse($row->created_at)->toDateString(),
                ]);
            }
        });

        Schema::table('riwayat_kelas_siswas', function (Blueprint $table) {
            $table->index(['siswa_id', 'tanggal_mutasi']);
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_kelas_siswas', function (Blueprint $table) {
            $table->dropIndex(['siswa_id', 'tanggal_mutasi']);
            $table->dropColumn(['jenis', 'tanggal_mutasi']);
            $table->unique(['siswa_id', 'tahun_ajaran_id'], 'riwayat_kelas_unique_siswa_tahun');
        });
    }
};
