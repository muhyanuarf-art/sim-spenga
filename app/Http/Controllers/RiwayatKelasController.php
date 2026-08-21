<?php

namespace App\Http\Controllers;

use App\Models\Siswa;

/**
 * (Revisi permintaan admin) — Menggantikan KenaikanKelasController.
 *
 * Fitur "Kenaikan Kelas" (proses pindah kelas massal lewat menu tersendiri)
 * DIHAPUS — sekolah ini memindahkan siswa antar kelas/tahun ajaran lewat
 * Import Excel Data Siswa (lihat app/Imports/SiswaImport.php), bukan lewat
 * menu ini.
 *
 * Yang TETAP ADA & disengaja dipertahankan: melihat HISTORI kelas siswa.
 * Data lama (riwayat_kelas_siswas) tidak dihapus sama sekali — tetap bisa
 * ditelusuri admin (lewat halaman ini) maupun orang tua (lewat Portal Orang
 * Tua, lihat OrangTuaDashboardController) kapan pun dibutuhkan.
 */
class RiwayatKelasController extends Controller
{
    /** Riwayat kelas seorang siswa, bernomor, urut dari periode paling awal. */
    public function show(Siswa $siswa)
    {
        $riwayat = $siswa->riwayatKelas()->with(['tahunAjaran', 'kelasAsal', 'kelas', 'dicatatOleh'])->get();

        return view('riwayat-kelas.show', compact('siswa', 'riwayat'));
    }
}
