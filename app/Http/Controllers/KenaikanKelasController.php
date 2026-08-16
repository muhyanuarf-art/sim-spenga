<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\RiwayatKelasSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KenaikanKelasController extends Controller
{
    /**
     * Form Kenaikan Kelas. Kalau kelas_asal_id sudah dipilih (query string),
     * langsung tampilkan daftar siswa aktif di kelas tsb supaya Kurikulum/
     * Admin bisa pilih siapa saja yang naik & kelas tujuannya sebelum
     * membuka modal preview (Alpine.js) dan submit.
     */
    public function index(Request $request)
    {
        $kelasList = Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get();
        $tahunAjaranList = TahunAjaran::orderByDesc('id')->get();

        $kelasAsal = null;
        $siswas = collect();

        if ($request->filled('kelas_asal_id')) {
            $kelasAsal = Kelas::findOrFail($request->integer('kelas_asal_id'));
            $siswas = $kelasAsal->siswas()->where('is_active', true)->orderBy('nama')->get();
        }

        return view('kenaikan-kelas.index', compact('kelasList', 'tahunAjaranList', 'kelasAsal', 'siswas'));
    }

    /**
     * Proses kenaikan kelas untuk siswa terpilih. Siswa yang TIDAK dicentang
     * dianggap tinggal di kelas asal (tidak dicatat/tidak dipindah).
     *
     * unique(siswa_id, tahun_ajaran_id) di level database mencegah siswa
     * yang sama tercatat naik kelas dua kali pada tahun ajaran yang sama —
     * kalau itu terjadi baris tsb dilewati (skip), bukan error 500.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_asal_id' => ['required', 'exists:kelas,id'],
            'kelas_tujuan_id' => ['required', 'exists:kelas,id', 'different:kelas_asal_id'],
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajarans,id'],
            'siswa_ids' => ['required', 'array', 'min:1'],
            'siswa_ids.*' => ['integer', 'exists:siswas,id'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $kelasTujuan = Kelas::findOrFail($validated['kelas_tujuan_id']);
        $tahunAjaran = TahunAjaran::findOrFail($validated['tahun_ajaran_id']);

        $berhasil = 0;
        $dilewati = 0;

        DB::transaction(function () use ($validated, $kelasTujuan, $tahunAjaran, &$berhasil, &$dilewati) {
            foreach ($validated['siswa_ids'] as $siswaId) {
                $sudahAda = RiwayatKelasSiswa::where('siswa_id', $siswaId)
                    ->where('tahun_ajaran_id', $tahunAjaran->id)
                    ->exists();

                if ($sudahAda) {
                    $dilewati++;
                    continue;
                }

                $siswa = Siswa::findOrFail($siswaId);

                RiwayatKelasSiswa::create([
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'kelas_asal_id' => $validated['kelas_asal_id'],
                    'kelas_id' => $kelasTujuan->id,
                    'keterangan' => $validated['keterangan'] ?? null,
                    'dicatat_oleh_id' => auth()->id(),
                ]);

                $siswa->update(['kelas_id' => $kelasTujuan->id]);
                $berhasil++;
            }
        });

        $pesan = "{$berhasil} siswa berhasil dinaikkan ke kelas {$kelasTujuan->nama_kelas}.";
        if ($dilewati > 0) {
            $pesan .= " {$dilewati} siswa dilewati karena sudah tercatat naik kelas pada tahun ajaran ini.";
        }

        return redirect()->route('kenaikan-kelas.index')->with('success', $pesan);
    }

    /** Riwayat kelas seorang siswa, bernomor, urut dari periode paling awal. */
    public function riwayat(Siswa $siswa)
    {
        $riwayat = $siswa->riwayatKelas()->with(['tahunAjaran', 'kelasAsal', 'kelas', 'dicatatOleh'])->get();

        return view('kenaikan-kelas.riwayat', compact('siswa', 'riwayat'));
    }
}
