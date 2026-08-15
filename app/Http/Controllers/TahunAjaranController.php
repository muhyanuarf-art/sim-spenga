<?php

namespace App\Http\Controllers;

use App\Models\TahunAjaran;
use Illuminate\Http\Request;

class TahunAjaranController extends Controller
{
    public function index()
    {
        $tahunAjaran = TahunAjaran::orderByDesc('id')->get();
        return view('tahun-ajaran.index', compact('tahunAjaran'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
        ]);
        TahunAjaran::create($validated);
        return back()->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function update(Request $request, TahunAjaran $tahunAjaran)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
        ]);
        $tahunAjaran->update($validated);
        return back()->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function aktifkan(TahunAjaran $tahunAjaran)
    {
        TahunAjaran::query()->update(['is_active' => false]);
        $tahunAjaran->update(['is_active' => true]);
        return back()->with('success', "Tahun ajaran {$tahunAjaran->nama} {$tahunAjaran->semester} sekarang aktif.");
    }

    public function destroy(TahunAjaran $tahunAjaran)
    {
        return $this->hapusAtauGagalDenganPesan(
            $tahunAjaran,
            'Tahun ajaran berhasil dihapus.',
            'Tahun ajaran ini tidak dapat dihapus karena masih memiliki data terkait (jadwal, mapping guru, atau data lain).'
        );
    }
}
