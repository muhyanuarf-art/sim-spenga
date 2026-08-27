<?php

namespace App\Http\Controllers;

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

/**
 * LAPORAN PENILAIAN UNTUK WALI KELAS.
 *
 * Wali kelas TIDAK mengetik nilai apa pun di sini. Semua angkanya datang
 * otomatis dari Daftar Nilai yang diisi guru mata pelajaran
 * (lihat NilaiController) — sesuai permintaan: "Nilai akhir otomatis masuk
 * ke nilai wali kelas."
 *
 * Ada dua bentuk laporan:
 *
 * 1. REKAP NILAI AKHIR KELAS (rekapKelas)
 *    Satu lembar berisi SELURUH mata pelajaran: baris = siswa, kolom =
 *    mapel, isi = Nilai Akhir (Rapor). Dilengkapi rata-rata, peringkat,
 *    dan jumlah mapel yang belum tuntas per siswa — inilah lembar yang
 *    dipakai wali kelas saat menyusun rapor & rapat kenaikan kelas.
 *
 * 2. NILAI AKHIR PER MATA PELAJARAN (laporanMapel)
 *    Bentuknya persis lembar "NILAI AKHIR (RAPORT)" pada contoh: rincian
 *    NILAI FORMATIF TPF 1–7 + RT, lalu NA (RAPOR) untuk satu mapel.
 *
 * Keduanya punya tombol Cetak / Export PDF.
 *
 * HAK AKSES (mengikuti pola WaliKelasController yang sudah ada):
 * - Guru      : terkunci ke kelas perwaliannya sendiri.
 * - Guru BK   : kelas-kelas yang di-mapping-kan kepadanya.
 * - Kurikulum / Kepala Sekolah / Admin: bebas memilih kelas mana pun.
 */
class NilaiWaliKelasController extends Controller
{
    public function rekapKelas(Request $request, ?Kelas $kelas = null)
    {
        $periode = $this->periodeAktif();
        $daftarKelas = $this->daftarKelasPilihan($request->user());
        $kelas = $this->resolveKelas($request, $kelas);

        $skema = SkemaPenilaian::untuk($periode, (int) $kelas->tingkat);
        $siswas = $this->daftarSiswa($kelas, $periode);

        // Mapel yang ditampilkan = mapel yang DIAJARKAN di kelas ini
        // (dari pemetaan guru mengajar), digabung dengan mapel yang sudah
        // terlanjur punya nilai — supaya kolomnya tidak hilang kalau
        // pemetaannya sempat diubah di tengah semester.
        $mapels = $this->mapelKelas($kelas, $periode);

        $nilai = NilaiSiswa::where('tahun_ajaran_id', $periode->id)
            ->whereIn('siswa_id', $siswas->pluck('id'))
            ->whereIn('mata_pelajaran_id', $mapels->pluck('id'))
            ->get()
            ->groupBy('siswa_id');

        $baris = $siswas->map(function (Siswa $siswa) use ($nilai, $mapels, $skema) {
            $milikSiswa = ($nilai->get($siswa->id) ?? collect())->keyBy('mata_pelajaran_id');

            $na = $mapels->map(fn (MataPelajaran $m) => $milikSiswa->get($m->id)?->nilai_akhir)
                ->filter(fn ($n) => $n !== null);

            return [
                'siswa' => $siswa,
                'nilai' => $milikSiswa,
                'jumlah' => $na->sum(),
                'rata' => $na->isEmpty() ? null : round($na->avg(), 2),
                'terisi' => $na->count(),
                'belum_tuntas' => $na->filter(fn ($n) => $n < $skema->kktpMin)->count(),
            ];
        });

        $baris = $this->beriPeringkat($baris);

        // Ringkasan per mata pelajaran untuk kaki laporan (rata-rata kelas
        // & berapa siswa belum tuntas per mapel) + status finalisasinya.
        $header = PenilaianKelasMapel::where('tahun_ajaran_id', $periode->id)
            ->where('kelas_id', $kelas->id)
            ->with('guru')
            ->get()
            ->keyBy('mata_pelajaran_id');

        $ringkasanMapel = $mapels->map(function (MataPelajaran $m) use ($baris, $skema, $header) {
            $na = $baris->map(fn ($b) => $b['nilai']->get($m->id)?->nilai_akhir)->filter(fn ($n) => $n !== null);

            return [
                'mapel' => $m,
                'header' => $header->get($m->id),
                'rata' => $na->isEmpty() ? null : round($na->avg(), 2),
                'terisi' => $na->count(),
                'belum_tuntas' => $na->filter(fn ($n) => $n < $skema->kktpMin)->count(),
            ];
        });

        return view('nilai.rekap-kelas', compact(
            'kelas', 'periode', 'skema', 'mapels', 'baris', 'ringkasanMapel', 'daftarKelas'
        ));
    }

    /** Lembar "NILAI AKHIR (RAPOR)" satu mata pelajaran — rincian formatif + NA. */
    public function laporanMapel(Request $request, ?Kelas $kelas = null)
    {
        $periode = $this->periodeAktif();
        $daftarKelas = $this->daftarKelasPilihan($request->user());
        $kelas = $this->resolveKelas($request, $kelas);

        $skema = SkemaPenilaian::untuk($periode, (int) $kelas->tingkat);
        $mapels = $this->mapelKelas($kelas, $periode);

        abort_if($mapels->isEmpty(), 404, 'Belum ada mata pelajaran yang dipetakan untuk kelas ini.');

        $mapelId = (int) $request->get('mapel_id', $mapels->first()->id);
        $mapel = $mapels->firstWhere('id', $mapelId) ?? $mapels->first();

        $siswas = $this->daftarSiswa($kelas, $periode);
        $nilai = NilaiSiswa::where('tahun_ajaran_id', $periode->id)
            ->where('mata_pelajaran_id', $mapel->id)
            ->whereIn('siswa_id', $siswas->pluck('id'))
            ->get()
            ->keyBy('siswa_id');

        $baris = $siswas->map(fn (Siswa $siswa) => [
            'siswa' => $siswa,
            'nilai' => $nilai->get($siswa->id),
            'deskripsi' => $nilai->get($siswa->id)
                ? $skema->deskripsiCapaian(
                    $nilai->get($siswa->id)->formatif ?? [],
                    $nilai->get($siswa->id)->nilai_akhir
                )
                : null,
        ]);

        $header = PenilaianKelasMapel::with('guru')
            ->where('tahun_ajaran_id', $periode->id)
            ->where('kelas_id', $kelas->id)
            ->where('mata_pelajaran_id', $mapel->id)
            ->first();

        return view('nilai.laporan-mapel', compact(
            'kelas', 'periode', 'skema', 'mapels', 'mapel', 'baris', 'header', 'daftarKelas'
        ));
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
     * Peringkat kelas berdasarkan rata-rata nilai akhir. Nilai yang SAMA
     * mendapat peringkat yang SAMA (peringkat kembar), dan peringkat
     * berikutnya melompat sesuai jumlah yang kembar — sama seperti cara
     * sekolah menuliskan peringkat di rapor. Siswa yang belum punya nilai
     * sama sekali tidak diberi peringkat (bukan diberi peringkat terakhir).
     */
    private function beriPeringkat($baris)
    {
        $urut = $baris->filter(fn ($b) => $b['rata'] !== null)
            ->sortByDesc('rata')
            ->values();

        $peringkat = [];
        $nomor = 0;
        $sebelumnya = null;
        foreach ($urut as $index => $b) {
            if ($sebelumnya === null || $b['rata'] < $sebelumnya) {
                $nomor = $index + 1;
                $sebelumnya = $b['rata'];
            }
            $peringkat[$b['siswa']->id] = $nomor;
        }

        return $baris->map(function ($b) use ($peringkat) {
            $b['peringkat'] = $peringkat[$b['siswa']->id] ?? null;

            return $b;
        });
    }

    /** Mapel yang diajarkan di kelas ini + mapel yang sudah punya nilai. */
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

    /** Anggota kelas sekarang + siswa yang sudah punya nilai di kelas ini. */
    private function daftarSiswa(Kelas $kelas, TahunAjaran $periode)
    {
        $idSekarang = $kelas->siswas()->where('is_active', true)->pluck('id');
        $idBernilai = NilaiSiswa::where('kelas_id', $kelas->id)
            ->where('tahun_ajaran_id', $periode->id)
            ->distinct()
            ->pluck('siswa_id');

        return Siswa::whereIn('id', $idSekarang->merge($idBernilai)->unique())
            ->orderBy('nama')
            ->get();
    }

    /** Kelas yang boleh dipilih lewat dropdown, beda cakupan per role. */
    private function daftarKelasPilihan($user)
    {
        if ($user->isGuruBk()) {
            return $user->kelasBk();
        }

        if ($user->isAdmin() || $user->isKurikulum() || $user->isKepalaSekolah()) {
            return Kelas::aktif()->orderBy('nama_kelas')->get();
        }

        // Guru/wali kelas: terkunci ke kelas perwaliannya sendiri.
        return collect(array_filter([$user->kelasWali]));
    }

    /** Tentukan & validasi kelas yang sedang dilihat. */
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

        abort_unless(
            $bolehDilihat->contains('id', $kelasId),
            403,
            'Anda tidak memiliki akses ke kelas ini.'
        );

        return $bolehDilihat->firstWhere('id', $kelasId);
    }
}
