<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MODUL PENILAIAN (Daftar Nilai Guru Mapel → Nilai Rapor Wali Kelas).
 *
 * Alur yang dimodelkan di sini persis mengikuti format Daftar Nilai yang
 * dipakai sekolah:
 *
 *   FORMATIF                 : TPF 1 .. TPF 7  → RT (rata-rata)
 *   SUMATIF LINGKUP MATERI   : LM 1 .. LM 4, tiap LM punya SUM + REM (remedi)
 *                              → RT (rata-rata, remedi diperhitungkan)
 *   Gabungan keduanya        : berbobot (default 60%)
 *   ASTS  (Sumatif Tengah Semester)          : berbobot (default 20%)
 *   ASAS/ASAT (Sumatif Akhir Semester/Tahun) : berbobot (default 20%)
 *   ------------------------------------------------------------------
 *   NILAI AKHIR (RAPOR)
 *
 * Catatan penting soal SEMESTER: tabel `tahun_ajarans` di aplikasi ini
 * sudah 1 baris = 1 kombinasi (tahun ajaran + semester) — lihat catatan di
 * App\Models\TahunAjaran. Jadi `tahun_ajaran_id` di tabel-tabel bawah ini
 * SUDAH otomatis membedakan Semester Ganjil & Genap; tidak perlu kolom
 * semester terpisah.
 *
 * Kenapa TIDAK ada tabel "Tujuan Pembelajaran": permintaan sekolah eksplisit
 * — guru tidak perlu mengetik rumusan TP supaya pengisian ringkas. TPF ke-n
 * dibaca sebagai penilaian formatif BAB ke-n.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // 1. SKEMA PENILAIAN — bobot & jumlah kolom, diatur Kurikulum.
        //    Satu baris per periode (tahun ajaran + semester) supaya
        //    bobot yang dipakai semester lalu tidak ikut berubah kalau
        //    Kurikulum mengubah bobot di semester berikutnya (nilai lama
        //    tetap bisa dipertanggungjawabkan apa adanya).
        // ============================================================
        Schema::create('pengaturan_penilaians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();

            // Bobot 3 komponen utama NILAI AKHIR (RAPOR). Divalidasi di
            // controller supaya totalnya selalu 100.
            $table->unsignedTinyInteger('bobot_formatif_sumatif')->default(60);
            $table->unsignedTinyInteger('bobot_asts')->default(20);
            $table->unsignedTinyInteger('bobot_asas')->default(20);

            // Komposisi DI DALAM bobot 60% di atas. Di format daftar nilai
            // sekolah, FORMATIF dan SUMATIF LINGKUP MATERI berbagi satu
            // kolom "%BOBOT 60" — porsi masing-masing tidak disebutkan,
            // jadi dibuat bisa diatur dan default-nya dibagi rata 50:50.
            $table->unsignedTinyInteger('komposisi_formatif')->default(50);
            $table->unsignedTinyInteger('komposisi_sumatif_lm')->default(50);

            // Banyaknya kolom yang tampil di daftar nilai.
            $table->unsignedTinyInteger('jumlah_tpf')->default(7);   // TPF 1..7
            $table->unsignedTinyInteger('jumlah_lm')->default(4);    // LM 1..4

            // Bagaimana nilai REM (remedi) diperlakukan terhadap nilai SUM
            // aslinya. Lihat App\Support\SkemaPenilaian::nilaiLingkupMateri()
            // untuk penjelasan lengkap tiap pilihan.
            // batas_kktp | tertinggi | rata_rata
            $table->string('kebijakan_remedial', 20)->default('batas_kktp');

            $table->foreignId('diperbarui_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('tahun_ajaran_id');
        });

        // ============================================================
        // 2. KKTP per TINGKAT (7, 8, 9) — "Penentuan KKTP Kelas 7: 73–82".
        //    kktp_min = ambang TUNTAS (nilai di bawahnya wajib remedi),
        //    kktp_max = batas atas rentang ketercapaian minimum, dipakai
        //    untuk menentukan predikat.
        // ============================================================
        Schema::create('kktp_tingkats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();
            $table->unsignedTinyInteger('tingkat'); // 7, 8, 9
            $table->unsignedTinyInteger('kktp_min')->default(73);
            $table->unsignedTinyInteger('kktp_max')->default(82);
            $table->timestamps();

            $table->unique(['tahun_ajaran_id', 'tingkat'], 'kktp_tingkat_unik');
        });

        // ============================================================
        // 3. HEADER DAFTAR NILAI per (kelas × mapel × periode).
        //    Gunanya dua: (a) status finalisasi — begitu guru menekan
        //    "Finalisasi", nilainya terkunci supaya tidak berubah diam-diam
        //    setelah dipakai wali kelas menyusun rapor; (b) sumber data
        //    Monitoring Input Nilai untuk Kurikulum/Kepala Sekolah.
        // ============================================================
        Schema::create('penilaian_kelas_mapels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajarans')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();
            // Guru yang terakhir mengisi (untuk kolom "Guru Mapel" di cetakan
            // & monitoring). Bukan penentu hak akses — hak akses tetap dari
            // guru_mengajar_kelas.
            $table->foreignId('guru_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 20)->default('draft'); // draft | final
            $table->timestamp('difinalisasi_at')->nullable();
            $table->foreignId('difinalisasi_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibuka_at')->nullable();
            $table->foreignId('dibuka_oleh_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['kelas_id', 'mata_pelajaran_id', 'tahun_ajaran_id'], 'penilaian_kelas_mapel_unik');
            $table->index(['tahun_ajaran_id', 'status']);
        });

        // ============================================================
        // 4. NILAI SISWA — 1 baris = 1 siswa × 1 mapel × 1 periode.
        //
        //    Nilai mentah disimpan sebagai JSON (kolomnya bisa diatur
        //    Kurikulum lewat jumlah_tpf/jumlah_lm, jadi kolom tetap tpf1..
        //    tpf7 akan kaku). Nilai hasil hitungan TETAP disimpan juga
        //    (rata_formatif, rata_sumatif_lm, nilai_akhir, predikat,
        //    tuntas) — bukan karena tidak bisa dihitung ulang, tapi supaya
        //    laporan wali kelas (semua mapel × semua siswa sekaligus) dan
        //    peringkat kelas tidak perlu menghitung ulang ratusan baris
        //    tiap kali halaman dibuka. Semua kolom hitungan itu diisi
        //    ULANG setiap kali nilai disimpan — lihat NilaiSiswa::hitungUlang().
        // ============================================================
        Schema::create('nilai_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajarans')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();
            // SNAPSHOT kelas saat nilai diisi (pola yang sama dengan
            // absensi_siswas.kelas_id) — supaya nilai semester lalu tidak
            // ikut "pindah" kalau siswanya pindah kelas.
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();

            // {"1": 85, "2": 78, ...} — kunci = nomor TPF (BAB).
            $table->json('formatif')->nullable();
            // {"1": {"sum": 70, "rem": 75}, "2": {...}} — kunci = nomor LM.
            $table->json('sumatif_lm')->nullable();

            $table->decimal('asts', 5, 2)->nullable();
            $table->decimal('asas', 5, 2)->nullable();

            // ===== hasil hitungan (diisi otomatis saat simpan) =====
            $table->decimal('rata_formatif', 5, 2)->nullable();
            $table->decimal('rata_sumatif_lm', 5, 2)->nullable();
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->string('predikat', 1)->nullable();     // A | B | C | D
            $table->boolean('tuntas')->nullable();          // null = belum ada nilai akhir
            // Komponen yang belum terisi sama sekali (mis. ASAS belum
            // diinput karena semester belum berakhir). Disimpan supaya
            // laporan bisa menandai "nilai masih sementara" tanpa harus
            // membongkar JSON-nya lagi.
            $table->boolean('lengkap')->default(false);

            $table->foreignId('diperbarui_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['siswa_id', 'mata_pelajaran_id', 'tahun_ajaran_id'], 'nilai_siswa_unik');
            $table->index(['kelas_id', 'tahun_ajaran_id']);
            $table->index(['tahun_ajaran_id', 'mata_pelajaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_siswas');
        Schema::dropIfExists('penilaian_kelas_mapels');
        Schema::dropIfExists('kktp_tingkats');
        Schema::dropIfExists('pengaturan_penilaians');
    }
};
