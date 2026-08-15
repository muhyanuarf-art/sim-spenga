<?php

namespace App\Http\Controllers;

use App\Exports\TemplateExport;
use App\Imports\GuruMengajarImport;
use App\Models\GuruMengajarKelas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class GuruMengajarController extends Controller
{
    public function index(Request $request)
    {
        $tahunAjaran = TahunAjaran::aktif();
        $query = GuruMengajarKelas::with(['guru', 'kelas', 'mapel'])
            ->when($tahunAjaran, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
            ->when($request->kelas_id, fn ($q) => $q->where('kelas_id', $request->kelas_id))
            ->when($request->guru_id, fn ($q) => $q->where('guru_id', $request->guru_id));

        $data = $query->join('kelas', 'guru_mengajar_kelas.kelas_id', '=', 'kelas.id')
            ->orderBy('kelas.nama_kelas')
            ->select('guru_mengajar_kelas.*')
            ->paginate(25)
            ->withQueryString();

        $kelasList = Kelas::orderBy('nama_kelas')->get();
        $guruList = User::where('role', 'guru')->orderBy('name')->get();
        $mapelList = MataPelajaran::orderBy('nama_mapel')->get();

        return view('kurikulum.guru-mengajar.index', compact('data', 'kelasList', 'guruList', 'mapelList', 'tahunAjaran'));
    }

    public function store(Request $request)
    {
        $tahunAjaran = TahunAjaran::aktif();
        abort_if(! $tahunAjaran, 422, 'Tidak ada tahun ajaran aktif. Aktifkan dahulu di menu Tahun Ajaran.');

        $validated = $request->validate([
            'guru_id' => ['required', 'exists:users,id'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'mata_pelajaran_id' => ['required', 'exists:mata_pelajarans,id'],
        ]);
        $validated['tahun_ajaran_id'] = $tahunAjaran->id;

        GuruMengajarKelas::firstOrCreate($validated);

        return back()->with('success', 'Mapping guru mengajar berhasil ditambahkan.');
    }

    public function update(Request $request, GuruMengajarKelas $guruMengajar)
    {
        $validated = $request->validate([
            'guru_id' => ['required', 'exists:users,id'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'mata_pelajaran_id' => ['required', 'exists:mata_pelajarans,id'],
        ]);

        $guruMengajar->update($validated);

        return back()->with('success', 'Mapping guru mengajar berhasil diperbarui.');
    }

    public function destroy(GuruMengajarKelas $guruMengajar)
    {
        return $this->hapusAtauGagalDenganPesan(
            $guruMengajar,
            'Mapping berhasil dihapus.',
            'Mapping ini tidak dapat dihapus karena masih dipakai di jadwal pelajaran.'
        );
    }

    public function importForm()
    {
        return view('kurikulum.guru-mengajar.import');
    }

    public function template()
    {
        return Excel::download(new TemplateExport(
            ['nip_guru', 'kode_kelas', 'kode_mapel'],
            [
                ['198501012010011001', '7A', 'MTK'],
                ['198502022011012002', '7B', 'IPA'],
            ],
            'Mapping Guru Mengajar',
            [
                'Petunjuk:',
                '- nip_guru diisi dengan NIP guru yang sudah terdaftar di menu Kelola Pengguna.',
                '- kode_kelas diisi sesuai nama kelas pada menu Data Kelas (contoh: 7A).',
                '- kode_mapel diisi sesuai kode pada menu Mata Pelajaran (contoh: MTK).',
                '- Hapus baris contoh ini sebelum mengisi data yang sebenarnya.',
            ]
        ), 'template-mapping-guru-mengajar.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'mimes:xlsx,xls,csv']]);

        $tahunAjaran = TahunAjaran::aktif();
        abort_if(! $tahunAjaran, 422, 'Tidak ada tahun ajaran aktif.');

        Excel::import(new GuruMengajarImport($tahunAjaran->id), $request->file('file'));

        return redirect()->route('kurikulum.guru-mengajar.index')
            ->with('success', 'Import data guru mengajar kelas berhasil.');
    }
}
