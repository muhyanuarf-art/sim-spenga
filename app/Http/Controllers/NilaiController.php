<?php

namespace App\Http\Controllers;

use App\Models\AnggotaKelas;
use App\Models\GuruMengajarKelas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiSiswa;
use App\Models\PenilaianKelasMapel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Support\PeriodeAkademik;
use App\Support\SkemaPenilaian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DAFTAR NILAI — diisi GURU MATA PELAJARAN.
 *
 * Satu lembar daftar nilai = 1 kelas × 1 mata pelajaran × 1 periode
 * (tahun ajaran + semester), persis seperti lembar Excel yang selama ini
 * dipakai guru. Isinya:
 *
 *   FORMATIF   TPF 1..7  → RT
 *   SUMATIF LINGKUP MATERI  LM 1..4, masing-masing SUM + REM → RT
 *   ASTS  ·  ASAS/ASAT  ·  NILAI AKHIR (RAPOR)
 *
 * Nilai akhir yang dihitung di sini OTOMATIS menjadi bahan laporan wali
 * kelas (lihat NilaiWaliKelasController) — wali kelas tidak perlu
 * menyalin apa pun.
 *
 * HAK AKSES
 * - Guru: hanya kelas & mapel yang di-mapping-kan kepadanya oleh
 *   Kurikulum pada periode aktif (tabel guru_mengajar_kelas).
 * - Admin: boleh mengisi lembar mana pun (mewakili guru yang berhalangan),
 *   konsisten dengan MengajarController & AbsensiKegiatanController.
 * - Kurikulum & Kepala Sekolah: boleh MEMBUKA lembar mana pun untuk
 *   memeriksa/mencetak, tapi tidak boleh menyimpan.
 */
class NilaiController extends Controller
{
    /** Daftar lembar nilai yang menjadi tanggung jawab pengguna ini. */
    public function pilih(Request $request)
    {
        $user = $request->user();
        $periode = $this->periodeAktif();

        $mapping = $this->mappingUntuk($user, $periode);

        $status = PenilaianKelasMapel::where('tahun_ajaran_id', $periode->id)
            ->get()
            ->keyBy(fn ($h) => $h->kelas_id.'|'.$h->mata_pelajaran_id);

        // Berapa siswa yang nilai akhirnya sudah keluar per lembar —
        // dipakai untuk bar kemajuan pengisian di halaman pilih.
        $terisi = NilaiSiswa::where('tahun_ajaran_id', $periode->id)
            ->whereNotNull('nilai_akhir')
            ->selectRaw('kelas_id, mata_pelajaran_id, COUNT(*) as jumlah')
            ->groupBy('kelas_id', 'mata_pelajaran_id')
            ->get()
            ->keyBy(fn ($n) => $n->kelas_id.'|'.$n->mata_pelajaran_id);

        // Jumlah siswa dihitung dari tabel keanggotaan: sejak
        // 2026_08_29_000001 kelas siswa disimpan per SEMESTER di sana,
        // bukan lagi kolom di tabel siswas.
        $jumlahSiswaPerKelas = AnggotaKelas::whereIn('kelas_id', $mapping->pluck('kelas_id')->unique())
            ->whereHas('siswa', fn ($q) => $q->where('is_active', true))
            ->selectRaw('kelas_id, COUNT(*) as jumlah')
            ->groupBy('kelas_id')
            ->pluck('jumlah', 'kelas_id');

        $lembar = $mapping->map(function (GuruMengajarKelas $m) use ($status, $terisi, $jumlahSiswaPerKelas) {
            $kunci = $m->kelas_id.'|'.$m->mata_pelajaran_id;
            $totalSiswa = (int) ($jumlahSiswaPerKelas[$m->kelas_id] ?? 0);
            $sudah = (int) ($terisi[$kunci]->jumlah ?? 0);

            return [
                'kelas' => $m->kelas,
                'mapel' => $m->mapel,
                'guru' => $m->guru,
                'header' => $status->get($kunci),
                'total_siswa' => $totalSiswa,
                'sudah_dinilai' => $sudah,
                'persen' => $totalSiswa > 0 ? (int) round($sudah / $totalSiswa * 100) : 0,
            ];
        })->sortBy([
            fn ($a, $b) => strcmp($a['kelas']->nama_kelas ?? '', $b['kelas']->nama_kelas ?? ''),
            fn ($a, $b) => strcmp($a['mapel']->nama_mapel ?? '', $b['mapel']->nama_mapel ?? ''),
        ])->values();

        return view('nilai.pilih', compact('lembar', 'periode'));
    }

    /** Lembar daftar nilai satu kelas × satu mapel. */
    public function form(Request $request, Kelas $kelas, MataPelajaran $mapel)
    {
        $periode = $this->periodeAktif();
        $bolehIsi = $this->bolehMengisi($request->user(), $kelas, $mapel, $periode);

        abort_unless(
            $bolehIsi || $this->bolehMemantau($request->user()),
            403,
            'Anda tidak mengampu mata pelajaran ini di kelas tersebut.'
        );

        $skema = SkemaPenilaian::untuk($periode, (int) $kelas->tingkat);
        $header = $this->header($kelas, $mapel, $periode);
        $siswas = $this->daftarSiswa($kelas, $mapel, $periode);

        $nilai = NilaiSiswa::where('tahun_ajaran_id', $periode->id)
            ->where('mata_pelajaran_id', $mapel->id)
            ->whereIn('siswa_id', $siswas->pluck('id'))
            ->get()
            ->keyBy('siswa_id');

        // Baris kosong untuk siswa yang belum punya nilai sama sekali,
        // supaya view tidak perlu mengecek null di setiap sel.
        $baris = $siswas->map(function (Siswa $siswa) use ($nilai, $kelas, $mapel, $periode, $skema) {
            $n = $nilai->get($siswa->id) ?? new NilaiSiswa([
                'siswa_id' => $siswa->id,
                'kelas_id' => $kelas->id,
                'mata_pelajaran_id' => $mapel->id,
                'tahun_ajaran_id' => $periode->id,
            ]);

            return ['siswa' => $siswa, 'nilai' => $n->hitungUlang($skema)];
        });

        $statistik = $this->statistik($baris, $skema);
        $guruPengampu = $header->guru ?? $this->guruPengampu($kelas, $mapel, $periode);

        return view('nilai.daftar-nilai', compact(
            'kelas', 'mapel', 'periode', 'skema', 'header', 'baris',
            'bolehIsi', 'statistik', 'guruPengampu'
        ));
    }

    /** Simpan seluruh lembar sekaligus (semua siswa dalam 1 kali submit). */
    public function store(Request $request, Kelas $kelas, MataPelajaran $mapel)
    {
        $periode = $this->periodeAktif();
        $this->pastikanBolehMengisi($request->user(), $kelas, $mapel, $periode);
        PeriodeAkademik::pastikanTidakTerkunci($periode);

        $header = $this->header($kelas, $mapel, $periode);
        abort_if(
            $header->isFinal(),
            423,
            'Daftar nilai ini sudah difinalisasi dan terkunci. Minta Kurikulum/Admin membuka kuncinya bila memang perlu dikoreksi.'
        );

        $skema = SkemaPenilaian::untuk($periode, (int) $kelas->tingkat);

        // 0–100, boleh desimal, boleh dikosongkan. Kolom yang dikosongkan
        // berarti "belum dinilai" — bukan nol (lihat SkemaPenilaian).
        $aturanNilai = ['nullable', 'numeric', 'min:0', 'max:100'];
        $validated = $request->validate([
            'formatif' => ['nullable', 'array'],
            'formatif.*' => ['nullable', 'array'],
            'formatif.*.*' => $aturanNilai,
            'sumatif' => ['nullable', 'array'],
            'sumatif.*' => ['nullable', 'array'],
            'sumatif.*.*.sum' => $aturanNilai,
            'sumatif.*.*.rem' => $aturanNilai,
            'asts' => ['nullable', 'array'],
            'asts.*' => $aturanNilai,
            'asas' => ['nullable', 'array'],
            'asas.*' => $aturanNilai,
        ], [], [
            'formatif.*.*' => 'nilai TPF',
            'sumatif.*.*.sum' => 'nilai SUM',
            'sumatif.*.*.rem' => 'nilai REM',
            'asts.*' => 'nilai ASTS',
            'asas.*' => 'nilai '.$skema->labelSumatifAkhir(),
        ]);

        // Hanya siswa yang memang ada di lembar ini yang boleh disimpan —
        // id lain yang diselipkan di form ditolak (pola yang sama dengan
        // AbsensiKegiatanController::store).
        $siswaIds = $this->daftarSiswa($kelas, $mapel, $periode)->pluck('id');
        $idDikirim = collect(array_keys($validated['formatif'] ?? []))
            ->merge(array_keys($validated['sumatif'] ?? []))
            ->merge(array_keys($validated['asts'] ?? []))
            ->merge(array_keys($validated['asas'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->unique();

        if ($idDikirim->diff($siswaIds)->isNotEmpty()) {
            abort(422, 'Ada siswa pada data nilai yang bukan anggota kelas ini.');
        }

        DB::transaction(function () use ($siswaIds, $validated, $kelas, $mapel, $periode, $skema, $request, $header) {
            foreach ($siswaIds as $siswaId) {
                $nilai = NilaiSiswa::firstOrNew([
                    'siswa_id' => $siswaId,
                    'mata_pelajaran_id' => $mapel->id,
                    'tahun_ajaran_id' => $periode->id,
                ]);

                $nilai->kelas_id = $kelas->id;
                $nilai->formatif = $this->bersihkanFormatif($validated['formatif'][$siswaId] ?? [], $skema);
                $nilai->sumatif_lm = $this->bersihkanSumatif($validated['sumatif'][$siswaId] ?? [], $skema);
                $nilai->asts = $this->angka($validated['asts'][$siswaId] ?? null);
                $nilai->asas = $this->angka($validated['asas'][$siswaId] ?? null);
                $nilai->diperbarui_oleh_id = $request->user()->id;

                $nilai->hitungUlang($skema)->save();
            }

            $header->update(['guru_id' => $header->guru_id ?? $request->user()->id]);
        });

        return redirect()
            ->route('nilai.form', ['kelas' => $kelas->id, 'mapel' => $mapel->id])
            ->with('success', "Daftar nilai {$mapel->nama_mapel} kelas {$kelas->nama_kelas} berhasil disimpan.");
    }

    /** Kunci lembar supaya nilainya tidak berubah lagi setelah dipakai wali kelas. */
    public function finalisasi(Request $request, Kelas $kelas, MataPelajaran $mapel)
    {
        $periode = $this->periodeAktif();
        $this->pastikanBolehMengisi($request->user(), $kelas, $mapel, $periode);
        PeriodeAkademik::pastikanTidakTerkunci($periode);

        $header = $this->header($kelas, $mapel, $periode);

        // Menolak finalisasi setengah jalan lebih baik daripada rapor
        // keluar dengan siswa bernilai kosong tanpa ada yang sadar.
        // Siswa yang BELUM PUNYA baris nilai sama sekali juga terhitung
        // belum lengkap — makanya yang dihitung siswa yang SUDAH lengkap,
        // lalu dibandingkan dengan jumlah siswa di lembar ini.
        $siswaIds = $this->daftarSiswa($kelas, $mapel, $periode)->pluck('id');
        $sudahLengkap = NilaiSiswa::where('tahun_ajaran_id', $periode->id)
            ->where('mata_pelajaran_id', $mapel->id)
            ->whereIn('siswa_id', $siswaIds)
            ->whereNotNull('nilai_akhir')
            ->where('lengkap', true)
            ->count();

        $belum = $siswaIds->count() - $sudahLengkap;

        if ($belum > 0) {
            return back()->with('error',
                "Belum bisa difinalisasi: masih ada {$belum} siswa yang komponen nilainya belum lengkap "
                .'(FORMATIF/SUMATIF LINGKUP MATERI, ASTS, dan '
                .$this->skemaUntuk($periode, $kelas)->labelSumatifAkhir().' harus terisi semua).'
            );
        }

        $header->update([
            'status' => PenilaianKelasMapel::STATUS_FINAL,
            'difinalisasi_at' => now(),
            'difinalisasi_oleh_id' => $request->user()->id,
            'guru_id' => $header->guru_id ?? $request->user()->id,
        ]);

        return back()->with('success', 'Daftar nilai difinalisasi. Nilai sudah terkunci dan masuk ke laporan wali kelas.');
    }

    /** Buka kembali lembar yang sudah final — KHUSUS Kurikulum & Admin. */
    public function bukaKunci(Request $request, Kelas $kelas, MataPelajaran $mapel)
    {
        $user = $request->user();
        abort_unless(
            $user->isKurikulum() || $user->isAdmin(),
            403,
            'Hanya Kurikulum atau Admin yang dapat membuka kunci daftar nilai.'
        );

        $periode = $this->periodeAktif();
        PeriodeAkademik::pastikanTidakTerkunci($periode);

        $this->header($kelas, $mapel, $periode)->update([
            'status' => PenilaianKelasMapel::STATUS_DRAFT,
            'dibuka_at' => now(),
            'dibuka_oleh_id' => $user->id,
        ]);

        return back()->with('success', 'Kunci daftar nilai dibuka. Guru mata pelajaran dapat mengoreksi nilainya kembali.');
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

    private function skemaUntuk(TahunAjaran $periode, Kelas $kelas): SkemaPenilaian
    {
        return SkemaPenilaian::untuk($periode, (int) $kelas->tingkat);
    }

    /** Pemetaan mengajar yang jadi dasar hak akses & daftar lembar. */
    private function mappingUntuk($user, TahunAjaran $periode)
    {
        $query = GuruMengajarKelas::with(['kelas', 'mapel', 'guru'])
            ->where('tahun_ajaran_id', $periode->id);

        // Admin/Kurikulum/Kepsek melihat seluruh lembar; guru hanya miliknya.
        if (! $this->bolehMemantau($user)) {
            $query->where('guru_id', $user->id);
        }

        return $query->get();
    }

    /** Role yang boleh melihat lembar mana pun (baca & cetak, tanpa hak simpan). */
    private function bolehMemantau($user): bool
    {
        return $user->isAdmin() || $user->isKurikulum() || $user->isKepalaSekolah();
    }

    private function bolehMengisi($user, Kelas $kelas, MataPelajaran $mapel, TahunAjaran $periode): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return GuruMengajarKelas::where('tahun_ajaran_id', $periode->id)
            ->where('guru_id', $user->id)
            ->where('kelas_id', $kelas->id)
            ->where('mata_pelajaran_id', $mapel->id)
            ->exists();
    }

    private function pastikanBolehMengisi($user, Kelas $kelas, MataPelajaran $mapel, TahunAjaran $periode): void
    {
        abort_unless(
            $this->bolehMengisi($user, $kelas, $mapel, $periode),
            403,
            'Anda tidak mengampu mata pelajaran ini di kelas tersebut, sehingga tidak dapat mengubah nilainya.'
        );
    }

    private function header(Kelas $kelas, MataPelajaran $mapel, TahunAjaran $periode): PenilaianKelasMapel
    {
        return PenilaianKelasMapel::firstOrCreate([
            'kelas_id' => $kelas->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran_id' => $periode->id,
        ], [
            'guru_id' => $this->guruPengampu($kelas, $mapel, $periode)?->id,
        ]);
    }

    private function guruPengampu(Kelas $kelas, MataPelajaran $mapel, TahunAjaran $periode)
    {
        return GuruMengajarKelas::with('guru')
            ->where('tahun_ajaran_id', $periode->id)
            ->where('kelas_id', $kelas->id)
            ->where('mata_pelajaran_id', $mapel->id)
            ->first()?->guru;
    }

    /**
     * Siswa yang muncul di lembar ini: anggota kelas SAAT INI, DITAMBAH
     * siswa yang sudah terlanjur punya nilai di lembar ini tapi kemudian
     * pindah kelas — supaya nilainya tidak hilang dari layar (pola yang
     * sama dengan WaliKelasController::absensiBulanan).
     */
    private function daftarSiswa(Kelas $kelas, MataPelajaran $mapel, TahunAjaran $periode)
    {
        $idSekarang = $kelas->siswas()->where('is_active', true)->pluck('siswas.id');
        $idBernilai = NilaiSiswa::where('kelas_id', $kelas->id)
            ->where('mata_pelajaran_id', $mapel->id)
            ->where('tahun_ajaran_id', $periode->id)
            ->pluck('siswa_id');

        return Siswa::whereIn('id', $idSekarang->merge($idBernilai)->unique())
            ->orderBy('nama')
            ->get();
    }

    /** Ringkasan satu lembar: rata-rata kelas, tertinggi/terendah, jumlah belum tuntas. */
    private function statistik($baris, SkemaPenilaian $skema): array
    {
        $na = $baris->pluck('nilai.nilai_akhir')->filter(fn ($n) => $n !== null);

        return [
            'dinilai' => $na->count(),
            'total' => $baris->count(),
            'rata' => $na->isEmpty() ? null : round($na->avg(), 2),
            'tertinggi' => $na->max(),
            'terendah' => $na->min(),
            'belum_tuntas' => $na->filter(fn ($n) => $n < $skema->kktpMin)->count(),
        ];
    }

    /** Buang kolom di luar jumlah TPF yang berlaku & nilai kosong. */
    private function bersihkanFormatif(array $masukan, SkemaPenilaian $skema): array
    {
        $hasil = [];
        for ($i = 1; $i <= $skema->jumlahTpf; $i++) {
            $angka = $this->angka($masukan[$i] ?? null);
            if ($angka !== null) {
                $hasil[(string) $i] = $angka;
            }
        }

        return $hasil;
    }

    /** Sama untuk sumatif lingkup materi; pasangan SUM/REM disimpan utuh per LM. */
    private function bersihkanSumatif(array $masukan, SkemaPenilaian $skema): array
    {
        $hasil = [];
        for ($i = 1; $i <= $skema->jumlahLm; $i++) {
            $sum = $this->angka($masukan[$i]['sum'] ?? null);
            $rem = $this->angka($masukan[$i]['rem'] ?? null);

            if ($sum === null && $rem === null) {
                continue;
            }

            $hasil[(string) $i] = ['sum' => $sum, 'rem' => $rem];
        }

        return $hasil;
    }

    private function angka(mixed $nilai): ?float
    {
        if ($nilai === null || $nilai === '' || ! is_numeric($nilai)) {
            return null;
        }

        return round((float) $nilai, 2);
    }
}
