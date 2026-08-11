<?php

namespace App\Http\Controllers;

use App\Models\GuruBkKelas;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Http\Request;

class GuruBkController extends Controller
{
    public function index(Request $request)
    {
        $tahunAjaran = TahunAjaran::aktif();

        $query = GuruBkKelas::with(['guru', 'kelas'])
            ->when($tahunAjaran, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
            ->when($request->guru_id, fn ($q) => $q->where('guru_id', $request->guru_id));

        $data = $query->join('kelas', 'guru_bk_kelas.kelas_id', '=', 'kelas.id')
            ->orderBy('kelas.nama_kelas')
            ->select('guru_bk_kelas.*')
            ->paginate(25)
            ->withQueryString();

        $kelasList = Kelas::orderBy('nama_kelas')->get();
        $guruBkList = User::where('role', 'guru_bk')->orderBy('name')->get();

        return view('kurikulum.guru-bk.index', compact('data', 'kelasList', 'guruBkList', 'tahunAjaran'));
    }

    public function store(Request $request)
    {
        $tahunAjaran = TahunAjaran::aktif();
        abort_if(! $tahunAjaran, 422, 'Tidak ada tahun ajaran aktif. Aktifkan dahulu di menu Tahun Ajaran.');

        $validated = $request->validate([
            'guru_id' => ['required', 'exists:users,id'],
            'kelas_id' => ['required', 'exists:kelas,id'],
        ]);
        $validated['tahun_ajaran_id'] = $tahunAjaran->id;

        GuruBkKelas::firstOrCreate($validated);

        return back()->with('success', 'Mapping Guru BK berhasil ditambahkan.');
    }

    public function destroy(GuruBkKelas $guruBk)
    {
        $guruBk->delete();
        return back()->with('success', 'Mapping berhasil dihapus.');
    }
}
