<?php

namespace App\Http\Controllers;

use App\Exports\TemplateExport;
use App\Imports\KelasImport;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class KelasController extends Controller
{
    public function index()
    {
        $kelas = Kelas::withCount('siswas')->with('waliKelas')->orderBy('nama_kelas')->paginate(20);
        $guruList = User::where('role', 'guru')->orderBy('name')->get();
        return view('kelas.index', compact('kelas', 'guruList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:10', 'unique:kelas,nama_kelas'],
            'tingkat' => ['required', 'integer', 'in:7,8,9'],
            'wali_kelas_id' => ['nullable', 'exists:users,id'],
        ]);
        Kelas::create($validated);
        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, Kelas $kelas)
    {
        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:10', 'unique:kelas,nama_kelas,' . $kelas->id],
            'tingkat' => ['required', 'integer', 'in:7,8,9'],
            'wali_kelas_id' => ['nullable', 'exists:users,id'],
        ]);
        $kelas->update($validated);
        return back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kelas)
    {
        return $this->hapusAtauGagalDenganPesan(
            $kelas,
            'Kelas berhasil dihapus.',
            'Kelas ini tidak dapat dihapus karena masih memiliki data terkait (siswa, jadwal, atau data lain).'
        );
    }

    public function importForm()
    {
        return view('kelas.import');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'mimes:xlsx,xls,csv']]);
        Excel::import(new KelasImport(), $request->file('file'));
        return redirect()->route('kelas.index')->with('success', 'Import data kelas berhasil.');
    }

    public function template()
    {
        return Excel::download(new TemplateExport(
            ['nama_kelas', 'tingkat', 'nip_wali_kelas'],
            [
                ['7A', 7, '198501012010011001'],
                ['7B', 7, ''],
            ],
            'Data Kelas',
            [
                'Petunjuk:',
                '- nama_kelas wajib diisi dan bersifat unik (contoh: 7A, 8B, 9C).',
                '- tingkat diisi salah satu dari: 7, 8, atau 9.',
                '- nip_wali_kelas bersifat opsional; jika diisi, harus NIP guru yang sudah terdaftar di menu Kelola Pengguna.',
                '- Hapus baris contoh ini sebelum mengisi data yang sebenarnya.',
            ]
        ), 'template-data-kelas.xlsx');
    }
}
