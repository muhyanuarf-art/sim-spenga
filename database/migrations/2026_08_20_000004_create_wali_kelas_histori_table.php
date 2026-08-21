<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * STEP 4 — Bagian 15 & 16 & Test 5.
 *
 * AUDIT (lihat laporan STEP 4): tabel `kelas` bersifat GLOBAL (1 baris
 * per nama_kelas, dipakai ulang lintas tahun ajaran — sudah dipakai
 * begitu sejak awal project oleh 9+ modul: siswas, guru_mengajar_kelas,
 * jadwal_pelajarans, guru_bk_kelas, riwayat_kelas_siswas, dst).
 *
 * KEPUTUSAN: struktur `kelas` global TETAP DIPERTAHANKAN (bukan diubah
 * jadi per-tahun-ajaran) karena:
 *   1. nama_kelas (mis. "8A") secara alami adalah label yang dipakai
 *      ulang tiap tahun, bukan entitas baru tiap tahun — sama seperti
 *      Mata Pelajaran/Jenis Pelanggaran yang juga data master dipakai
 *      ulang (Bagian 23).
 *   2. Siswa.kelas_id (penempatan SAAT INI) dan riwayat_kelas_siswas
 *      (snapshot per tahun ajaran) SUDAH cukup untuk menelusuri "siswa
 *      X ada di kelas Y pada tahun Z" — mengubah kelas jadi per-tahun
 *      berarti migrasi ulang FK di 9+ tabel sekaligus untuk manfaat
 *      yang sama sekali tidak diminta secara eksplisit di STEP 4.
 *   3. SATU-SATUNYA atribut kelas yang terbukti tidak aman lintas tahun
 *      adalah `wali_kelas_id` (Bagian 15 & 16 & Test 5: ubah wali kelas
 *      tahun baru TIDAK BOLEH mengubah histori wali kelas tahun lama) —
 *      karena itu SATU kolom itu saja yang "dipisah per tahun ajaran"
 *      lewat tabel baru ini, bukan seluruh tabel kelas.
 *
 * Tabel ini ADITIF: tidak menyentuh tabel `kelas` atau relasi manapun
 * yang sudah ada. `kelas.wali_kelas_id` TETAP ADA & tetap dipakai apa
 * adanya oleh semua modul lama (WaliKelasController, dashboard, dll)
 * sebagai representasi "wali kelas SAAT INI" — tidak ada satu pun
 * controller/view lama yang perlu diubah query-nya. Tabel baru ini
 * HANYA dipakai untuk MENCATAT histori setiap kali wali kelas diubah
 * (lihat KelasController::update()), supaya histori tahun-tahun lama
 * bisa ditelusuri terpisah dari kondisi "saat ini".
 *
 * unique(kelas_id, tahun_ajaran_nama) — 1 kelas hanya 1 wali kelas per
 * TAHUN AJARAN (bukan per semester — wali kelas tidak berubah hanya
 * karena ganti semester, sesuai STEP 3 Bagian 10).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wali_kelas_histori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->string('tahun_ajaran_nama', 20); // mis. "2026/2027" — bukan FK ke tahun_ajarans karena 1 tahun ajaran = 2 baris semester di sana, wali kelas scope-nya per TAHUN, bukan per semester.
            $table->foreignId('wali_kelas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diatur_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['kelas_id', 'tahun_ajaran_nama'], 'wali_kelas_histori_unique_kelas_tahun');
        });

        // Backfill best-effort: kalau ada Tahun Ajaran yang sedang AKTIF
        // saat migrasi ini dijalankan, catat wali_kelas_id kelas SAAT INI
        // sebagai histori untuk tahun ajaran yang aktif itu. Ini TIDAK
        // merekonstruksi histori tahun-tahun sebelum STEP 4 (memang tidak
        // mungkin — datanya tidak pernah dicatat per tahun sebelum ini),
        // tapi memastikan tahun ajaran yang SEDANG berjalan saat migrasi
        // ini dipasang punya baris histori yang benar sebelum admin
        // sempat menggantinya untuk tahun ajaran berikutnya (lihat
        // KelasController::update()).
        $tahunAjaranAktif = DB::table('tahun_ajarans')->where('is_active', true)->first();
        if ($tahunAjaranAktif) {
            $now = now();
            $baris = DB::table('kelas')->whereNotNull('wali_kelas_id')->get(['id', 'wali_kelas_id']);
            foreach ($baris as $k) {
                DB::table('wali_kelas_histori')->insertOrIgnore([
                    'kelas_id' => $k->id,
                    'tahun_ajaran_nama' => $tahunAjaranAktif->nama,
                    'wali_kelas_id' => $k->wali_kelas_id,
                    'diatur_oleh_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wali_kelas_histori');
    }
};
