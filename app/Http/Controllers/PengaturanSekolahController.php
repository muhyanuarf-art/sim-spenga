<?php

namespace App\Http\Controllers;

use App\Models\PengaturanSekolah;
use Illuminate\Http\Request;

class PengaturanSekolahController extends Controller
{
    /**
     * Data relatif tetap milik sekolah (nama, lokasi, kepala sekolah, dst).
     * Hanya Admin & Kurikulum yang boleh mengubah (lihat routes/web.php).
     * Dipakai otomatis di semua halaman yang punya tombol Cetak, lewat
     * View composer 'pengaturanSekolahGlobal' (AppServiceProvider).
     */
    public function edit()
    {
        $pengaturan = PengaturanSekolah::current();

        return view('pengaturan-sekolah.edit', compact('pengaturan'));
    }

    public function update(Request $request)
    {
        $pengaturan = PengaturanSekolah::current();

        $validated = $request->validate([
            'nama_sekolah' => ['nullable', 'string', 'max:150'],
            'kabupaten_kota' => ['required', 'string', 'max:100'],
            'provinsi' => ['required', 'string', 'max:100'],
            'nama_kepala_sekolah' => ['nullable', 'string', 'max:150'],
            'nip_kepala_sekolah' => ['nullable', 'string', 'max:50'],
            'format_lokasi_ttd' => ['nullable', 'string', 'max:100'],
        ]);

        $pengaturan->update($validated);
        PengaturanSekolah::lupakanCache();

        return back()->with('success', 'Pengaturan sekolah berhasil disimpan.');
    }
}
