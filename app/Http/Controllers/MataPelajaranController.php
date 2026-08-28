<?php

namespace App\Http\Controllers;

use App\Support\JalankanImport;
use App\Exports\TemplateExport;
use App\Imports\MataPelajaranImport;
use App\Models\MataPelajaran;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class MataPelajaranController extends Controller
{
    public function index()
    {
        $mapel = MataPelajaran::periodeAktif()->orderBy('nama_mapel')->paginate(25);
        return view('mapel.index', compact('mapel'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => [
                'required', 'string', 'max:20',
                // Keunikan kode dihitung PER TAHUN AJARAN — kode yang sama
                // pada periode lain adalah baris tersendiri (lihat migrasi
                // 2026_08_28_000003_add_tahun_ajaran_to_master_tables).
                Rule::unique('mata_pelajarans', 'kode')
                    ->where(fn ($q) => $q->where('tahun_ajaran_id', MataPelajaran::idPeriodeAktif())),
            ],
            'nama_mapel' => ['required', 'string', 'max:255'],
        ]);
        MataPelajaran::create($validated);
        return back()->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, MataPelajaran $mapel)
    {
        $validated = $request->validate([
            'kode' => [
                'required', 'string', 'max:20',
                Rule::unique('mata_pelajarans', 'kode')
                    ->where(fn ($q) => $q->where('tahun_ajaran_id', $mapel->tahun_ajaran_id))
                    ->ignore($mapel->id),
            ],
            'nama_mapel' => ['required', 'string', 'max:255'],
        ]);
        $mapel->update($validated);
        return back()->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(MataPelajaran $mapel)
    {
        return $this->hapusAtauGagalDenganPesan(
            $mapel,
            'Mata pelajaran berhasil dihapus.',
            'Mata pelajaran ini tidak dapat dihapus karena masih dipakai di jadwal atau data lain.'
        );
    }

    public function importForm()
    {
        return view('mapel.import');
    }

    public function import(Request $request)
    {
        [$aturan, $pesan] = JalankanImport::aturanBerkas();
        $request->validate($aturan, $pesan);

        return JalankanImport::jalankan(new MataPelajaranImport(), $request->file('file'), 'mapel.import.form');
    }

    public function template()
    {
        return Excel::download(new TemplateExport(
            ['kode', 'nama_mapel'],
            [
                ['MTK', 'Matematika'],
                ['IPA', 'Ilmu Pengetahuan Alam'],
            ],
            'Mata Pelajaran',
            [
                'Petunjuk:',
                '- kode wajib diisi dan bersifat unik (contoh: MTK, IPA, BIN, ING, INF).',
                '- nama_mapel diisi nama lengkap mata pelajaran.',
                '- Hapus baris contoh ini sebelum mengisi data yang sebenarnya.',
            ]
        ), 'template-mata-pelajaran.xlsx');
    }
}
