<?php

namespace App\Http\Controllers;

use App\Rules\DalamPeriode;
use App\Models\AnalisisSumatif;
use App\Models\GuruMengajarKelas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Support\AnalisisButirSoal;
use App\Support\PeriodeAkademik;
use App\Support\SkemaPenilaian;
use Illuminate\Http\Request;

/**
 * PROGRAM PENGAYAAN DAN PERBAIKAN — untuk Guru Mata Pelajaran.
 *
 * Dokumen lanjutan dari Analisis Hasil Tes Sumatif Lingkup Materi. Isinya
 * dua bagian:
 *
 *   A. PROGRAM PERBAIKAN (REMEDIAL) — peserta didik yang nilai sumatifnya
 *      di bawah KKTP, lengkap dengan BUTIR SOAL MANA yang belum dikuasai
 *      masing-masing anak, dan nilai setelah perbaikannya.
 *   B. PROGRAM PENGAYAAN — peserta didik yang sudah mencapai KKTP.
 *
 * SEMUANYA DITURUNKAN, TIDAK DIKETIK ULANG
 * ========================================
 * - Siapa masuk perbaikan / pengayaan : dari nilai SUM di Daftar Nilai
 *   dibandingkan dengan KKTP tingkat kelas itu.
 * - Butir soal yang belum dikuasai    : dari sebaran skor butir pada
 *   lembar Analisis (App\Support\AnalisisButirSoal) — butir yang skornya
 *   belum sempurna, itulah yang perlu diperbaiki anak tersebut.
 * - Nilai setelah perbaikan           : dari kolom REM di Daftar Nilai.
 * - Ketuntasan setelah perbaikan      : memakai kebijakan remedial yang
 *   berlaku (SkemaPenilaian::nilaiLingkupMateri), jadi angkanya persis
 *   sama dengan yang dipakai menghitung rapor.
 *
 * Yang diketik guru hanya rencana pelaksanaannya: bentuk kegiatan dan
 * tanggal, masing-masing untuk perbaikan dan pengayaan.
 *
 * HAK AKSES — sama dengan Daftar Nilai & Analisis:
 * guru pengampu (Admin mewakili) boleh menyimpan; Kurikulum & Kepala
 * Sekolah boleh membaca & mencetak saja.
 */
class ProgramPerbaikanController extends Controller
{
    public function index(Request $request, Kelas $kelas, MataPelajaran $mapel)
    {
        $periode = $this->periodeAktif();
        $bolehIsi = $this->bolehMengisi($request->user(), $kelas, $mapel, $periode);

        abort_unless(
            $bolehIsi || $this->bolehMemantau($request->user()),
            403,
            'Anda tidak mengampu mata pelajaran ini di kelas tersebut.'
        );

        $skema = SkemaPenilaian::untuk($periode, (int) $kelas->tingkat);
        $siswas = $this->daftarSiswa($kelas, $mapel, $periode);

        $nilai = NilaiSiswa::where('tahun_ajaran_id', $periode->id)
            ->where('mata_pelajaran_id', $mapel->id)
            ->whereIn('siswa_id', $siswas->pluck('id'))
            ->get()
            ->keyBy('siswa_id');

        $lingkupTerisi = $this->lingkupMateriTerisi($nilai, $skema);

        if ($lingkupTerisi->isEmpty()) {
            return view('nilai.program-perbaikan', [
                'kelas' => $kelas, 'mapel' => $mapel, 'periode' => $periode, 'skema' => $skema,
                'lingkupTerisi' => $lingkupTerisi, 'bolehIsi' => $bolehIsi, 'program' => null,
                'perbaikan' => collect(), 'pengayaan' => collect(), 'ringkasan' => null,
                'guruPengampu' => $this->guruPengampu($kelas, $mapel, $periode),
            ]);
        }

        $lm = (int) $request->get('lm', $lingkupTerisi->first());
        abort_unless($lingkupTerisi->contains($lm), 404, 'Belum ada nilai Sumatif Lingkup Materi ke-'.$lm.' pada kelas ini.');

        $program = $this->lembar($kelas, $mapel, $periode, $lm);
        $mesin = new AnalisisButirSoal($program->jumlah_soal, $program->benihKelas());

        // ===== Pilah peserta didik: perbaikan vs pengayaan =====
        $perbaikan = collect();
        $pengayaan = collect();

        foreach ($siswas as $siswa) {
            $n = $nilai->get($siswa->id);
            $sum = $n?->lm($lm, 'sum');

            // Belum dinilai — belum bisa ditentukan masuk program yang mana.
            if ($sum === null) {
                continue;
            }

            if ($sum >= $skema->kktpMin) {
                $pengayaan->push([
                    'siswa' => $siswa,
                    'nilai' => $sum,
                    // Nilai jauh di atas KKTP layak pengayaan yang lebih menantang;
                    // dipakai hanya sebagai saran bentuk kegiatan di layar.
                    'istimewa' => $sum > $skema->kktpMax,
                ]);

                continue;
            }

            $rem = $n->lm($lm, 'rem');
            $nilaiAkhirLm = $skema->nilaiLingkupMateri($sum, $rem);

            // Butir soal yang belum dikuasai anak ini — diambil dari lembar
            // Analisis, jadi kedua dokumen selalu bercerita hal yang sama.
            $skor = $mesin->skorSiswa($sum, $program->benihKelas().'|siswa|'.$siswa->id);
            $belumDikuasai = collect($skor)
                ->filter(fn ($s) => $s < AnalisisButirSoal::SKOR_MAKS_BUTIR)
                ->keys()
                ->sort()
                ->values();

            $perbaikan->push([
                'siswa' => $siswa,
                'nilai' => $sum,
                'butir' => $belumDikuasai,
                'nilai_remedi' => $rem,
                'nilai_akhir_lm' => $nilaiAkhirLm,
                'tuntas' => $rem === null ? null : ($nilaiAkhirLm >= $skema->kktpMin),
            ]);
        }

        // ===== Butir soal yang perlu dibahas ulang untuk SATU KELAS =====
        // (bukan per anak) — butir dengan daya serap rendah. Inilah dasar
        // guru menyusun bentuk perbaikan klasikal.
        $pesertaDinilai = $perbaikan->count() + $pengayaan->count();
        $butirLemah = collect();
        if ($pesertaDinilai > 0) {
            $semuaSkor = $siswas
                ->map(fn (Siswa $s) => $mesin->skorSiswa(
                    $nilai->get($s->id)?->lm($lm, 'sum'),
                    $program->benihKelas().'|siswa|'.$s->id
                ))
                ->filter(fn ($skor) => ! empty($skor));

            $butirLemah = collect(range(1, $program->jumlah_soal))
                ->map(function (int $nomor) use ($semuaSkor) {
                    $dayaSerap = round(
                        $semuaSkor->avg(fn ($skor) => $skor[$nomor] ?? 0)
                            / AnalisisButirSoal::SKOR_MAKS_BUTIR * 100,
                        1
                    );

                    return ['nomor' => $nomor, 'daya_serap' => $dayaSerap];
                })
                // Di bawah 70% berarti belum tuntas dikuasai kelas (batas
                // baku "mudah" pada analisis butir soal).
                ->filter(fn ($b) => $b['daya_serap'] < 70)
                ->sortBy('daya_serap')
                ->values();
        }

        $ringkasan = [
            'peserta_dinilai' => $pesertaDinilai,
            'belum_dinilai' => $siswas->count() - $pesertaDinilai,
            'jumlah_perbaikan' => $perbaikan->count(),
            'jumlah_pengayaan' => $pengayaan->count(),
            'sudah_remedi' => $perbaikan->whereNotNull('nilai_remedi')->count(),
            'belum_remedi' => $perbaikan->whereNull('nilai_remedi')->count(),
            'tuntas_setelah' => $perbaikan->where('tuntas', true)->count(),
            'butir_lemah' => $butirLemah,
        ];

        return view('nilai.program-perbaikan', compact(
            'kelas', 'mapel', 'periode', 'skema', 'lingkupTerisi', 'lm',
            'program', 'perbaikan', 'pengayaan', 'ringkasan', 'bolehIsi'
        ) + ['guruPengampu' => $this->guruPengampu($kelas, $mapel, $periode)]);
    }

    /** Simpan rencana pelaksanaan perbaikan & pengayaan satu lingkup materi. */
    public function update(Request $request, Kelas $kelas, MataPelajaran $mapel)
    {
        $periode = $this->periodeAktif();
        $this->pastikanBolehMengisi($request->user(), $kelas, $mapel, $periode);
        PeriodeAkademik::pastikanTidakTerkunci($periode);

        $validated = $request->validate([
            'lingkup_materi' => ['required', 'integer', 'min:1', 'max:20'],
            'bentuk_perbaikan' => ['nullable', 'string', 'max:2000'],
            'tanggal_perbaikan' => ['nullable', 'date', new DalamPeriode($periode, 'pelaksanaan perbaikan')],
            'bentuk_pengayaan' => ['nullable', 'string', 'max:2000'],
            'tanggal_pengayaan' => ['nullable', 'date', new DalamPeriode($periode, 'pelaksanaan pengayaan')],
        ], [], [
            'bentuk_perbaikan' => 'Bentuk Pelaksanaan Perbaikan',
            'tanggal_perbaikan' => 'Tanggal Pelaksanaan Perbaikan',
            'bentuk_pengayaan' => 'Bentuk Pelaksanaan Pengayaan',
            'tanggal_pengayaan' => 'Tanggal Pelaksanaan Pengayaan',
        ]);

        $this->lembar($kelas, $mapel, $periode, (int) $validated['lingkup_materi'])->update([
            'bentuk_perbaikan' => $validated['bentuk_perbaikan'] ?? null,
            'tanggal_perbaikan' => $validated['tanggal_perbaikan'] ?? null,
            'bentuk_pengayaan' => $validated['bentuk_pengayaan'] ?? null,
            'tanggal_pengayaan' => $validated['tanggal_pengayaan'] ?? null,
            'diperbarui_oleh_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('nilai.program', [
                'kelas' => $kelas->id, 'mapel' => $mapel->id, 'lm' => $validated['lingkup_materi'],
            ])
            ->with('success', 'Rencana program pengayaan dan perbaikan disimpan.');
    }

    // =================================================================
    // internal — sengaja sama persis dengan AnalisisSumatifController
    // supaya kedua dokumen tidak pernah beda daftar peserta/hak aksesnya.
    // =================================================================

    private function periodeAktif(): TahunAjaran
    {
        $periode = PeriodeAkademik::aktif();

        abort_if($periode === null, 409, 'Belum ada Tahun Ajaran yang aktif. Hubungi Kurikulum/Admin untuk mengaktifkan periode terlebih dahulu.');

        return $periode;
    }

    private function lingkupMateriTerisi($nilai, SkemaPenilaian $skema)
    {
        return collect(range(1, $skema->jumlahLm))
            ->filter(fn (int $lm) => $nilai->contains(fn ($n) => $n->lm($lm, 'sum') !== null))
            ->values();
    }

    private function lembar(Kelas $kelas, MataPelajaran $mapel, TahunAjaran $periode, int $lm): AnalisisSumatif
    {
        return AnalisisSumatif::firstOrCreate([
            'kelas_id' => $kelas->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran_id' => $periode->id,
            'lingkup_materi' => $lm,
        ], [
            'jumlah_soal' => AnalisisSumatif::DEFAULT_JUMLAH_SOAL,
        ]);
    }

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
            'Anda tidak mengampu mata pelajaran ini di kelas tersebut.'
        );
    }

    private function guruPengampu(Kelas $kelas, MataPelajaran $mapel, TahunAjaran $periode)
    {
        return GuruMengajarKelas::with('guru')
            ->where('tahun_ajaran_id', $periode->id)
            ->where('kelas_id', $kelas->id)
            ->where('mata_pelajaran_id', $mapel->id)
            ->first()?->guru;
    }

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
}
