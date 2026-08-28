<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MASTER DATA IKUT SEMESTER, BUKAN LAGI TAHUN (permintaan admin, 28 Agustus 2026).
 *
 * =====================================================================
 * APA YANG BERUBAH
 * =====================================================================
 * Migrasi 2026_08_28_000003 sudah memberi tahun_ajaran_id pada lima tabel
 * master, tapi dengan konvensi "selalu baris Semester GANJIL" — artinya
 * isinya dipakai bersama oleh Semester 1 & 2. Sekolah menghendaki setiap
 * SEMESTER punya datanya sendiri: daftar mata pelajaran, jam pelajaran,
 * jenis pelanggaran, jenis surat, dan ekstrakurikuler Semester 2 boleh
 * berbeda dari Semester 1 tanpa mengubah semester yang sudah lewat.
 *
 * Sesudah migrasi ini, tahun_ajaran_id pada kelima tabel menunjuk baris
 * SEMESTER-nya sendiri — sama seperti guru_mengajar_kelas, guru_bk_kelas,
 * jadwal_pelajarans, penugasan_wali_kelas, dan ekstrakurikuler_pembinas.
 *
 * =====================================================================
 * DATA LAMA — PERILAKUNYA DIPERTAHANKAN PERSIS
 * =====================================================================
 * Setiap baris yang sekarang menempel di Semester Ganjil DIGANDAKAN untuk
 * setiap semester lain pada tahun ajaran yang sama. Tanpa itu, seluruh
 * menu akan tampak kosong begitu Semester Genap diaktifkan.
 *
 * Baris ASLI dipertahankan id-nya dan tetap di Semester Ganjil, karena
 * banyak data transaksi menunjuk id itu (nilai menunjuk mata_pelajaran_id,
 * jadwal menunjuk jam_pelajaran_id, kasus BK menunjuk jenis_pelanggaran_id,
 * surat menunjuk jenis_surat_id, absensi ekskul menunjuk ekstrakurikuler_id).
 * Yang berupa baris baru adalah salinan untuk Semester Genap.
 *
 * IKUT DIBAWA BERSAMA EKSTRAKURIKULER
 * ===================================
 * - ANGGOTA (ekstrakurikuler_siswas) disalin ke kegiatan kembarannya,
 *   supaya daftar anggota Semester Genap tidak mendadak kosong.
 * - PEMBINA (ekstrakurikuler_pembinas) sudah punya kolom semester sendiri
 *   sejak migrasi 000004; baris milik Semester Genap DIPINDAHKAN menunjuk
 *   kegiatan kembaran Genap-nya, supaya kegiatan dan pembinanya berada di
 *   semester yang sama.
 */
return new class extends Migration
{
    /** tabel => kolom yang ikut disalin ke kembarannya. */
    private const TABEL = [
        'mata_pelajarans' => ['kode', 'nama_mapel'],
        'jam_pelajarans' => ['hari', 'jam_ke', 'jam_mulai', 'jam_selesai', 'is_active'],
        'jenis_pelanggarans' => ['kode', 'nama', 'kategori', 'poin_default', 'is_active'],
        'jenis_surats' => ['nama_jenis', 'kode_jenis', 'kategori', 'tipe_formulir', 'template_isi', 'is_aktif'],
        'ekstrakurikulers' => ['nama_ekstrakurikuler', 'keterangan', 'is_aktif'],
    ];

    public function up(): void
    {
        $semesterPerNama = $this->semesterPerNamaTahun();
        $namaPerId = DB::table('tahun_ajarans')->pluck('nama', 'id')->all();
        $sekarang = now();

        foreach (self::TABEL as $tabel => $kolom) {
            // Peta kembaran khusus ekstrakurikuler: id lama => id baru per semester.
            $kembaran = [];

            foreach (DB::table($tabel)->whereNotNull('tahun_ajaran_id')->get() as $baris) {
                $nama = $namaPerId[$baris->tahun_ajaran_id] ?? null;

                foreach ($semesterPerNama[$nama] ?? [] as $idSemester) {
                    if ($idSemester === (int) $baris->tahun_ajaran_id) {
                        continue; // baris aslinya sendiri
                    }

                    // Aman dijalankan ulang: lewati kalau kembarannya sudah ada.
                    $sudahAda = DB::table($tabel)
                        ->where('tahun_ajaran_id', $idSemester)
                        ->where($this->kunciPembeda($tabel, $baris))
                        ->first();

                    if ($sudahAda) {
                        $kembaran[$baris->id][$idSemester] = (int) $sudahAda->id;

                        continue;
                    }

                    $isi = ['tahun_ajaran_id' => $idSemester, 'created_at' => $sekarang, 'updated_at' => $sekarang];
                    foreach ($kolom as $k) {
                        $isi[$k] = $baris->{$k};
                    }

                    $kembaran[$baris->id][$idSemester] = (int) DB::table($tabel)->insertGetId($isi);
                }
            }

            if ($tabel === 'ekstrakurikulers') {
                $this->bawaSertaIsiEkskul($kembaran, $sekarang);
            }
        }
    }

    public function down(): void
    {
        // Kembali ke konvensi "semua master data menempel di Semester
        // Ganjil": buang baris milik semester selain Ganjil beserta
        // anggota ekskulnya, dan kembalikan pembina ke kegiatan Ganjil.
        $idGanjil = DB::table('tahun_ajarans')->where('semester', 'Ganjil')->pluck('id')->all();

        $ekskulBukanGanjil = DB::table('ekstrakurikulers')
            ->whereNotNull('tahun_ajaran_id')->whereNotIn('tahun_ajaran_id', $idGanjil)->pluck('id');

        if ($ekskulBukanGanjil->isNotEmpty()) {
            DB::table('ekstrakurikuler_siswas')->whereIn('ekstrakurikuler_id', $ekskulBukanGanjil)->delete();

            // Pembina TIDAK dihapus — barisnya milik migrasi 000004 (penugasan
            // per semester) dan masih dipakai absensi. Cukup dikembalikan
            // menunjuk kegiatan Semester Ganjil dengan nama yang sama.
            foreach (DB::table('ekstrakurikulers')->whereIn('id', $ekskulBukanGanjil)->get() as $ekskul) {
                $nama = DB::table('tahun_ajarans')->where('id', $ekskul->tahun_ajaran_id)->value('nama');

                $kembarGanjil = DB::table('ekstrakurikulers')
                    ->where('nama_ekstrakurikuler', $ekskul->nama_ekstrakurikuler)
                    ->whereIn('tahun_ajaran_id', DB::table('tahun_ajarans')
                        ->where('nama', $nama)->where('semester', 'Ganjil')->pluck('id'))
                    ->value('id');

                if ($kembarGanjil) {
                    DB::table('ekstrakurikuler_pembinas')
                        ->where('ekstrakurikuler_id', $ekskul->id)
                        ->update(['ekstrakurikuler_id' => $kembarGanjil]);
                }
            }
        }

        foreach (array_keys(self::TABEL) as $tabel) {
            DB::table($tabel)->whereNotNull('tahun_ajaran_id')->whereNotIn('tahun_ajaran_id', $idGanjil)->delete();
        }
    }

    /**
     * Anggota disalin ke kegiatan kembarannya; pembina milik semester itu
     * dipindahkan menunjuk kegiatan kembarannya.
     *
     * @param  array<int, array<int, int>>  $kembaran  id ekskul lama => [id semester => id ekskul baru]
     */
    private function bawaSertaIsiEkskul(array $kembaran, $sekarang): void
    {
        foreach ($kembaran as $idLama => $perSemester) {
            $anggota = DB::table('ekstrakurikuler_siswas')->where('ekstrakurikuler_id', $idLama)->get();

            foreach ($perSemester as $idSemester => $idBaru) {
                $baris = [];
                foreach ($anggota as $a) {
                    $sudahAda = DB::table('ekstrakurikuler_siswas')
                        ->where('ekstrakurikuler_id', $idBaru)->where('siswa_id', $a->siswa_id)->exists();

                    if (! $sudahAda) {
                        $baris[] = [
                            'ekstrakurikuler_id' => $idBaru,
                            'siswa_id' => $a->siswa_id,
                            'tanggal_gabung' => $a->tanggal_gabung,
                            'created_at' => $sekarang,
                            'updated_at' => $sekarang,
                        ];
                    }
                }

                foreach (array_chunk($baris, 200) as $potongan) {
                    DB::table('ekstrakurikuler_siswas')->insert($potongan);
                }

                // Pembina semester ini pindah ke kegiatan kembarannya.
                DB::table('ekstrakurikuler_pembinas')
                    ->where('ekstrakurikuler_id', $idLama)
                    ->where('tahun_ajaran_id', $idSemester)
                    ->update(['ekstrakurikuler_id' => $idBaru]);
            }
        }
    }

    /**
     * Kunci yang membedakan satu baris master dari baris lain dalam satu
     * semester — dipakai supaya migrasi ini aman dijalankan berulang.
     *
     * Jenis surat sengaja dicocokkan lewat KODE lebih dulu: sekolah ini
     * punya dua jenis surat bernama sama persis ("Surat Izin Meninggalkan
     * Pelajaran") dengan kode berbeda (SIMP & IMP). Kalau dicocokkan lewat
     * nama saja, yang kedua dianggap sudah ada dan tidak ikut tergandakan.
     */
    private function kunciPembeda(string $tabel, object $baris): array
    {
        return match ($tabel) {
            'mata_pelajarans', 'jenis_pelanggarans' => ['kode' => $baris->kode],
            'jam_pelajarans' => ['hari' => $baris->hari, 'jam_ke' => $baris->jam_ke],
            'jenis_surats' => filled($baris->kode_jenis)
                ? ['kode_jenis' => $baris->kode_jenis]
                : ['nama_jenis' => $baris->nama_jenis],
            'ekstrakurikulers' => ['nama_ekstrakurikuler' => $baris->nama_ekstrakurikuler],
        };
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
