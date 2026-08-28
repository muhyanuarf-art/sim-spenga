<?php

namespace App\Support;

use App\Models\Ekstrakurikuler;
use App\Models\EkstrakurikulerPembina;
use App\Models\GuruBkKelas;
use App\Models\GuruMengajarKelas;
use App\Models\JadwalPelajaran;
use App\Models\JamPelajaran;
use App\Models\JenisPelanggaran;
use App\Models\JenisSurat;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

/**
 * SALIN DATA ANTAR PERIODE — dipakai menu Tahun Ajaran ("Salin Data").
 *
 * =====================================================================
 * KENAPA ADA
 * =====================================================================
 * Sejak 28 Agustus 2026 master data ikut tahun ajaran (lihat migrasi
 * 2026_08_28_000003). Konsekuensinya: tahun ajaran baru dimulai dengan
 * halaman-halaman yang kosong — mata pelajaran, jam pelajaran, jenis
 * pelanggaran, jenis surat, ekstrakurikuler, kelas, mapping guru, dan
 * jadwal semuanya belum ada.
 *
 * Mengetik ulang semuanya jelas tidak masuk akal (78 jenis pelanggaran
 * saja sudah melelahkan), jadi kelas ini menyalin isi periode lama ke
 * periode baru dalam SATU aksi, dengan pratinjau lebih dulu.
 *
 * =====================================================================
 * DUA PEMAKAIAN, SATU PERHITUNGAN
 * =====================================================================
 * hitung()  -> dipakai halaman pratinjau (GET, tidak menulis apa pun).
 * jalankan() -> benar-benar menyimpan (POST, dibungkus 1 transaksi).
 * Keduanya memakai daftar & penjodohan yang sama supaya apa yang
 * dijanjikan pratinjau persis sama dengan yang terjadi.
 *
 * =====================================================================
 * PENJODOHAN TIDAK PERNAH MEMAKAI ID
 * =====================================================================
 * Setiap baris di periode tujuan adalah BARIS BARU dengan id berbeda.
 * Karena itu penjodohan selalu lewat identitas yang dibaca manusia:
 *   mata pelajaran   -> kode
 *   jam pelajaran    -> hari + jam ke
 *   jenis pelanggaran-> kode
 *   jenis surat      -> kode jenis (kalau kosong, nama jenis)
 *   ekstrakurikuler  -> nama
 *   kelas            -> tingkat + nama kelas
 *
 * Urutannya penting: master data disalin LEBIH DULU, baru mapping guru
 * & jadwal — karena jadwal menunjuk mata pelajaran dan jam pelajaran
 * MILIK PERIODE TUJUAN, bukan id milik periode sumber.
 *
 * =====================================================================
 * YANG SENGAJA TIDAK IKUT DISALIN
 * =====================================================================
 * - Anggota ekstrakurikuler. Saat "Salin Data" dijalankan, siswa periode
 *   baru biasanya BELUM diimpor, jadi tidak ada yang bisa dijodohkan.
 *   Lebih jujur dikosongkan dan diisi ulang setelah data siswa lengkap
 *   daripada menyalin daftar yang memuat siswa yang sudah lulus.
 * - Seluruh data transaksi (jurnal, absensi, nilai, kasus BK, surat).
 *   Itu catatan kejadian, bukan pengaturan.
 */
class SalinDataPeriode
{
    /** Urutan kunci = urutan penyalinan. Jangan diacak. */
    public const KATEGORI = [
        'mapel' => 'Mata Pelajaran',
        'jam' => 'Jam Pelajaran',
        'pelanggaran' => 'Jenis Pelanggaran',
        'jenis_surat' => 'Jenis Surat',
        'ekskul' => 'Ekstrakurikuler (beserta pembinanya)',
        'kelas' => 'Kelas & Wali Kelas',
        'mengajar' => 'Mapping Guru Mengajar',
        'guru_bk' => 'Mapping Guru BK',
        'jadwal' => 'Jadwal Pelajaran',
    ];

    /** Kategori yang isinya sama persis bila sumber & tujuan satu tahun ajaran (beda semester saja). */
    public const KATEGORI_PER_TAHUN = ['mapel', 'jam', 'pelanggaran', 'jenis_surat', 'ekskul', 'kelas'];

    /**
     * Rencana penyalinan — TIDAK menulis apa pun ke database.
     *
     * @return array<string, array{disalin: list<array{label: string, catatan: ?string}>, sudah_ada: list<array{label: string, catatan: ?string}>}>
     */
    public static function hitung(TahunAjaran $sumber, TahunAjaran $tujuan): array
    {
        $rencana = [];
        foreach (array_keys(self::KATEGORI) as $kunci) {
            $rencana[$kunci] = ['disalin' => [], 'sudah_ada' => []];
        }

        $tahunSama = $sumber->nama === $tujuan->nama;
        $idSumber = self::idTahun($sumber);
        $idTujuan = self::idTahun($tujuan);

        // ===== Master data (hanya relevan kalau beda TAHUN AJARAN) =====
        if (! $tahunSama) {
            foreach (self::daftarMaster() as $kunci => $master) {
                [$model, $kunciBaris, $label] = $master;
                $adaDiTujuan = self::petaBaris($model, $idTujuan, $kunciBaris);

                foreach (self::baris($model, $idSumber) as $baris) {
                    $sisi = isset($adaDiTujuan[$kunciBaris($baris)]) ? 'sudah_ada' : 'disalin';
                    $rencana[$kunci][$sisi][] = ['label' => $label($baris), 'catatan' => null];
                }
            }

            // ===== Kelas =====
            $kelasTujuan = self::petaBaris(Kelas::class, $idTujuan, self::kunciKelas());
            foreach (self::baris(Kelas::class, $idSumber, ['waliKelas'], ['tingkat', 'nama_kelas']) as $k) {
                $sisi = isset($kelasTujuan[self::kunciKelas()($k)]) ? 'sudah_ada' : 'disalin';
                $rencana['kelas'][$sisi][] = [
                    'label' => $k->nama_kelas.' (Tingkat '.$k->tingkat.')',
                    'catatan' => 'Wali Kelas: '.($k->waliKelas->name ?? 'belum diatur'),
                ];
            }
        }

        // ===== Mapping & jadwal (selalu diperiksa, termasuk antar semester) =====
        $peta = self::petaPenjodohan($idSumber, $idTujuan, $tahunSama);

        foreach (self::mengajarSumber($sumber->id) as $m) {
            [$sisi, $catatan] = self::periksaMengajar($m, $peta, $idTujuan);
            $rencana['mengajar'][$sisi][] = [
                'label' => ($m->guru->name ?? '-').' — '.($m->mapel->nama_mapel ?? '-').' — '.($m->kelas->nama_kelas ?? '-'),
                'catatan' => $catatan,
            ];
        }

        foreach (self::guruBkSumber($sumber->id) as $gb) {
            [$sisi, $catatan] = self::periksaGuruBk($gb, $peta, $idTujuan);
            $rencana['guru_bk'][$sisi][] = [
                'label' => ($gb->guru->name ?? '-').' — '.($gb->kelas->nama_kelas ?? '-'),
                'catatan' => $catatan,
            ];
        }

        foreach (self::jadwalSumber($sumber->id) as $j) {
            [$sisi, $catatan] = self::periksaJadwal($j, $peta, $idTujuan);
            $rencana['jadwal'][$sisi][] = [
                'label' => $j->hari.', '.($j->jamPelajaran->label ?? '-').' — '
                    .($j->mapel->nama_mapel ?? '-').' — '.($j->kelas->nama_kelas ?? '-')
                    .' ('.($j->guru->name ?? '-').')',
                'catatan' => $catatan,
            ];
        }

        return $rencana;
    }

    /**
     * Jalankan penyalinan. Dibungkus SATU transaksi supaya tidak pernah
     * tersalin sebagian saja kalau ada kegagalan di tengah jalan.
     *
     * @return array<string, int> jumlah baris baru per kategori
     */
    public static function jalankan(TahunAjaran $sumber, TahunAjaran $tujuan): array
    {
        $tahunSama = $sumber->nama === $tujuan->nama;
        $idSumber = self::idTahun($sumber);
        $idTujuan = self::idTahun($tujuan);

        $jumlah = array_fill_keys(array_keys(self::KATEGORI), 0);

        DB::transaction(function () use ($sumber, $tujuan, $tahunSama, $idSumber, $idTujuan, &$jumlah) {
            if (! $tahunSama) {
                // --- 1..4: master data sederhana (salin kolomnya apa adanya) ---
                foreach (self::daftarMaster() as $kunci => $master) {
                    [$model, $kunciBaris, , $kolom] = $master;
                    $adaDiTujuan = self::petaBaris($model, $idTujuan, $kunciBaris);

                    foreach (self::baris($model, $idSumber) as $baris) {
                        if (isset($adaDiTujuan[$kunciBaris($baris)])) {
                            continue;
                        }

                        $baru = $model::create(
                            collect($kolom)->mapWithKeys(fn ($k) => [$k => $baris->{$k}])->all()
                                + ['tahun_ajaran_id' => $idTujuan]
                        );
                        $jumlah[$kunci]++;

                        // Ekstrakurikuler: pembinanya ikut, anggotanya TIDAK
                        // (lihat catatan kelas ini).
                        if ($model === Ekstrakurikuler::class) {
                            foreach ($baris->pembinas as $p) {
                                EkstrakurikulerPembina::create([
                                    'ekstrakurikuler_id' => $baru->id,
                                    'user_id' => $p->user_id,
                                    'nama_eksternal' => $p->nama_eksternal,
                                    'kontak_eksternal' => $p->kontak_eksternal,
                                ]);
                            }
                        }
                    }
                }

                // --- 5: kelas (+ wali kelas sebagai titik awal) ---
                $kelasTujuan = self::petaBaris(Kelas::class, $idTujuan, self::kunciKelas());
                foreach (self::baris(Kelas::class, $idSumber, [], ['tingkat', 'nama_kelas']) as $k) {
                    if (isset($kelasTujuan[self::kunciKelas()($k)])) {
                        continue;
                    }
                    $baru = Kelas::firstOrCreate(
                        ['tahun_ajaran_id' => $idTujuan, 'tingkat' => $k->tingkat, 'nama_kelas' => $k->nama_kelas],
                        ['wali_kelas_id' => $k->wali_kelas_id]
                    );
                    if ($baru->wasRecentlyCreated) {
                        $jumlah['kelas']++;
                    }
                }
            }

            // --- 6..8: mapping & jadwal. Peta dihitung SETELAH master data
            //     dibuat, supaya setiap penunjuk sudah menemukan barisnya. ---
            $peta = self::petaPenjodohan($idSumber, $idTujuan, $tahunSama);

            foreach (self::mengajarSumber($sumber->id) as $m) {
                $kelas = $peta['kelas'][$m->kelas_id] ?? null;
                $mapel = $peta['mapel'][$m->mata_pelajaran_id] ?? null;
                if (! $kelas || ! $mapel) {
                    continue;
                }

                $baru = GuruMengajarKelas::firstOrCreate([
                    'tahun_ajaran_id' => $tujuan->id,
                    'guru_id' => $m->guru_id,
                    'kelas_id' => $kelas,
                    'mata_pelajaran_id' => $mapel,
                ]);
                if ($baru->wasRecentlyCreated) {
                    $jumlah['mengajar']++;
                }
            }

            foreach (self::guruBkSumber($sumber->id) as $gb) {
                $kelas = $peta['kelas'][$gb->kelas_id] ?? null;
                if (! $kelas) {
                    continue;
                }

                $baru = GuruBkKelas::firstOrCreate([
                    'tahun_ajaran_id' => $tujuan->id,
                    'guru_id' => $gb->guru_id,
                    'kelas_id' => $kelas,
                ]);
                if ($baru->wasRecentlyCreated) {
                    $jumlah['guru_bk']++;
                }
            }

            foreach (self::jadwalSumber($sumber->id) as $j) {
                $kelas = $peta['kelas'][$j->kelas_id] ?? null;
                $mapel = $peta['mapel'][$j->mata_pelajaran_id] ?? null;
                $jam = $peta['jam'][$j->jam_pelajaran_id] ?? null;
                if (! $kelas || ! $mapel || ! $jam) {
                    continue;
                }

                $baru = JadwalPelajaran::firstOrCreate([
                    'tahun_ajaran_id' => $tujuan->id,
                    'hari' => $j->hari,
                    'kelas_id' => $kelas,
                    'jam_pelajaran_id' => $jam,
                ], [
                    'mata_pelajaran_id' => $mapel,
                    'guru_id' => $j->guru_id,
                ]);
                if ($baru->wasRecentlyCreated) {
                    $jumlah['jadwal']++;
                }
            }
        });

        return $jumlah;
    }

    // =================================================================
    // Bagian dalam
    // =================================================================

    /**
     * Definisi master data sederhana:
     * kunci => [kelas model, penghasil kunci penjodohan, penghasil label, kolom yang disalin].
     */
    private static function daftarMaster(): array
    {
        return [
            'mapel' => [
                MataPelajaran::class,
                fn ($b) => mb_strtolower(trim((string) $b->kode)),
                fn ($b) => $b->kode.' — '.$b->nama_mapel,
                ['kode', 'nama_mapel'],
            ],
            'jam' => [
                JamPelajaran::class,
                fn ($b) => $b->hari.'|'.$b->jam_ke,
                fn ($b) => $b->hari.' — jam ke-'.$b->jam_ke.' ('.substr((string) $b->jam_mulai, 0, 5).'–'.substr((string) $b->jam_selesai, 0, 5).')',
                ['hari', 'jam_ke', 'jam_mulai', 'jam_selesai', 'is_active'],
            ],
            'pelanggaran' => [
                JenisPelanggaran::class,
                fn ($b) => mb_strtolower(trim((string) $b->kode)),
                fn ($b) => $b->kode.' — '.$b->nama.' ('.$b->kategori.', '.$b->poin_default.' poin)',
                ['kode', 'nama', 'kategori', 'poin_default', 'is_active'],
            ],
            'jenis_surat' => [
                JenisSurat::class,
                fn ($b) => mb_strtolower(trim((string) ($b->kode_jenis ?: $b->nama_jenis))),
                fn ($b) => ($b->kode_jenis ? $b->kode_jenis.' — ' : '').$b->nama_jenis,
                ['nama_jenis', 'kode_jenis', 'kategori', 'tipe_formulir', 'template_isi', 'is_aktif'],
            ],
            'ekskul' => [
                Ekstrakurikuler::class,
                fn ($b) => mb_strtolower(trim((string) $b->nama_ekstrakurikuler)),
                fn ($b) => $b->nama_ekstrakurikuler,
                ['nama_ekstrakurikuler', 'keterangan', 'is_aktif'],
            ],
        ];
    }

    private static function kunciKelas(): callable
    {
        return fn ($k) => $k->tingkat.'|'.$k->nama_kelas;
    }

    /**
     * Baris master milik SATU periode. Sengaja memakai where() langsung,
     * BUKAN scope untukTahunAjaran() — scope itu ikut memuat baris ber-
     * tahun_ajaran_id NULL (data instalasi lama), yang kalau ikut terbaca
     * di sini akan tampak ada di kedua periode sekaligus.
     */
    private static function baris(string $model, ?int $idTahun, array $muat = [], array $urut = ['id'])
    {
        $q = $model::query()->where('tahun_ajaran_id', $idTahun);

        foreach ($muat as $relasi) {
            $q->with($relasi);
        }
        foreach ($urut as $kolom) {
            $q->orderBy($kolom);
        }

        return $q->get();
    }

    /** Peta "kunci penjodohan => baris" untuk satu periode. */
    private static function petaBaris(string $model, ?int $idTahun, callable $kunci): array
    {
        $peta = [];
        foreach (self::baris($model, $idTahun) as $baris) {
            $peta[$kunci($baris)] = $baris;
        }

        return $peta;
    }

    /**
     * Peta id-sumber => id-tujuan untuk mata pelajaran, jam pelajaran, dan
     * kelas. Kalau sumber & tujuan satu tahun ajaran (beda semester saja),
     * ketiganya memang baris yang sama sehingga dipetakan ke dirinya sendiri.
     *
     * @return array{mapel: array<int,int>, jam: array<int,int>, kelas: array<int,int>}
     */
    private static function petaPenjodohan(?int $idSumber, ?int $idTujuan, bool $tahunSama): array
    {
        $bangun = function (string $model, callable $kunci) use ($idSumber, $idTujuan, $tahunSama) {
            $sumber = self::baris($model, $idSumber);

            if ($tahunSama) {
                return $sumber->mapWithKeys(fn ($b) => [$b->id => $b->id])->all();
            }

            $tujuan = self::petaBaris($model, $idTujuan, $kunci);
            $peta = [];
            foreach ($sumber as $b) {
                $pasangan = $tujuan[$kunci($b)] ?? null;
                if ($pasangan) {
                    $peta[$b->id] = $pasangan->id;
                }
            }

            return $peta;
        };

        $master = self::daftarMaster();

        return [
            'mapel' => $bangun(MataPelajaran::class, $master['mapel'][1]),
            'jam' => $bangun(JamPelajaran::class, $master['jam'][1]),
            'kelas' => $bangun(Kelas::class, self::kunciKelas()),
        ];
    }

    /*
     * Mapping guru & jadwal disimpan PER SEMESTER (tahun_ajaran_id-nya baris
     * semester itu sendiri), berbeda dengan master data & kelas yang memakai
     * baris Ganjil. Karena itu ketiga pembacaan di bawah memakai id SEMESTER
     * yang dipilih admin apa adanya — bukan id Ganjil-nya.
     */

    private static function mengajarSumber(int $idSemester)
    {
        return GuruMengajarKelas::with(['guru', 'kelas', 'mapel'])
            ->where('tahun_ajaran_id', $idSemester)
            ->get()->filter(fn ($m) => $m->kelas !== null);
    }

    private static function guruBkSumber(int $idSemester)
    {
        return GuruBkKelas::with(['guru', 'kelas'])
            ->where('tahun_ajaran_id', $idSemester)
            ->get()->filter(fn ($gb) => $gb->kelas !== null);
    }

    private static function jadwalSumber(int $idSemester)
    {
        return JadwalPelajaran::with(['guru', 'kelas', 'mapel', 'jamPelajaran'])
            ->where('tahun_ajaran_id', $idSemester)
            ->get()->filter(fn ($j) => $j->kelas !== null);
    }

    private static function periksaMengajar($m, array $peta, ?int $idTujuan): array
    {
        $kelas = $peta['kelas'][$m->kelas_id] ?? null;
        $mapel = $peta['mapel'][$m->mata_pelajaran_id] ?? null;

        if (! $kelas) {
            return ['disalin', 'kelasnya dibuat lebih dulu dalam aksi yang sama'];
        }
        if (! $mapel) {
            return ['disalin', 'mata pelajarannya dibuat lebih dulu dalam aksi yang sama'];
        }

        $ada = GuruMengajarKelas::where('tahun_ajaran_id', $idTujuan)
            ->where('guru_id', $m->guru_id)->where('kelas_id', $kelas)
            ->where('mata_pelajaran_id', $mapel)->exists();

        return $ada ? ['sudah_ada', null] : ['disalin', null];
    }

    private static function periksaGuruBk($gb, array $peta, ?int $idTujuan): array
    {
        $kelas = $peta['kelas'][$gb->kelas_id] ?? null;

        if (! $kelas) {
            return ['disalin', 'kelasnya dibuat lebih dulu dalam aksi yang sama'];
        }

        $ada = GuruBkKelas::where('tahun_ajaran_id', $idTujuan)
            ->where('guru_id', $gb->guru_id)->where('kelas_id', $kelas)->exists();

        return $ada ? ['sudah_ada', null] : ['disalin', null];
    }

    private static function periksaJadwal($j, array $peta, ?int $idTujuan): array
    {
        $kelas = $peta['kelas'][$j->kelas_id] ?? null;
        $jam = $peta['jam'][$j->jam_pelajaran_id] ?? null;
        $mapel = $peta['mapel'][$j->mata_pelajaran_id] ?? null;

        if (! $kelas || ! $jam || ! $mapel) {
            return ['disalin', 'kelas/mapel/jam pelajarannya dibuat lebih dulu dalam aksi yang sama'];
        }

        $ada = JadwalPelajaran::where('tahun_ajaran_id', $idTujuan)
            ->where('hari', $j->hari)->where('kelas_id', $kelas)
            ->where('jam_pelajaran_id', $jam)->exists();

        return $ada ? ['sudah_ada', null] : ['disalin', null];
    }

    /** id baris Semester Ganjil — konvensi penyimpanan master data & kelas. */
    private static function idTahun(TahunAjaran $periode): ?int
    {
        return $periode->semester === 'Ganjil'
            ? $periode->id
            : TahunAjaran::idSemesterGanjilUntukNama($periode->nama);
    }
}
