<?php

namespace App\Http\Controllers;

use App\Models\PengaturanSekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PengaturanSekolahController extends Controller
{
    /**
     * Data relatif tetap milik sekolah (nama, lokasi, kepala sekolah, dst).
     * Hanya Admin & Kurikulum yang boleh mengubah (lihat routes/web.php).
     * Dipakai otomatis di semua halaman yang punya tombol Cetak, lewat
     * View composer 'pengaturanSekolahGlobal' (AppServiceProvider) — baik
     * untuk baris tanda tangan maupun KOP Surat (lihat komponen
     * <x-kop-surat />, hanya tampil saat Cetak, bukan di layar).
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
            'pemerintah_daerah' => ['nullable', 'string', 'max:150'],
            'instansi_induk' => ['nullable', 'string', 'max:150'],
            'unit_kerja' => ['nullable', 'string', 'max:150'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'alamat_sekolah' => ['nullable', 'string', 'max:255'],
            'email_sekolah' => ['nullable', 'string', 'max:255'],
            'kabupaten_kota' => ['required', 'string', 'max:100'],
            'provinsi' => ['required', 'string', 'max:100'],
            'nama_kepala_sekolah' => ['nullable', 'string', 'max:150'],
            'nip_kepala_sekolah' => ['nullable', 'string', 'max:50'],
            'format_lokasi_ttd' => ['nullable', 'string', 'max:100'],
            'logo_kiri' => ['nullable', 'image', 'max:2048'],
            'logo_kanan' => ['nullable', 'image', 'max:2048'],
            'hapus_logo_kiri' => ['nullable', 'boolean'],
            'hapus_logo_kanan' => ['nullable', 'boolean'],
        ]);

        foreach (['kiri', 'kanan'] as $sisi) {
            $kolom = "logo_{$sisi}_path";
            if ($request->hasFile("logo_{$sisi}")) {
                if ($pengaturan->$kolom) {
                    Storage::disk('public')->delete($pengaturan->$kolom);
                }
                $validated[$kolom] = $request->file("logo_{$sisi}")->store('pengaturan-sekolah', 'public');
            } elseif ($request->boolean("hapus_logo_{$sisi}") && $pengaturan->$kolom) {
                Storage::disk('public')->delete($pengaturan->$kolom);
                $validated[$kolom] = null;
            }
            // Bukan kolom di tabel — jangan ikut di mass-assignment.
            unset($validated["logo_{$sisi}"], $validated["hapus_logo_{$sisi}"]);
        }

        $pengaturan->update($validated);
        PengaturanSekolah::lupakanCache();

        return back()->with('success', 'Pengaturan sekolah berhasil disimpan.');
    }
}
