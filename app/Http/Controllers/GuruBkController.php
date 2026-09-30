<?php

namespace App\Http\Controllers;

use App\Support\KonteksPeriode;
use App\Models\GuruBkKelas;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuruBkController extends Controller
{
    public function index(Request $request)
    {
        $tahunAjaran = KonteksPeriode::pilihan();

        $query = GuruBkKelas::with(['guru', 'kelas'])
            ->when($tahunAjaran, fn ($q) => $q->where('guru_bk_kelas.tahun_ajaran_id', $tahunAjaran->id))
            ->when($request->guru_id, fn ($q) => $q->where('guru_bk_kelas.guru_id', $request->guru_id));

        $data = $query->join('kelas', 'guru_bk_kelas.kelas_id', '=', 'kelas.id')
            ->orderBy('kelas.nama_kelas')
            ->select('guru_bk_kelas.*')
            ->paginate(25)
            ->withQueryString();

        // STEP 5 Bagian 16/23 — hanya kelas TAHUN AJARAN AKTIF.
        $kelasList = Kelas::aktif()->orderBy('nama_kelas')->get();
        $guruBkList = User::where('role', 'guru_bk')->orderBy('name')->get();

        return view('kurikulum.guru-bk.index', compact('data', 'kelasList', 'guruBkList', 'tahunAjaran'));
    }

    public function store(Request $request)
    {
        $tahunAjaran = TahunAjaran::aktif();
        abort_if(! $tahunAjaran, 422, 'Tidak ada tahun ajaran aktif. Aktifkan dahulu di menu Tahun Ajaran.');

        // BANYAK KELAS SEKALIGUS UNTUK SATU GURU BK — sama persis dengan
        // Pemetaan Guru Mengajar (lihat GuruMengajarController::store).
        //
        // Seorang guru BK biasanya membina satu tingkat penuh, jadi dulu
        // formulir ini harus diisi empat sampai lima kali dengan nama yang
        // sama diulang terus.
        //
        // Yang TERSIMPAN tidak berubah: tetap satu baris per guru + kelas.
        $validated = $request->validate([
            'guru_id' => ['required', 'exists:users,id'],
            'kelas_id' => ['required', 'array', 'min:1'],
            // STEP 5 Bagian 16 — kelas WAJIB dari tahun ajaran yang sama.
            'kelas_id.*' => [
                Rule::exists('kelas', 'id')->where(
                    fn ($q) => $q->whereIn('id', Kelas::untukTahunAjaran($tahunAjaran)->pluck('id'))
                ),
            ],
        ], [], ['kelas_id' => 'kelas']);

        $dibuat = 0;
        $sudahAda = 0;

        foreach ($validated['kelas_id'] as $kelasId) {
            // firstOrCreate: mencentang kelas yang mappingnya sudah ada
            // tidak menggandakan baris, dan operator tetap diberi tahu
            // berapa yang dilewati supaya tidak mengira centangannya gagal.
            $baris = GuruBkKelas::firstOrCreate([
                'guru_id' => $validated['guru_id'],
                'kelas_id' => $kelasId,
                'tahun_ajaran_id' => $tahunAjaran->id,
            ]);

            $baris->wasRecentlyCreated ? $dibuat++ : $sudahAda++;
        }

        $pesan = $dibuat > 0
            ? "Mapping Guru BK berhasil ditambahkan untuk {$dibuat} kelas."
            : 'Tidak ada mapping baru yang ditambahkan.';

        if ($sudahAda > 0) {
            $pesan .= " {$sudahAda} kelas dilewati karena mappingnya sudah ada.";
        }

        return back()->with($dibuat > 0 ? 'success' : 'error', $pesan);
    }

    public function destroy(GuruBkKelas $guruBk)
    {
        $guruBk->delete();
        return back()->with('success', 'Mapping berhasil dihapus.');
    }
}
