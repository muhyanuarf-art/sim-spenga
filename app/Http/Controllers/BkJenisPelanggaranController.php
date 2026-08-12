<?php

namespace App\Http\Controllers;

use App\Models\JenisPelanggaran;
use App\Services\PoinSiswaService;
use Illuminate\Http\Request;

/**
 * Master jenis pelanggaran — configurable, TIDAK hardcode (Bagian 9 spec).
 * Dikelola Guru BK & Admin saja.
 */
class BkJenisPelanggaranController extends Controller
{
    public function index(Request $request, PoinSiswaService $poinService)
    {
        $data = JenisPelanggaran::orderBy('kategori')->orderBy('nama')->get();
        $rentangKategori = PoinSiswaService::RENTANG_KATEGORI;
        return view('bk.jenis-pelanggaran.index', compact('data', 'rentangKategori'));
    }

    public function store(Request $request, PoinSiswaService $poinService)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:jenis_pelanggarans,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'in:Ringan,Sedang,Berat,Sangat Berat'],
            'poin_default' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        if (!$poinService->validasiPoinSesuaiKategori($validated['kategori'], (int) $validated['poin_default'])) {
            [$min, $max] = PoinSiswaService::RENTANG_KATEGORI[$validated['kategori']];
            return back()->withInput()->withErrors([
                'poin_default' => "Poin untuk kategori {$validated['kategori']} harus antara {$min}-{$max}.",
            ]);
        }

        JenisPelanggaran::create($validated);
        return back()->with('success', 'Jenis pelanggaran berhasil ditambahkan.');
    }

    public function update(Request $request, JenisPelanggaran $jenisPelanggaran, PoinSiswaService $poinService)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:jenis_pelanggarans,kode,' . $jenisPelanggaran->id],
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'in:Ringan,Sedang,Berat,Sangat Berat'],
            'poin_default' => ['required', 'integer', 'min:1', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (!$poinService->validasiPoinSesuaiKategori($validated['kategori'], (int) $validated['poin_default'])) {
            [$min, $max] = PoinSiswaService::RENTANG_KATEGORI[$validated['kategori']];
            return back()->withInput()->withErrors([
                'poin_default' => "Poin untuk kategori {$validated['kategori']} harus antara {$min}-{$max}.",
            ]);
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $jenisPelanggaran->update($validated);
        return back()->with('success', 'Jenis pelanggaran berhasil diperbarui.');
    }
}
