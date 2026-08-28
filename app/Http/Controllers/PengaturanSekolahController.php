<?php

namespace App\Http\Controllers;

use App\Models\PengaturanSekolah;
use App\Support\IkonAplikasi;
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
            'website_sekolah' => ['nullable', 'string', 'max:255'],
            'email_sekolah' => ['nullable', 'string', 'max:255'],
            'kabupaten_kota' => ['required', 'string', 'max:100'],
            'provinsi' => ['required', 'string', 'max:100'],
            'nama_kepala_sekolah' => ['nullable', 'string', 'max:150'],
            'nip_kepala_sekolah' => ['nullable', 'string', 'max:50'],
            'format_lokasi_ttd' => ['nullable', 'string', 'max:100'],
            'logo_kiri' => ['nullable', 'image', 'max:2048'],
            'logo_kanan' => ['nullable', 'image', 'max:2048'],
            'logo_aplikasi' => ['nullable', 'image', 'max:2048'],
            'hapus_logo_kiri' => ['nullable', 'boolean'],
            'hapus_logo_kanan' => ['nullable', 'boolean'],
            'hapus_logo_aplikasi' => ['nullable', 'boolean'],
        ], [
            'logo_kiri.image' => 'Logo Kiri harus berupa berkas gambar (JPG/PNG/SVG).',
            'logo_kanan.image' => 'Logo Kanan harus berupa berkas gambar (JPG/PNG/SVG).',
            'logo_aplikasi.image' => 'Logo Aplikasi harus berupa berkas gambar (JPG/PNG/SVG).',
            'logo_kiri.max' => 'Ukuran Logo Kiri melebihi 2 MB.',
            'logo_kanan.max' => 'Ukuran Logo Kanan melebihi 2 MB.',
            'logo_aplikasi.max' => 'Ukuran Logo Aplikasi melebihi 2 MB.',
        ]);

        // 'aplikasi' ikut di sini supaya ketiga logo diperlakukan sama
        // persis: unggah baru menggantikan berkas lama (yang lama dihapus
        // dari disk supaya tidak menumpuk), dan centang "Hapus" mengosongkan.
        foreach (['kiri', 'kanan', 'aplikasi'] as $sisi) {
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

        // Set ikon (favicon.ico, apple-touch-icon, ikon manifest) dibuat
        // ulang dari Logo Aplikasi setiap kali pengaturan disimpan. Peramban
        // dan ponsel butuh ikon dalam beberapa ukuran yang tajam; memaksa
        // logo 512px mengecil sendiri jadi 16px di tab browser hasilnya
        // buram. Lihat App\Support\IkonAplikasi.
        $ikonRaster = IkonAplikasi::perbarui($pengaturan->logo_aplikasi_path);

        $pesan = 'Pengaturan sekolah berhasil disimpan.';
        if ($pengaturan->logo_aplikasi_path && ! $ikonRaster && IkonAplikasi::url('favicon.svg')) {
            $pesan .= ' Logo berformat SVG dipakai langsung sebagai ikon — ukuran lainnya tidak dibuat '
                .'karena SVG sudah tajam di semua ukuran.';
        }

        return back()->with('success', $pesan);
    }
}
