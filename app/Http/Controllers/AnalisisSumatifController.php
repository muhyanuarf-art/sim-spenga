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
 * ANALISIS HASIL TES SUMATIF LINGKUP MATERI — untuk Guru Mata Pelajaran.
 *
 * Lembar yang biasa dibuat guru setelah melaksanakan ulangan harian /
 * sumatif lingkup materi: rincian skor tiap peserta didik pada butir soal
 * nomor 1–20, jumlah skor, persentase ketercapaian, dan ketuntasan
 * belajarnya terhadap KKTP.
 *
 * DARI MANA ANGKANYA
 * ==================
 * Seluruhnya diturunkan dari nilai SUM (Sumatif Lingkup Materi) yang sudah
 * diinput guru di Daftar Nilai — guru tidak mengetik ulang apa pun kecuali
 * Materi Ajar, banyaknya butir soal, dan tanggal pelaksanaan.
 *
 * Jumlah skor tiap siswa dijamin SAMA PERSIS dengan nilai sumatifnya
 * (siswa bernilai 90 pasti berjumlah skor 90) — lihat penjelasan lengkap
 * cara penyebarannya di App\Support\AnalisisButirSoal.
 *
 * Yang dipakai adalah nilai SUM (hasil tes aslinya), BUKAN nilai setelah
 * remedi. Memang begitu seharusnya: lembar ini menganalisis hasil TES,
 * dan justru dari sinilah ketahuan siapa yang perlu remedi.
 *
 * BERAPA LEMBAR YANG MUNCUL
 * =========================
 * Satu lembar per Lingkup Materi yang SUDAH ada nilainya. Kalau guru baru
 * mengisi sampai Sumatif ke-3, yang muncul juga sampai ke-3 (sesuai
 * ketentuan sekolah) — lihat lingkupMateriTerisi().
 *
 * HAK AKSES — sama persis dengan Daftar Nilai (NilaiController):
 * - Guru: hanya kelas & mapel yang diampunya; Admin boleh mewakili.
 * - Kurikulum & Kepala Sekolah: boleh membaca & mencetak, tidak menyimpan.
 */
class AnalisisSumatifController extends Controller
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
            return view('nilai.analisis-sumatif', [
                'kelas' => $kelas, 'mapel' => $mapel, 'periode' => $periode, 'skema' => $skema,
                'lingkupTerisi' => $lingkupTerisi, 'bolehIsi' => $bolehIsi,
                'lembar' => null, 'analisis' => null, 'baris' => collect(),
                'butir' => collect(), 'ringkasan' => null, 'guruPengampu' => $this->guruPengampu($kelas, $mapel, $periode),
            ]);
        }

        // Lingkup materi yang sedang dilihat (default: yang pertama).
        $lm = (int) $request->get('lm', $lingkupTerisi->first());
        abort_unless($lingkupTerisi->contains($lm), 404, 'Belum ada nilai Sumatif Lingkup Materi ke-'.$lm.' pada kelas ini.');

        $analisis = $this->lembar($kelas, $mapel, $periode, $lm);
        $mesin = new AnalisisButirSoal($analisis->jumlah_soal, $analisis->benihKelas());

        // ===== Baris per peserta didik =====
        $baris = $siswas->map(function (Siswa $siswa) use ($nilai, $lm, $mesin, $skema, $analisis) {
            $n = $nilai->get($siswa->id);
            $nilaiSum = $n?->lm($lm, 'sum');
            $skor = $mesin->skorSiswa($nilaiSum, $analisis->benihKelas().'|siswa|'.$siswa->id);

            return [
                'siswa' => $siswa,
                'skor' => $skor,
                'jumlah_skor' => $mesin->jumlahSkor($skor),
                'nilai_remedi' => $n?->lm($lm, 'rem'),
                'tuntas' => $nilaiSum === null ? null : $nilaiSum >= $skema->kktpMin,
            ];
        });

        // ===== Rekap per butir soal (inti kegunaan lembar ini) =====
        $pesertaBernilai = $baris->filter(fn ($b) => $b['jumlah_skor'] !== null);
        $butir = collect(range(1, $analisis->jumlah_soal))->map(function (int $nomor) use ($pesertaBernilai) {
            $skorButir = $pesertaBernilai->map(fn ($b) => $b['skor'][$nomor] ?? 0);
            $jumlah = round($skorButir->sum(), 2);
            $dayaSerap = $pesertaBernilai->isEmpty()
                ? null
                : round($jumlah / ($pesertaBernilai->count() * AnalisisButirSoal::SKOR_MAKS_BUTIR) * 100, 1);

            return [
                'nomor' => $nomor,
                'jumlah' => $jumlah,
                'daya_serap' => $dayaSerap,
                'label' => AnalisisButirSoal::labelKesukaran($dayaSerap),
                'warna' => AnalisisButirSoal::warnaKesukaran($dayaSerap),
            ];
        });

        $nilaiPeserta = $pesertaBernilai->pluck('jumlah_skor');
        $ringkasan = [
            'peserta' => $pesertaBernilai->count(),
            'belum_dinilai' => $baris->count() - $pesertaBernilai->count(),
            'rata' => $nilaiPeserta->isEmpty() ? null : round($nilaiPeserta->avg(), 2),
            'tertinggi' => $nilaiPeserta->max(),
            'terendah' => $nilaiPeserta->min(),
            'tuntas' => $baris->where('tuntas', true)->count(),
            'belum_tuntas' => $baris->where('tuntas', false)->count(),
            'daya_serap_kelas' => $nilaiPeserta->isEmpty() ? null : round($nilaiPeserta->avg(), 1),
            'perlu_remedial' => $baris->where('tuntas', false)->values(),
            'soal_sukar' => $butir->where('label', 'Sukar')->pluck('nomor')->values(),
            'soal_sedang' => $butir->where('label', 'Sedang')->pluck('nomor')->values(),
        ];

        return view('nilai.analisis-sumatif', compact(
            'kelas', 'mapel', 'periode', 'skema', 'lingkupTerisi', 'lm',
            'analisis', 'baris', 'butir', 'ringkasan', 'bolehIsi'
        ) + [
            'lembar' => $analisis,
            'guruPengampu' => $this->guruPengampu($kelas, $mapel, $periode),
        ]);
    }

    /** Simpan Materi Ajar, banyak soal, dan tanggal pelaksanaan satu lembar. */
    public function update(Request $request, Kelas $kelas, MataPelajaran $mapel)
    {
        $periode = $this->periodeAktif();
        $this->pastikanBolehMengisi($request->user(), $kelas, $mapel, $periode);
        PeriodeAkademik::pastikanTidakTerkunci($periode);

        $validated = $request->validate([
            'lingkup_materi' => ['required', 'integer', 'min:1', 'max:20'],
            'materi_ajar' => ['nullable', 'string', 'max:255'],
            // Dibatasi 50 supaya tabelnya tetap terbaca & muat saat dicetak.
            'jumlah_soal' => ['required', 'integer', 'min:1', 'max:50'],
            'tanggal_pelaksanaan' => ['nullable', 'date', new DalamPeriode(sebutan: 'pelaksanaan tes')],
        ], [], [
            'materi_ajar' => 'Materi Ajar',
            'jumlah_soal' => 'Banyak Soal',
            'tanggal_pelaksanaan' => 'Tanggal Pelaksanaan',
        ]);

        $this->lembar($kelas, $mapel, $periode, (int) $validated['lingkup_materi'])->update([
            'materi_ajar' => $validated['materi_ajar'] ?? null,
            'jumlah_soal' => $validated['jumlah_soal'],
            'tanggal_pelaksanaan' => $validated['tanggal_pelaksanaan'] ?? null,
            'diperbarui_oleh_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('nilai.analisis', [
                'kelas' => $kelas->id, 'mapel' => $mapel->id, 'lm' => $validated['lingkup_materi'],
            ])
            ->with('success', 'Keterangan lembar analisis disimpan.');
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
     * Lingkup Materi ke berapa saja yang SUDAH punya nilai sumatif di kelas
     * ini — inilah yang menentukan berapa lembar analisis yang muncul.
     * Kalau guru baru mengisi sampai Sumatif ke-3, hasilnya [1, 2, 3].
     *
     * Satu siswa saja yang sudah dinilai sudah cukup membuat lembarnya
     * muncul, supaya guru bisa memantau sambil menilai — bukan menunggu
     * seluruh kelas selesai.
     */
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

    /** Sama dengan NilaiController: anggota kelas sekarang + yang sudah punya nilai di sini. */
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
