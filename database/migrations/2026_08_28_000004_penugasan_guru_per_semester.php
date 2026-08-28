<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PENUGASAN GURU IKUT SEMESTER (permintaan admin, 28 Agustus 2026).
 *
 * =====================================================================
 * ATURAN YANG DITEGAKKAN
 * =====================================================================
 * BENDA melekat pada TAHUN AJARAN — mata pelajaran, jam pelajaran, jenis
 * pelanggaran, jenis surat, ekstrakurikuler, dan kelas. IPA tetap IPA dan
 * 8A tetap 8A sepanjang tahun; tidak ada gunanya menggandakannya per
 * semester (anggota ekstrakurikuler & isi kelas pun harus diisi ulang).
 *
 * PENUGASAN ORANG melekat pada SEMESTER — siapa mengajar apa, siapa guru
 * BK kelas mana, siapa wali kelasnya, siapa pembina ekstrakurikulernya.
 * Guru bisa pensiun, mutasi, atau bertukar tugas di tengah tahun ajaran,
 * dan Semester 1 yang sudah lewat TIDAK BOLEH ikut berubah karenanya.
 *
 * guru_mengajar_kelas dan guru_bk_kelas sudah mengikuti aturan itu sejak
 * awal. Dua yang belum — dan diperbaiki migrasi ini:
 *
 *   1. WALI KELAS      — dulu kolom `kelas.wali_kelas_id`. Karena baris
 *                        kelas dipakai bersama Semester 1 & 2, mengganti
 *                        wali kelas di Semester 2 ikut mengubah Semester 1.
 *                        Sekarang pindah ke tabel penugasan_wali_kelas.
 *
 *   2. PEMBINA EKSKUL  — tabel ekstrakurikuler_pembinas belum punya
 *                        periode sama sekali, sehingga mengikuti
 *                        ekstrakurikulernya yang berlaku setahun penuh.
 *                        Sekarang diberi tahun_ajaran_id sendiri.
 *
 * =====================================================================
 * DATA LAMA — PERILAKUNYA DIPERTAHANKAN PERSIS
 * =====================================================================
 * Penugasan yang sudah ada sekarang memang berlaku untuk SATU TAHUN
 * PENUH. Karena itu setiap penugasan lama disalin ke SETIAP semester
 * tahun ajarannya (Ganjil dan Genap), bukan hanya ke semester aktif.
 * Tanpa itu, daftar wali kelas & pembina akan tampak kosong begitu
 * Semester 2 diaktifkan — persis kebalikan dari yang diinginkan.
 *
 * Baris pembina yang SUDAH ADA sengaja dipertahankan id-nya dan
 * ditugaskan ke Semester GANJIL, karena absensi ekstrakurikuler menunjuk
 * id baris pembina (absensi_ekskul_pesertas.ekstrakurikuler_pembina_id).
 * Salinan untuk Semester Genap-lah yang berupa baris baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        // =============================================================
        // 1. WALI KELAS -> tabel penugasan per semester
        // =============================================================
        if (! Schema::hasTable('penugasan_wali_kelas')) {
            Schema::create('penugasan_wali_kelas', function (Blueprint $t) {
                $t->id();
                // Menunjuk baris SEMESTER-nya sendiri (bukan baris Ganjil),
                // sama seperti guru_bk_kelas & guru_mengajar_kelas.
                $t->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();
                $t->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
                $t->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
                $t->timestamps();

                // Satu kelas hanya punya satu wali kelas per semester.
                $t->unique(['tahun_ajaran_id', 'kelas_id'], 'penugasan_wali_unik');
            });
        }

        if (Schema::hasColumn('kelas', 'wali_kelas_id')) {
            $this->pindahkanWaliKelas();

            Schema::table('kelas', function (Blueprint $t) {
                $t->dropConstrainedForeignId('wali_kelas_id');
            });
        }

        // =============================================================
        // 2. PEMBINA EKSTRAKURIKULER -> ikut semester
        // =============================================================
        if (! Schema::hasColumn('ekstrakurikuler_pembinas', 'tahun_ajaran_id')) {
            Schema::table('ekstrakurikuler_pembinas', function (Blueprint $t) {
                $t->foreignId('tahun_ajaran_id')->nullable()->after('ekstrakurikuler_id')
                    ->constrained('tahun_ajarans')->nullOnDelete();
            });

            $this->sebarkanPembinaKeSemuaSemester();
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ekstrakurikuler_pembinas', 'tahun_ajaran_id')) {
            // Sisakan satu baris per (ekstrakurikuler, pembina) — kebalikan
            // dari penyebaran per semester di atas.
            $simpan = DB::table('ekstrakurikuler_pembinas')
                ->selectRaw('MIN(id) as id')
                ->groupBy('ekstrakurikuler_id', 'user_id', 'nama_eksternal')
                ->pluck('id');

            DB::table('ekstrakurikuler_pembinas')->whereNotIn('id', $simpan)->delete();

            Schema::table('ekstrakurikuler_pembinas', function (Blueprint $t) {
                $t->dropConstrainedForeignId('tahun_ajaran_id');
            });
        }

        if (! Schema::hasColumn('kelas', 'wali_kelas_id')) {
            Schema::table('kelas', function (Blueprint $t) {
                $t->foreignId('wali_kelas_id')->nullable()->after('tingkat')
                    ->constrained('users')->nullOnDelete();
            });

            // Kembalikan dari penugasan semester paling awal tiap kelas.
            foreach (DB::table('penugasan_wali_kelas')->orderBy('tahun_ajaran_id')->get() as $p) {
                DB::table('kelas')->where('id', $p->kelas_id)
                    ->whereNull('wali_kelas_id')
                    ->update(['wali_kelas_id' => $p->guru_id]);
            }
        }

        Schema::dropIfExists('penugasan_wali_kelas');
    }

    /** Salin kelas.wali_kelas_id ke penugasan_wali_kelas untuk SETIAP semester tahun ajarannya. */
    private function pindahkanWaliKelas(): void
    {
        $semesterPerNama = $this->semesterPerNamaTahun();
        $namaPerId = $this->namaTahunPerId();
        $sekarang = now();

        $baris = [];
        foreach (DB::table('kelas')->whereNotNull('wali_kelas_id')->get() as $k) {
            $nama = $namaPerId[$k->tahun_ajaran_id] ?? null;

            // Kelas tanpa tahun ajaran (data sangat lama) tidak bisa
            // ditugaskan ke semester mana pun — dilewati, bukan ditebak.
            foreach ($semesterPerNama[$nama] ?? [] as $idSemester) {
                $baris[] = [
                    'tahun_ajaran_id' => $idSemester,
                    'kelas_id' => $k->id,
                    'guru_id' => $k->wali_kelas_id,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ];
            }
        }

        foreach (array_chunk($baris, 200) as $potongan) {
            DB::table('penugasan_wali_kelas')->insertOrIgnore($potongan);
        }
    }

    /** Baris pembina yang ada ditugaskan ke Ganjil, lalu digandakan untuk semester lainnya. */
    private function sebarkanPembinaKeSemuaSemester(): void
    {
        $semesterPerNama = $this->semesterPerNamaTahun();
        $namaPerId = $this->namaTahunPerId();
        $sekarang = now();

        $ekskul = DB::table('ekstrakurikulers')->pluck('tahun_ajaran_id', 'id');
        $tambahan = [];

        foreach (DB::table('ekstrakurikuler_pembinas')->get() as $p) {
            $nama = $namaPerId[$ekskul[$p->ekstrakurikuler_id] ?? null] ?? null;
            $semester = $semesterPerNama[$nama] ?? [];

            if ($semester === []) {
                continue;
            }

            // Baris asli tetap (id-nya dipakai absensi) -> semester pertama.
            DB::table('ekstrakurikuler_pembinas')->where('id', $p->id)
                ->update(['tahun_ajaran_id' => array_shift($semester)]);

            foreach ($semester as $idSemester) {
                $tambahan[] = [
                    'ekstrakurikuler_id' => $p->ekstrakurikuler_id,
                    'tahun_ajaran_id' => $idSemester,
                    'user_id' => $p->user_id,
                    'nama_eksternal' => $p->nama_eksternal,
                    'kontak_eksternal' => $p->kontak_eksternal,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ];
            }
        }

        foreach (array_chunk($tambahan, 200) as $potongan) {
            DB::table('ekstrakurikuler_pembinas')->insert($potongan);
        }
    }

    /**
     * "2026/2027" => [id Ganjil, id Genap] — Ganjil selalu di depan supaya
     * baris asli (yang id-nya dipakai absensi) jatuh ke Semester Ganjil.
     *
     * @return array<string, list<int>>
     */
    private function semesterPerNamaTahun(): array
    {
        $peta = [];

        foreach (DB::table('tahun_ajarans')->orderByRaw("FIELD(semester, 'Ganjil', 'Genap')")->get() as $ta) {
            $peta[$ta->nama][] = (int) $ta->id;
        }

        return $peta;
    }

    /** @return array<int, string> id tahun ajaran => nama tahun ajarannya */
    private function namaTahunPerId(): array
    {
        return DB::table('tahun_ajarans')->pluck('nama', 'id')->all();
    }
};
