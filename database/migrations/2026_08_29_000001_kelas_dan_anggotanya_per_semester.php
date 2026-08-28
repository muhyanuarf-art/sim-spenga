<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * KELAS & DAFTAR SISWANYA IKUT SEMESTER (permintaan admin, 29 Agustus 2026).
 *
 * =====================================================================
 * YANG BERUBAH
 * =====================================================================
 * Sampai sekarang satu baris kelas ("7A 2026/2027") dipakai bersama oleh
 * Semester Ganjil dan Genap, dan daftar siswanya cuma satu kolom penunjuk
 * `siswas.kelas_id`. Akibatnya:
 *
 *   - Sekolah tidak bisa menyusun ulang kelas di pergantian semester.
 *   - Kalaupun siswa dipindahkan, penunjuk itu ikut berpindah sehingga
 *     daftar siswa SEMESTER LAMA ikut berubah — wali kelas Semester 1
 *     yang menengok periodenya akan melihat daftar siswa Semester 2.
 *
 * Sesudah migrasi ini:
 *   - `kelas.tahun_ajaran_id` menunjuk baris SEMESTER-nya sendiri, sama
 *     seperti seluruh data lain sejak 2026_08_28_000005.
 *   - Keanggotaan pindah ke tabel `anggota_kelas`, satu baris = "siswa X
 *     anggota kelas Y pada semester Z". Kolom `siswas.kelas_id` dibuang
 *     supaya tidak ada dua sumber kebenaran.
 *   - Indeks unik (tahun_ajaran_id, siswa_id) menjamin di tingkat database
 *     bahwa satu siswa hanya berada di SATU kelas per semester.
 *
 * =====================================================================
 * DATA LAMA — PERILAKUNYA DIPERTAHANKAN PERSIS
 * =====================================================================
 * Setiap kelas yang ada digandakan untuk semester lain pada tahun ajaran
 * yang sama, dan seluruh anggotanya ikut disalin. Jadi begitu Semester
 * Genap diaktifkan, kelas & daftar siswanya sudah siap — persis seperti
 * sebelum migrasi ini — dan sekolah tinggal menyusun ulang bila memang
 * ada perubahan.
 *
 * Baris kelas ASLI dipertahankan id-nya dan tetap di Semester Ganjil,
 * karena SELURUH data transaksi menunjuk id itu (absensi, jurnal, nilai,
 * kasus BK, riwayat kelas). Yang berupa baris baru adalah kembaran untuk
 * semester lainnya.
 *
 * Penunjuk PER SEMESTER yang selama ini terlanjur mengarah ke baris
 * Ganjil (mis. penugasan wali kelas Semester Genap) dipindahkan ke
 * kembaran semesternya masing-masing.
 */
return new class extends Migration
{
    /** Tabel per-semester yang kelas_id-nya harus ikut kembaran semesternya. */
    private const IKUT_SEMESTER = [
        'penugasan_wali_kelas',
        'guru_mengajar_kelas',
        'guru_bk_kelas',
        'jadwal_pelajarans',
        'nilai_siswas',
        'analisis_sumatifs',
        'penilaian_kelas_mapels',
        'kasus_siswas',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('anggota_kelas')) {
            Schema::create('anggota_kelas', function (Blueprint $t) {
                $t->id();
                // Semesternya sendiri — disimpan juga di sini (bukan cuma
                // lewat kelas) supaya bisa dijamin indeks unik di bawah.
                $t->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();
                $t->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
                // Kelas yang masih punya anggota tidak boleh dihapus —
                // sejalan dengan migrasi 2026_08_28_000006.
                $t->foreignId('kelas_id')->constrained('kelas')->restrictOnDelete();
                $t->timestamps();

                // Satu siswa hanya boleh di SATU kelas per semester.
                $t->unique(['tahun_ajaran_id', 'siswa_id'], 'anggota_kelas_unik');
                $t->index(['kelas_id', 'tahun_ajaran_id'], 'anggota_kelas_per_kelas');
            });
        }

        if (! Schema::hasColumn('siswas', 'kelas_id')) {
            return; // sudah dijalankan sebelumnya
        }

        $semesterPerNama = $this->semesterPerNamaTahun();
        $namaPerId = DB::table('tahun_ajarans')->pluck('nama', 'id')->all();
        $sekarang = now();

        // ---- 1. Gandakan kelas untuk semester lain + petakan kembarannya.
        // $kembaran[idKelasAsli][idSemester] = idKelasSemesterItu
        $kembaran = [];

        foreach (DB::table('kelas')->whereNotNull('tahun_ajaran_id')->get() as $kelas) {
            $nama = $namaPerId[$kelas->tahun_ajaran_id] ?? null;
            $kembaran[$kelas->id][(int) $kelas->tahun_ajaran_id] = (int) $kelas->id;

            foreach ($semesterPerNama[$nama] ?? [] as $idSemester) {
                if ($idSemester === (int) $kelas->tahun_ajaran_id) {
                    continue;
                }

                $sudahAda = DB::table('kelas')
                    ->where('tahun_ajaran_id', $idSemester)
                    ->where('tingkat', $kelas->tingkat)
                    ->where('nama_kelas', $kelas->nama_kelas)
                    ->value('id');

                $kembaran[$kelas->id][$idSemester] = (int) ($sudahAda ?: DB::table('kelas')->insertGetId([
                    'tahun_ajaran_id' => $idSemester,
                    'nama_kelas' => $kelas->nama_kelas,
                    'tingkat' => $kelas->tingkat,
                    'status' => $kelas->status,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ]));
            }
        }

        // ---- 2. Pindahkan keanggotaan siswa ke tabel baru, untuk SETIAP semester.
        $baris = [];
        foreach (DB::table('siswas')->whereNotNull('kelas_id')->get() as $siswa) {
            foreach ($kembaran[$siswa->kelas_id] ?? [] as $idSemester => $idKelas) {
                $baris[] = [
                    'tahun_ajaran_id' => $idSemester,
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $idKelas,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ];
            }
        }
        foreach (array_chunk($baris, 300) as $potongan) {
            DB::table('anggota_kelas')->insertOrIgnore($potongan);
        }

        // ---- 3. Penunjuk per semester diarahkan ke kelas semesternya.
        foreach (self::IKUT_SEMESTER as $tabel) {
            if (! Schema::hasTable($tabel) || ! Schema::hasColumn($tabel, 'tahun_ajaran_id')) {
                continue;
            }

            foreach (DB::table($tabel)->whereNotNull('kelas_id')->get() as $r) {
                $tujuan = $kembaran[$r->kelas_id][(int) $r->tahun_ajaran_id] ?? null;

                if ($tujuan && $tujuan !== (int) $r->kelas_id) {
                    DB::table($tabel)->where('id', $r->id)->update(['kelas_id' => $tujuan]);
                }
            }
        }

        // ---- 4. Kolom lama dibuang: keanggotaan sekarang hanya di anggota_kelas.
        Schema::table('siswas', function (Blueprint $t) {
            $t->dropConstrainedForeignId('kelas_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('siswas', 'kelas_id')) {
            Schema::table('siswas', function (Blueprint $t) {
                $t->foreignId('kelas_id')->nullable()->after('jenis_kelamin')
                    ->constrained('kelas')->nullOnDelete();
            });

            // Kembalikan penunjuk tunggal dari keanggotaan semester paling awal.
            foreach (DB::table('anggota_kelas')->orderBy('tahun_ajaran_id')->get() as $a) {
                DB::table('siswas')->where('id', $a->siswa_id)
                    ->whereNull('kelas_id')
                    ->update(['kelas_id' => $a->kelas_id]);
            }
        }

        Schema::dropIfExists('anggota_kelas');

        // Kelas kembaran (semester selain Ganjil) dibuang lagi.
        $idGanjil = DB::table('tahun_ajarans')->where('semester', 'Ganjil')->pluck('id')->all();
        DB::table('kelas')->whereNotNull('tahun_ajaran_id')->whereNotIn('tahun_ajaran_id', $idGanjil)->delete();
    }

    /** @return array<string, list<int>> "2026/2027" => [id Ganjil, id Genap] */
    private function semesterPerNamaTahun(): array
    {
        $peta = [];

        foreach (DB::table('tahun_ajarans')->orderByRaw("FIELD(semester, 'Ganjil', 'Genap')")->get() as $ta) {
            $peta[$ta->nama][] = (int) $ta->id;
        }

        return $peta;
    }
};
