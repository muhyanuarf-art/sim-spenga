<?php

namespace App\Http\Controllers;

use App\Models\JenisSurat;
use App\Support\SuratMerge;
use Illuminate\Http\Request;

/**
 * Master "Jenis Surat" — dipakai BERSAMA oleh Kesiswaan & BK. Lihat
 * App\Support\SuratMerge untuk daftar placeholder yang bisa dipakai
 * di template_isi.
 */
class JenisSuratController extends Controller
{
    public function index()
    {
        $jenisSurat = JenisSurat::withCount('surats')->orderBy('nama_jenis')->paginate(25);
        $placeholder = SuratMerge::DAFTAR_PLACEHOLDER;

        return view('surat.jenis-index', compact('jenisSurat', 'placeholder'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_jenis' => ['required', 'string', 'max:150'],
            'kode_jenis' => ['nullable', 'string', 'max:10'],
            'template_isi' => ['nullable', 'string', 'max:5000'],
        ]);
        $validated['kode_jenis'] = $validated['kode_jenis'] ? strtoupper($validated['kode_jenis']) : null;

        JenisSurat::create($validated);

        return back()->with('success', 'Jenis surat berhasil ditambahkan.');
    }

    public function update(Request $request, JenisSurat $jenisSurat)
    {
        $validated = $request->validate([
            'nama_jenis' => ['required', 'string', 'max:150'],
            'kode_jenis' => ['nullable', 'string', 'max:10'],
            'template_isi' => ['nullable', 'string', 'max:5000'],
        ]);
        $validated['kode_jenis'] = $validated['kode_jenis'] ? strtoupper($validated['kode_jenis']) : null;

        $jenisSurat->update($validated);

        return back()->with('success', 'Jenis surat berhasil diperbarui.');
    }

    public function destroy(JenisSurat $jenisSurat)
    {
        return $this->hapusAtauGagalDenganPesan(
            $jenisSurat,
            'Jenis surat berhasil dihapus.',
            'Jenis surat ini tidak dapat dihapus karena masih dipakai di surat yang sudah dibuat.'
        );
    }
}
