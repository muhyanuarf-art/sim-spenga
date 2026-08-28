<?php

namespace App\Http\Controllers;

use App\Support\PesanAksesKelas;
use App\Models\AbsensiEkskulPeserta;
use App\Models\AbsensiSiswa;
use App\Models\EkstrakurikulerSiswa;
use App\Models\GuruMengajarKelas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\PoinSiswaService;
use App\Support\PeriodeAkademik;
use App\Support\PeringkatKelas;
use App\Support\RentangPeriode;
use App\Support\SkemaPenilaian;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * LAPORAN AKHIR SEMESTER — satu lembar rekap untuk RAPAT PENERIMAAN RAPOR.
 *
 * Selama ini wali kelas harus membuka empat menu berbeda untuk menyiapkan
 * rapat penerimaan rapor: Nilai Rapor Kelas, Rekap Absensi (per bulan,
 * jadi enam kali buka), profil poin BK satu per satu, dan rekap
 * ekstrakurikuler. Halaman ini menggabungkan keempatnya menjadi SATU tabel
 * untuk SATU SEMESTER PENUH, plus totalnya per item.
 *
 * EMPAT ITEM YANG DIREKAP
 * =======================
 * 1. NILAI          — rata-rata nilai akhir seluruh mapel, peringkat kelas,
 *                     dan berapa mapel yang belum tuntas.
 * 2. KEHADIRAN      — Hadir/Sakit/Izin/Alfa sepanjang semester.
 * 3. KEDISIPLINAN   — jumlah kasus, poin pelanggaran, poin aktif, status BK.
 * 4. EKSTRAKURIKULER— kegiatan yang diikuti dan persentase kehadirannya.
 *
 * SEMUANYA MEMAKAI SUMBER YANG SAMA dengan halaman aslinya, bukan
 * perhitungan baru:
 * - peringkat lewat App\Support\PeringkatKelas (dipakai bersama Rekap Nilai
 *   Rapor Kelas, supaya kedua lembar tidak pernah berbeda peringkat);
 * - kehadiran lewat AbsensiSiswa::finalPerHari() (aturan yang sama dengan
 *   Rekap Absensi Kelas: absensi kegiatan sekolah menang, kalau tidak ada
 *   maka status dari guru mapel jam paling akhir hari itu);
 * - poin BK lewat PoinSiswaService::ringkasanBanyak() (rumus yang sama
 *   dengan profil poin siswa, dan tanpa N+1).
 *
 * RENTANG WAKTUNYA
 * ================
 * Bawaannya seluruh semester (tanggal mulai s.d. selesai pada Tahun
 * Ajaran aktif — kurang lebih enam bulan). Operator boleh mempersempit
 * lewat isian Dari/Sampai, mis. untuk melihat perkembangan satu triwulan.
 *
 * HAK AKSES — sama dengan laporan rapor lain (NilaiWaliKelasController):
 * Guru terkunci ke kelas perwaliannya, Guru BK ke kelas binaannya,
 * Kurikulum/Kepala Sekolah/Admin bebas memilih kelas.
 */
class LaporanSemesterController extends Controller
{
    public function index(Request $request, PoinSiswaService $poinService, ?Kelas $kelas = null)
    {
        $periode = $this->periodeAktif();
        $daftarKelas = $this->daftarKelasPilihan($request->user());
        $kelas = $this->resolveKelas($request, $kelas);

        [$mulai, $selesai, $tanggalDiturunkan] = $this->rentangSemester($periode, $request);

        $skema = SkemaPenilaian::untuk($periode, (int) $kelas->tingkat);
        $siswas = $this->daftarSiswa($kelas, $periode);
        $siswaIds = $siswas->pluck('id');

        $mapels = $this->mapelKelas($kelas, $periode);
        $nilaiPerSiswa = NilaiSiswa::where('tahun_ajaran_id', $periode->id)
            ->whereIn('siswa_id', $siswaIds)
            ->whereIn('mata_pelajaran_id', $mapels->pluck('id'))
            ->get()
            ->groupBy('siswa_id');

        $kehadiran = $this->rekapKehadiran($kelas, $siswaIds, $mulai, $selesai);
        $poin = $poinService->ringkasanBanyak($siswaIds);
        $ekskul = $this->rekapEkstrakurikuler($siswaIds, $mulai, $selesai, $periode);

        // ===== Satu baris per peserta didik =====
        $baris = $siswas->map(function (Siswa $siswa) use ($nilaiPerSiswa, $mapels, $skema, $kehadiran, $poin, $ekskul) {
            $milik = ($nilaiPerSiswa->get($siswa->id) ?? collect())->keyBy('mata_pelajaran_id');
            $na = $mapels->map(fn (MataPelajaran $m) => $milik->get($m->id)?->nilai_akhir)
                ->filter(fn ($n) => $n !== null);

            return [
                'siswa' => $siswa,
                // --- item 1: nilai ---
                'mapel_dinilai' => $na->count(),
                'rata' => $na->isEmpty() ? null : round($na->avg(), 2),
                'belum_tuntas' => $na->filter(fn ($n) => $n < $skema->kktpMin)->count(),
                'mapel_belum_tuntas' => $mapels
                    ->filter(fn (MataPelajaran $m) => ($milik->get($m->id)?->nilai_akhir ?? null) !== null
                        && $milik->get($m->id)->nilai_akhir < $skema->kktpMin)
                    ->pluck('nama_mapel')->values(),
                // --- item 2: kehadiran ---
                'hadir' => $kehadiran[$siswa->id]['hadir'] ?? 0,
                'sakit' => $kehadiran[$siswa->id]['sakit'] ?? 0,
                'izin' => $kehadiran[$siswa->id]['izin'] ?? 0,
                'alfa' => $kehadiran[$siswa->id]['alfa'] ?? 0,
                'hari_tercatat' => $kehadiran[$siswa->id]['hari'] ?? 0,
                'persen_hadir' => $kehadiran[$siswa->id]['persen'] ?? null,
                // --- item 3: kedisiplinan ---
                'jumlah_kasus' => $poin[$siswa->id]['jumlah_kasus'] ?? 0,
                'poin_pelanggaran' => $poin[$siswa->id]['total_pelanggaran'] ?? 0,
                'poin_aktif' => $poin[$siswa->id]['poin_aktif'] ?? 0,
                'status_bk' => $poin[$siswa->id]['status'] ?? 'Normal',
                // --- item 4: ekstrakurikuler ---
                'ekskul_nama' => $ekskul[$siswa->id]['nama'] ?? collect(),
                'ekskul_sesi' => $ekskul[$siswa->id]['sesi'] ?? 0,
                'ekskul_hadir' => $ekskul[$siswa->id]['hadir'] ?? 0,
                'ekskul_persen' => $ekskul[$siswa->id]['persen'] ?? null,
            ];
        });

        $baris = PeringkatKelas::bubuhkan($baris);
        $ringkasan = $this->ringkasanKelas($baris, $skema);
        $perluPerhatian = $this->perluPerhatian($baris, $skema);

        return view('laporan.akhir-semester', compact(
            'kelas', 'periode', 'skema', 'mapels', 'baris', 'ringkasan', 'perluPerhatian',
            'daftarKelas', 'mulai', 'selesai', 'tanggalDiturunkan'
        ));
    }

    // =================================================================
    // ITEM 2 — KEHADIRAN
    // =================================================================

    /**
     * Rekap Hadir/Sakit/Izin/Alfa sepanjang rentang, memakai aturan
     * "satu status final per hari" yang sama persis dengan Rekap Absensi
     * Kelas (AbsensiSiswa::finalPerHari) — supaya angka di laporan ini
     * tidak pernah berbeda dengan rekap bulanan yang sudah dipakai.
     *
     * Difilter dengan kelas_id kelas ini: kolom itu SNAPSHOT permanen saat
     * absensi dicatat, jadi hari-hari ketika siswa masih di kelas lain
     * memang bukan tanggung jawab wali kelas ini.
     */
    private function rekapKehadiran(Kelas $kelas, $siswaIds, Carbon $mulai, Carbon $selesai): array
    {
        if ($siswaIds->isEmpty()) {
            return [];
        }

        $records = AbsensiSiswa::whereIn('siswa_id', $siswaIds)
            ->where('kelas_id', $kelas->id)
            ->whereBetween('tanggal', [$mulai, $selesai])
            ->with(AbsensiSiswa::RELASI_KONTEKS)
            ->get()
            ->groupBy('siswa_id');

        $hasil = [];
        foreach ($records as $siswaId => $milik) {
            $rekap = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alfa' => 0];

            foreach (AbsensiSiswa::finalPerHari($milik) as $final) {
                $kunci = match ($final->status) {
                    'Sakit' => 'sakit',
                    'Izin' => 'izin',
                    'Alfa' => 'alfa',
                    default => 'hadir',
                };
                $rekap[$kunci]++;
            }

            $hari = array_sum($rekap);
            $hasil[$siswaId] = $rekap + [
                'hari' => $hari,
                // Persentase kehadiran dihitung dari HARI YANG TERCATAT,
                // bukan dari jumlah hari kalender — guru tidak selalu
                // sempat mengisi absensi tiap hari, dan hari yang tidak
                // terisi bukan berarti siswa tidak masuk.
                'persen' => $hari > 0 ? round($rekap['hadir'] / $hari * 100, 1) : null,
            ];
        }

        return $hasil;
    }

    // =================================================================
    // ITEM 4 — EKSTRAKURIKULER
    // =================================================================

    /**
     * Kegiatan ekstrakurikuler yang diikuti tiap siswa beserta persentase
     * kehadirannya sepanjang rentang. Keanggotaan ekstrakurikuler tidak
     * terikat tahun ajaran di aplikasi ini, jadi yang dibatasi rentang
     * waktunya adalah SESI ABSENSI-nya (absensi_ekskuls.tanggal).
     */
    private function rekapEkstrakurikuler($siswaIds, Carbon $mulai, Carbon $selesai, TahunAjaran $periode): array
    {
        if ($siswaIds->isEmpty()) {
            return [];
        }

        // Keanggotaan disaring lewat ekstrakurikulernya, yang sejak
        // 2026-08-28 sudah terikat tahun ajaran. Tanpa ini, keanggotaan
        // tahun-tahun sebelumnya ikut terbawa ke laporan semester ini.
        $keanggotaan = EkstrakurikulerSiswa::with('ekstrakurikuler')
            ->whereHas('ekstrakurikuler', fn ($q) => $q->untukTahunAjaran($periode))
            ->whereIn('siswa_id', $siswaIds)
            ->get()
            ->groupBy('siswa_id');

        $absensi = AbsensiEkskulPeserta::whereIn('siswa_id', $siswaIds)
            ->whereHas('absensiEkskul', fn ($q) => $q->whereBetween('tanggal', [$mulai, $selesai]))
            ->get()
            ->groupBy('siswa_id');

        $hasil = [];
        foreach ($siswaIds as $siswaId) {
            $sesi = $absensi->get($siswaId) ?? collect();
            $hadir = $sesi->where('status', 'Hadir')->count();

            $hasil[$siswaId] = [
                'nama' => ($keanggotaan->get($siswaId) ?? collect())
                    ->map(fn ($k) => $k->ekstrakurikuler?->nama_ekstrakurikuler)
                    ->filter()->values(),
                'sesi' => $sesi->count(),
                'hadir' => $hadir,
                'persen' => $sesi->count() > 0 ? round($hadir / $sesi->count() * 100, 1) : null,
            ];
        }

        return $hasil;
    }

    // =================================================================
    // TOTAL PER ITEM (bahan paparan rapat)
    // =================================================================

    private function ringkasanKelas($baris, SkemaPenilaian $skema): array
    {
        $rata = $baris->pluck('rata')->filter(fn ($r) => $r !== null);
        $hariTercatat = $baris->sum('hari_tercatat');

        return [
            'jumlah_siswa' => $baris->count(),

            // Item 1 — nilai
            'dinilai' => $baris->filter(fn ($b) => $b['mapel_dinilai'] > 0)->count(),
            'rata_kelas' => $rata->isEmpty() ? null : round($rata->avg(), 2),
            'rata_tertinggi' => $rata->max(),
            'rata_terendah' => $rata->min(),
            'tuntas_semua' => $baris->filter(fn ($b) => $b['mapel_dinilai'] > 0 && $b['belum_tuntas'] === 0)->count(),
            'ada_belum_tuntas' => $baris->filter(fn ($b) => $b['belum_tuntas'] > 0)->count(),

            // Item 2 — kehadiran
            'total_hadir' => $baris->sum('hadir'),
            'total_sakit' => $baris->sum('sakit'),
            'total_izin' => $baris->sum('izin'),
            'total_alfa' => $baris->sum('alfa'),
            'hari_tercatat' => $hariTercatat,
            'persen_hadir_kelas' => $hariTercatat > 0
                ? round($baris->sum('hadir') / $hariTercatat * 100, 1)
                : null,

            // Item 3 — kedisiplinan
            'siswa_berkasus' => $baris->filter(fn ($b) => $b['jumlah_kasus'] > 0)->count(),
            'total_kasus' => $baris->sum('jumlah_kasus'),
            'total_poin_aktif' => $baris->sum('poin_aktif'),
            'dalam_pembinaan' => $baris->filter(fn ($b) => $b['status_bk'] === 'Dalam Pembinaan')->count(),

            // Item 4 — ekstrakurikuler
            'ikut_ekskul' => $baris->filter(fn ($b) => $b['ekskul_nama']->isNotEmpty())->count(),
            'total_sesi_ekskul' => $baris->sum('ekskul_sesi'),
            'persen_ekskul_kelas' => $baris->sum('ekskul_sesi') > 0
                ? round($baris->sum('ekskul_hadir') / $baris->sum('ekskul_sesi') * 100, 1)
                : null,
        ];
    }

    /**
     * Peserta didik yang perlu dibicarakan khusus di rapat, beserta
     * ALASANNYA — supaya wali kelas tidak perlu menyisir tabel satu per
     * satu untuk menemukan siapa yang bermasalah.
     */
    private function perluPerhatian($baris, SkemaPenilaian $skema)
    {
        return $baris->map(function ($b) use ($skema) {
            $alasan = [];

            if ($b['belum_tuntas'] > 0) {
                $alasan[] = $b['belum_tuntas'].' mapel di bawah KKTP ('.$b['mapel_belum_tuntas']->implode(', ').')';
            }
            // Tiga hari alfa dalam satu semester sudah cukup menjadi bahan
            // pembicaraan dengan orang tua — di bawah itu biasanya insidental.
            if ($b['alfa'] >= 3) {
                $alasan[] = $b['alfa'].' hari tanpa keterangan (Alfa)';
            }
            if ($b['poin_aktif'] > 0) {
                $alasan[] = 'poin pelanggaran aktif '.$b['poin_aktif'].' ('.$b['status_bk'].')';
            }
            if ($b['persen_hadir'] !== null && $b['persen_hadir'] < 90 && $b['hari_tercatat'] >= 10) {
                // Koma sebagai pemisah desimal, sama dengan angka lain di
                // lembar ini (dokumen resmi berbahasa Indonesia).
                $alasan[] = 'kehadiran '.rtrim(rtrim(number_format($b['persen_hadir'], 1, ',', ''), '0'), ',').'%';
            }

            return ['siswa' => $b['siswa'], 'alasan' => $alasan, 'rata' => $b['rata']];
        })->filter(fn ($x) => ! empty($x['alasan']))->values();
    }

    // =================================================================
    // internal
    // =================================================================

    private function periodeAktif(): TahunAjaran
    {
        $periode = PeriodeAkademik::aktif();

        abort_if($periode === null, 409, 'Belum ada Tahun Ajaran yang aktif. Hubungi Kurikulum/Admin untuk mengaktifkan periode terlebih dahulu.');

        return $periode;
    }

    /**
     * Rentang tanggal laporan.
     *
     * Urutan sumbernya: isian Dari/Sampai dari operator, kalau kosong pakai
     * tanggal mulai & selesai Tahun Ajaran, dan kalau ITU pun belum diisi
     * admin, diturunkan dari nama tahun ajaran + semesternya (Ganjil =
     * Juli–Desember tahun pertama, Genap = Januari–Juni tahun kedua).
     * Penurunan ini ditandai supaya halaman bisa mengingatkan admin untuk
     * melengkapi tanggal periode.
     *
     * @return array{0: Carbon, 1: Carbon, 2: bool}
     */
    private function rentangSemester(TahunAjaran $periode, Request $request): array
    {
        // Rentangnya diambil dari App\Support\RentangPeriode — SUMBER YANG
        // SAMA dipakai App\Rules\DalamPeriode saat data disimpan. Ini
        // disengaja: apa pun yang boleh disimpan pada periode ini pasti
        // ikut terhitung di laporan ini, sehingga tidak mungkin lagi ada
        // data yang "tersimpan tapi tidak pernah muncul di laporan".
        $rentang = RentangPeriode::untuk($periode);

        if ($rentang !== null) {
            [$mulai, $selesai, $diturunkan] = [$rentang[0]->copy(), $rentang[1]->copy(), $rentang[2]];
        } else {
            // Nama tahun ajarannya tidak berpola "YYYY/YYYY" — ambil enam
            // bulan terakhir supaya halaman ini tetap berguna.
            $diturunkan = true;
            [$mulai, $selesai] = [now()->copy()->subMonths(6)->startOfDay(), now()->copy()->endOfDay()];
        }

        if ($request->filled('dari')) {
            $mulai = Carbon::parse($request->get('dari'));
        }
        if ($request->filled('sampai')) {
            $selesai = Carbon::parse($request->get('sampai'));
        }

        // Kalau operator membalik urutannya, tukar saja daripada
        // mengembalikan tabel kosong tanpa penjelasan.
        if ($mulai->gt($selesai)) {
            [$mulai, $selesai] = [$selesai, $mulai];
        }

        return [$mulai->startOfDay(), $selesai->endOfDay(), $diturunkan];
    }

    /** Cadangan kalau tanggal periode belum diisi admin. */

    private function mapelKelas(Kelas $kelas, TahunAjaran $periode)
    {
        $dariMapping = GuruMengajarKelas::where('tahun_ajaran_id', $periode->id)
            ->where('kelas_id', $kelas->id)
            ->pluck('mata_pelajaran_id');

        $dariNilai = NilaiSiswa::where('tahun_ajaran_id', $periode->id)
            ->where('kelas_id', $kelas->id)
            ->distinct()
            ->pluck('mata_pelajaran_id');

        return MataPelajaran::whereIn('id', $dariMapping->merge($dariNilai)->unique())
            ->orderBy('nama_mapel')
            ->get();
    }

    private function daftarSiswa(Kelas $kelas, TahunAjaran $periode)
    {
        $idSekarang = $kelas->siswas()->where('is_active', true)->pluck('siswas.id');
        $idBernilai = NilaiSiswa::where('kelas_id', $kelas->id)
            ->where('tahun_ajaran_id', $periode->id)
            ->distinct()
            ->pluck('siswa_id');

        return Siswa::whereIn('id', $idSekarang->merge($idBernilai)->unique())
            ->orderBy('nama')
            ->get();
    }

    private function daftarKelasPilihan($user)
    {
        if ($user->isGuruBk()) {
            return $user->kelasBk();
        }

        if ($user->isAdmin() || $user->isKurikulum() || $user->isKepalaSekolah()) {
            return Kelas::aktif()->orderBy('nama_kelas')->get();
        }

        return collect(array_filter([$user->kelasWali]));
    }

    private function resolveKelas(Request $request, ?Kelas $kelas): Kelas
    {
        $user = $request->user();
        $bolehDilihat = $this->daftarKelasPilihan($user);

        abort_if(
            $bolehDilihat->isEmpty(),
            403,
            $user->isGuruBk()
                ? 'Anda belum di-mapping ke kelas manapun. Hubungi Kurikulum/Admin.'
                : 'Anda bukan Wali Kelas pada tahun ajaran yang sedang aktif.'
        );

        $kelasId = (int) ($request->get('kelas_id') ?: $kelas?->id ?: $bolehDilihat->first()->id);

        abort_unless($bolehDilihat->contains('id', $kelasId), 403, PesanAksesKelas::tolak($kelasId));

        return $bolehDilihat->firstWhere('id', $kelasId);
    }
}
