<?php

namespace App\Http\Controllers;

use App\Exports\TemplateExport;
use App\Imports\SiswaImport;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = Siswa::with('kelas')->when($request->kelas_id, fn ($q) => $q->where('kelas_id', $request->kelas_id));
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                  ->orWhere('nis', 'like', "%{$request->search}%");
            });
        }
        $siswas = $query->orderBy('nama')->paginate(25)->withQueryString();
        $kelasList = Kelas::orderBy('nama_kelas')->get();
        return view('siswa.index', compact('siswas', 'kelasList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'unique:siswas,nis'],
            'nisn' => ['nullable', 'string'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'no_hp_ortu' => ['nullable', 'string', 'max:20'],
            'kelas_id' => ['required', 'exists:kelas,id'],
        ]);
        Siswa::create($validated);
        return back()->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Siswa $siswa)
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'unique:siswas,nis,' . $siswa->id],
            'nisn' => ['nullable', 'string'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'no_hp_ortu' => ['nullable', 'string', 'max:20'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        $siswa->update($validated);
        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Siswa $siswa)
    {
        $siswa->delete();
        return back()->with('success', 'Siswa berhasil dihapus.');
    }

    public function importForm()
    {
        return view('siswa.import');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'mimes:xlsx,xls,csv']]);
        Excel::import(new SiswaImport(), $request->file('file'));
        return redirect()->route('siswa.index')->with('success', 'Import data siswa berhasil.');
    }

    public function template()
    {
        return Excel::download(new TemplateExport(
            ['nis', 'nisn', 'nama', 'jenis_kelamin', 'no_hp_ortu', 'kode_kelas'],
            [
                ['2526001', '0091234567', 'Ahmad Fauzan', 'L', '6281234567890', '7A'],
                ['2526002', '0091234568', 'Siti Aminah', 'P', '6281234567891', '7A'],
            ],
            'Data Siswa',
            [
                'Petunjuk:',
                '- nis wajib diisi dan bersifat unik (tidak boleh sama dengan siswa lain).',
                '- nisn boleh dikosongkan jika belum ada.',
                '- jenis_kelamin diisi L (Laki-laki) atau P (Perempuan).',
                '- no_hp_ortu diisi nomor WhatsApp orang tua format 62xxxxxxxxxx (tanpa +, tanpa spasi/strip). Boleh dikosongkan, tapi notifikasi Alfa tidak akan terkirim jika kosong.',
                '- kode_kelas diisi sesuai nama kelas pada menu Data Kelas (contoh: 7A).',
                '- Hapus baris contoh ini sebelum mengisi data yang sebenarnya.',
            ]
        ), 'template-data-siswa.xlsx');
    }
}
