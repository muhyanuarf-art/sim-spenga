<?php

namespace App\Http\Controllers;

use App\Exports\TemplateExport;
use App\Imports\MataPelajaranImport;
use App\Models\MataPelajaran;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MataPelajaranController extends Controller
{
    public function index()
    {
        $mapel = MataPelajaran::orderBy('nama_mapel')->paginate(25);
        return view('mapel.index', compact('mapel'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:mata_pelajarans,kode'],
            'nama_mapel' => ['required', 'string', 'max:255'],
        ]);
        MataPelajaran::create($validated);
        return back()->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, MataPelajaran $mapel)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:mata_pelajarans,kode,' . $mapel->id],
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
        $request->validate(['file' => ['required', 'mimes:xlsx,xls,csv']]);
        Excel::import(new MataPelajaranImport(), $request->file('file'));
        return redirect()->route('mapel.index')->with('success', 'Import mata pelajaran berhasil.');
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
