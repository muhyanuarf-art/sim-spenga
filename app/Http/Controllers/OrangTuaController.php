<?php

namespace App\Http\Controllers;

use App\Exports\TemplateExport;
use App\Imports\OrangTuaImport;
use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OrangTuaController extends Controller
{
    public function index(Request $request)
    {
        $query = OrangTua::with('siswa.kelas')
            ->when($request->kelas_id, fn ($q) => $q->whereHas('siswa', fn ($s) => $s->where('kelas_id', $request->kelas_id)))
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($qq) use ($request) {
                    $qq->where('nis', 'like', "%{$request->search}%")
                       ->orWhereHas('siswa', fn ($s) => $s->where('nama', 'like', "%{$request->search}%"));
                });
            });

        $akunOrtu = $query->latest()->paginate(25)->withQueryString();
        $kelasList = Kelas::orderBy('nama_kelas')->get();
        $jumlahSiswaBelumPunyaAkun = Siswa::where('is_active', true)
            ->whereDoesntHave('orangTua')
            ->count();

        return view('orangtua.index', compact('akunOrtu', 'kelasList', 'jumlahSiswaBelumPunyaAkun'));
    }

    public function importForm()
    {
        return view('orangtua.import');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'mimes:xlsx,xls,csv']]);

        $import = new OrangTuaImport();
        Excel::import($import, $request->file('file'));

        $pesan = "Berhasil membuat {$import->dibuat} akun orang tua baru (password default: password).";
        if (! empty($import->dilewatiSudahAda)) {
            $pesan .= ' ' . count($import->dilewatiSudahAda) . ' NIS dilewati karena sudah punya akun.';
        }
        if (! empty($import->dilewatiTidakDitemukan)) {
            $pesan .= ' ' . count($import->dilewatiTidakDitemukan) . ' NIS dilewati karena data siswa tidak ditemukan: '
                . implode(', ', array_slice($import->dilewatiTidakDitemukan, 0, 10))
                . (count($import->dilewatiTidakDitemukan) > 10 ? ', ...' : '');
        }

        return redirect()->route('orangtua-akun.index')->with('success', $pesan);
    }

    public function template()
    {
        return Excel::download(new TemplateExport(
            ['nis'],
            [
                ['2526001'],
                ['2526002'],
            ],
            'Import Akun Orang Tua',
            [
                'Petunjuk:',
                '- Kolom nis wajib diisi sesuai NIS siswa yang sudah terdaftar di menu Data Siswa.',
                '- NIS yang tidak ditemukan di Data Siswa akan otomatis dilewati.',
                '- NIS yang sudah punya akun orang tua akan dilewati (tidak menimpa password lama).',
                '- Password default akun baru adalah "password". Orang tua wajib menggantinya',
                '  setelah login pertama. Admin bisa mereset password dari menu Data Orang Tua.',
                '- Hapus baris contoh ini sebelum mengisi data yang sebenarnya.',
            ]
        ), 'template-import-akun-orangtua.xlsx');
    }

    public function resetPassword(OrangTua $orangTua)
    {
        $orangTua->update([
            'password' => OrangTuaImport::PASSWORD_DEFAULT,
            'password_diubah_at' => null,
        ]);

        return back()->with('success', "Password akun orang tua NIS {$orangTua->nis} berhasil direset ke default.");
    }

    public function destroy(OrangTua $orangTua)
    {
        $orangTua->delete();
        return back()->with('success', 'Akun orang tua berhasil dihapus.');
    }
}
