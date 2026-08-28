<?php

namespace App\Http\Controllers;

use App\Models\AnggotaKelas;
use App\Models\GuruMengajarKelas;
use App\Models\Kelas;
use App\Models\NilaiSiswa;
use App\Models\PenilaianKelasMapel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Support\PeriodeAkademik;
use Illuminate\Http\Request;

/**
 * MONITORING INPUT NILAI — untuk Kurikulum & Kepala Sekolah.
 *
 * Menjawab satu pertanyaan yang selalu muncul menjelang pembagian rapor:
 * "mapel mana di kelas mana yang nilainya belum masuk, dan siapa gurunya?"
 *
 * Sumber datanya sama persis dengan yang dilihat guru & wali kelas — tidak
 * ada rekap terpisah yang bisa basi. Satu baris = satu pemetaan guru
 * mengajar (kelas × mapel × guru) pada periode berjalan.
 *
 * Sengaja dibuat sejajar dengan menu "Rekapitulasi Kepatuhan" yang sudah
 * ada untuk jurnal & absensi, supaya polanya familier bagi Kurikulum.
 */
class NilaiMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $periode = $this->periodeDilihat($request);

        $mapping = GuruMengajarKelas::with(['kelas', 'mapel', 'guru'])
            ->where('tahun_ajaran_id', $periode->id)
            ->get()
            ->filter(fn ($m) => $m->kelas && $m->mapel);

        $header = PenilaianKelasMapel::where('tahun_ajaran_id', $periode->id)
            ->get()
            ->keyBy(fn ($h) => $h->kelas_id.'|'.$h->mata_pelajaran_id);

        // Dua angka per lembar: berapa siswa yang sudah punya nilai akhir,
        // dan berapa yang komponennya sudah LENGKAP (siap difinalisasi).
        $rekapNilai = NilaiSiswa::where('tahun_ajaran_id', $periode->id)
            ->selectRaw('kelas_id, mata_pelajaran_id,
                         COUNT(nilai_akhir) as ada_nilai,
                         SUM(CASE WHEN lengkap = 1 AND nilai_akhir IS NOT NULL THEN 1 ELSE 0 END) as lengkap')
            ->groupBy('kelas_id', 'mata_pelajaran_id')
            ->get()
            ->keyBy(fn ($n) => $n->kelas_id.'|'.$n->mata_pelajaran_id);

        // Lihat catatan yang sama di NilaiController.
        $jumlahSiswa = AnggotaKelas::whereIn('kelas_id', $mapping->pluck('kelas_id')->unique())
            ->whereHas('siswa', fn ($q) => $q->where('is_active', true))
            ->selectRaw('kelas_id, COUNT(*) as jumlah')
            ->groupBy('kelas_id')
            ->pluck('jumlah', 'kelas_id');

        $baris = $mapping->map(function (GuruMengajarKelas $m) use ($header, $rekapNilai, $jumlahSiswa) {
            $kunci = $m->kelas_id.'|'.$m->mata_pelajaran_id;
            $total = (int) ($jumlahSiswa[$m->kelas_id] ?? 0);
            $adaNilai = (int) ($rekapNilai[$kunci]->ada_nilai ?? 0);
            $lengkap = (int) ($rekapNilai[$kunci]->lengkap ?? 0);
            $h = $header->get($kunci);

            return [
                'kelas' => $m->kelas,
                'mapel' => $m->mapel,
                'guru' => $m->guru,
                'header' => $h,
                'total' => $total,
                'ada_nilai' => $adaNilai,
                'lengkap' => $lengkap,
                'persen' => $total > 0 ? (int) round($adaNilai / $total * 100) : 0,
                'status' => $this->statusLembar($h, $total, $adaNilai, $lengkap),
            ];
        })->sortBy([
            fn ($a, $b) => strcmp($a['kelas']->nama_kelas, $b['kelas']->nama_kelas),
            fn ($a, $b) => strcmp($a['mapel']->nama_mapel, $b['mapel']->nama_mapel),
        ])->values();

        // Saringan tampilan (tidak mengubah angka ringkasan di atasnya).
        $filterKelas = $request->integer('kelas_id') ?: null;
        $filterStatus = $request->get('status');

        $ringkasan = [
            'total' => $baris->count(),
            'final' => $baris->where('status', 'final')->count(),
            'lengkap' => $baris->where('status', 'lengkap')->count(),
            'sebagian' => $baris->where('status', 'sebagian')->count(),
            'kosong' => $baris->where('status', 'kosong')->count(),
        ];

        $barisTampil = $baris
            ->when($filterKelas, fn ($c) => $c->where('kelas.id', $filterKelas))
            ->when($filterStatus, fn ($c) => $c->where('status', $filterStatus))
            ->values();

        $daftarKelas = Kelas::untukTahunAjaran($periode)->orderBy('nama_kelas')->get();
        $daftarPeriode = TahunAjaran::orderByDesc('nama')->orderBy('semester')->get();

        return view('nilai.monitoring', compact(
            'periode', 'baris', 'barisTampil', 'ringkasan', 'daftarKelas', 'daftarPeriode',
            'filterKelas', 'filterStatus'
        ));
    }

    /**
     * Status satu lembar daftar nilai:
     * - final    : sudah difinalisasi guru, terkunci
     * - lengkap  : semua siswa sudah lengkap nilainya, tinggal difinalisasi
     * - sebagian : sudah mulai diisi tapi belum semua
     * - kosong   : belum diisi sama sekali
     */
    private function statusLembar(?PenilaianKelasMapel $header, int $total, int $adaNilai, int $lengkap): string
    {
        if ($header?->isFinal()) {
            return 'final';
        }

        if ($adaNilai === 0) {
            return 'kosong';
        }

        return ($total > 0 && $lengkap >= $total) ? 'lengkap' : 'sebagian';
    }

    private function periodeDilihat(Request $request): TahunAjaran
    {
        if ($request->filled('tahun_ajaran_id')) {
            return TahunAjaran::findOrFail($request->integer('tahun_ajaran_id'));
        }

        $periode = PeriodeAkademik::aktif();

        abort_if($periode === null, 409, 'Belum ada Tahun Ajaran yang aktif. Hubungi Kurikulum/Admin untuk mengaktifkan periode terlebih dahulu.');

        return $periode;
    }
}
