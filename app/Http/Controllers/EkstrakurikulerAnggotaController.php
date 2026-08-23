<?php

namespace App\Http\Controllers;

use App\Models\Ekstrakurikuler;
use App\Models\EkstrakurikulerSiswa;
use App\Models\Siswa;
use Illuminate\Http\Request;

/**
 * Anggota (siswa) per kegiatan ekstrakurikuler — dikelola Kesiswaan.
 * Siswa lintas kelas/angkatan boleh jadi anggota kegiatan yang sama, dan
 * 1 siswa boleh ikut lebih dari 1 kegiatan (lihat migrasi ekstrakurikuler_siswas).
 */
class EkstrakurikulerAnggotaController extends Controller
{
    public function index(Request $request, Ekstrakurikuler $ekstrakurikuler)
    {
        $ekstrakurikuler->load(['anggotas.siswa.kelas']);

        // Pencarian siswa untuk DITAMBAHKAN — kecualikan yang sudah jadi
        // anggota kegiatan ini, supaya tidak dobel.
        $idSudahAnggota = $ekstrakurikuler->anggotas->pluck('siswa_id');
        $hasilCari = collect();
        if ($request->filled('cari')) {
            $hasilCari = Siswa::with('kelas')
                ->where('is_active', true)
                ->whereNotIn('id', $idSudahAnggota)
                ->where(function ($q) use ($request) {
                    $q->where('nama', 'like', "%{$request->cari}%")
                      ->orWhere('nis', 'like', "%{$request->cari}%");
                })
                ->orderBy('nama')
                ->limit(20)
                ->get();
        }

        return view('ekstrakurikuler.anggota', compact('ekstrakurikuler', 'hasilCari'));
    }

    public function store(Request $request, Ekstrakurikuler $ekstrakurikuler)
    {
        $validated = $request->validate([
            'siswa_id' => ['required', 'exists:siswas,id'],
        ]);

        $sudahAnggota = $ekstrakurikuler->anggotas()->where('siswa_id', $validated['siswa_id'])->exists();
        if ($sudahAnggota) {
            return back()->with('error', 'Siswa ini sudah jadi anggota kegiatan ini.');
        }

        EkstrakurikulerSiswa::create([
            'ekstrakurikuler_id' => $ekstrakurikuler->id,
            'siswa_id' => $validated['siswa_id'],
            'tanggal_gabung' => now()->toDateString(),
        ]);

        return back()->with('success', 'Siswa berhasil ditambahkan sebagai anggota.');
    }

    public function destroy(Ekstrakurikuler $ekstrakurikuler, EkstrakurikulerSiswa $anggota)
    {
        if ($anggota->ekstrakurikuler_id !== $ekstrakurikuler->id) {
            abort(404);
        }

        // Absensi ekskul yang SUDAH tersimpan untuk siswa ini TIDAK ikut
        // terhapus (baris di absensi_ekskul_pesertas berdiri sendiri lewat
        // siswa_id, bukan lewat baris anggota ini) — konsisten dengan
        // prinsip "riwayat tidak berubah" yang dipakai di seluruh sistem.
        $anggota->delete();

        return back()->with('success', 'Siswa berhasil dikeluarkan dari kegiatan ini.');
    }
}
