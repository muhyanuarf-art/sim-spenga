<?php

namespace App\Http\Controllers;

use App\Models\Ekstrakurikuler;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Master data "Kegiatan Ekstrakurikuler" — dikelola Kesiswaan.
 * Ini langkah pertama dari fitur absensi ekskul: Kesiswaan input nama
 * kegiatan (+ pembina opsional) di sini dulu. Anggota/jadwal/absensi per
 * kegiatan menyusul di menu terpisah, dibangun di atas data ini.
 */
class EkstrakurikulerController extends Controller
{
    public function index()
    {
        $ekstrakurikuler = Ekstrakurikuler::with('pembina')->orderBy('nama_ekstrakurikuler')->paginate(25);
        // Pembina biasanya guru/guru BK, tapi daftar sengaja tidak dibatasi
        // ketat ke role itu saja — kesiswaan sendiri kadang jadi pembina.
        $calonPembina = User::whereIn('role', ['guru', 'guru_bk', 'kesiswaan'])->orderBy('name')->get();

        return view('ekstrakurikuler.index', compact('ekstrakurikuler', 'calonPembina'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_ekstrakurikuler' => ['required', 'string', 'max:255'],
            'pembina_id' => ['nullable', 'exists:users,id'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);
        $validated['is_aktif'] = true;

        Ekstrakurikuler::create($validated);

        return back()->with('success', 'Kegiatan ekstrakurikuler berhasil ditambahkan.');
    }

    public function update(Request $request, Ekstrakurikuler $ekstrakurikuler)
    {
        $validated = $request->validate([
            'nama_ekstrakurikuler' => ['required', 'string', 'max:255'],
            'pembina_id' => ['nullable', 'exists:users,id'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'is_aktif' => ['nullable', 'boolean'],
        ]);
        $validated['is_aktif'] = $request->boolean('is_aktif', true);

        $ekstrakurikuler->update($validated);

        return back()->with('success', 'Kegiatan ekstrakurikuler berhasil diperbarui.');
    }

    public function destroy(Ekstrakurikuler $ekstrakurikuler)
    {
        return $this->hapusAtauGagalDenganPesan(
            $ekstrakurikuler,
            'Kegiatan ekstrakurikuler berhasil dihapus.',
            'Kegiatan ini tidak dapat dihapus karena masih dipakai di data lain (mis. anggota/absensi).'
        );
    }
}
