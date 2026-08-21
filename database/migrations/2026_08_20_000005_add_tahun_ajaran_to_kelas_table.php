<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * STEP 5 — Bagian 1-6. Perubahan struktural utama: `kelas` sekarang
 * terikat Tahun Ajaran (1 baris kelas = 1 rombel pada SATU tahun ajaran).
 *
 * KONVENSI PENTING (dipakai konsisten di seluruh app, sama seperti
 * riwayat_kelas_siswas.tahun_ajaran_id sejak STEP 4): `kelas.tahun_ajaran_id`
 * SELALU menunjuk baris Semester GANJIL tahun ajaran tsb — bukan Genap —
 * karena satu rombel dipakai LINTAS SEMESTER dalam tahun ajaran yang sama
 * (STEP 3 Bagian 9: "Kelas tidak boleh berubah hanya karena ganti
 * semester"). Untuk mencocokkan "kelas ini termasuk tahun ajaran aktif
 * yang mana", kode SELALU membandingkan lewat NAMA tahun ajaran
 * (kelas.tahunAjaran.nama), BUKAN membandingkan tahun_ajaran_id persis —
 * lihat App\Models\Kelas::scopeAktif()/scopeUntukTahunAjaran().
 *
 * TAHAP MIGRASI (aman, tidak menghapus data):
 * 1. Tambah kolom tahun_ajaran_id (nullable dulu) + status.
 * 2. Backfill SEMUA baris kelas yang ada sekarang ke Tahun Ajaran yang
 *    SEDANG AKTIF saat migrasi ini dijalankan (baris Semester Ganjil-nya).
 *    Ini best-effort: histori SEBELUM STEP 5 tidak bisa direkonstruksi
 *    sempurna kalau kelas yang sama pernah dipakai lintas tahun ajaran
 *    SEBELUM perbaikan ini (datanya memang tidak pernah dipisah per
 *    tahun) — tapi TIDAK ADA data yang hilang: seluruh kelas_id lama di
 *    siswas/guru_mengajar_kelas/jadwal_pelajarans/dst TETAP menunjuk ke
 *    baris kelas yang SAMA PERSIS seperti sebelumnya (hanya baris kelas
 *    itu sendiri yang sekarang punya tahun_ajaran_id).
 * 3. Ganti unique(nama_kelas) → unique(tahun_ajaran_id, tingkat, nama_kelas)
 *    (Bagian 15/26).
 *
 * tahun_ajaran_id TETAP NULLABLE di level database (bukan diubah paksa
 * jadi NOT NULL) karena mengubah nullability kolom yang sudah berisi
 * data di Laravel 11 tanpa doctrine/dbal berisiko — proyek ini tidak
 * memasangnya. "Wajib diisi" ditegakkan di level aplikasi (validasi
 * KelasController) sebagai gantinya, konsisten dengan pendekatan
 * konservatif migrasi di STEP 1-4 sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->foreignId('tahun_ajaran_id')->nullable()->after('id')
                ->constrained('tahun_ajarans')->cascadeOnDelete();
            $table->string('status', 20)->default('aktif')->after('wali_kelas_id'); // aktif|nonaktif (Bagian 12)
        });

        $tahunAjaranAktif = DB::table('tahun_ajarans')
            ->where('is_active', true)
            ->where('semester', 'Ganjil')
            ->first();

        // Kalau yang aktif kebetulan Semester Genap, cari baris Ganjil
        // pasangannya (nama sama) untuk dipakai sebagai anchor — sesuai
        // konvensi "tahun_ajaran_id kelas selalu baris Ganjil".
        if (! $tahunAjaranAktif) {
            $aktifApapun = DB::table('tahun_ajarans')->where('is_active', true)->first();
            if ($aktifApapun) {
                $tahunAjaranAktif = DB::table('tahun_ajarans')
                    ->where('nama', $aktifApapun->nama)
                    ->where('semester', 'Ganjil')
                    ->first();
            }
        }

        if ($tahunAjaranAktif) {
            DB::table('kelas')->whereNull('tahun_ajaran_id')->update([
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
            ]);
        }

        Schema::table('kelas', function (Blueprint $table) {
            $table->dropUnique(['nama_kelas']);
            $table->unique(['tahun_ajaran_id', 'tingkat', 'nama_kelas'], 'kelas_unique_tahun_tingkat_nama');
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropUnique('kelas_unique_tahun_tingkat_nama');
            $table->unique(['nama_kelas']);
            $table->dropConstrainedForeignId('tahun_ajaran_id');
            $table->dropColumn('status');
        });
    }
};
