<?php

namespace App\Http\Controllers;

use App\Models\Surat;

/**
 * (2026-08-26) — disederhanakan mengikuti rombak modul Surat jadi khusus
 * BK: kartu/statistik terkait Disposisi DIHAPUS (disposisi tidak lagi
 * dipakai — lihat SuratController). Cuma role guru_bk/admin yang buka
 * dashboard ini (lihat routes/web.php).
 */
class SuratDashboardController extends Controller
{
    public function index()
    {
        $bulanIni = now()->startOfMonth();

        $ringkasan = [
            'total' => Surat::count(),
            'bulan_ini' => Surat::where('created_at', '>=', $bulanIni)->count(),
            'selesai' => Surat::where('status', 'selesai')->count(),
            'selesai_bulan_ini' => Surat::where('status', 'selesai')->where('updated_at', '>=', $bulanIni)->count(),
            'draft' => Surat::where('status', 'draft')->count(),
            'diarsipkan' => Surat::where('status', 'diarsipkan')->count(),
        ];

        // Statistik 6 bulan terakhir (untuk grafik) — dihitung dari created_at.
        $statistik = collect(range(5, 0))->map(function ($i) {
            $bulan = now()->subMonths($i);
            return [
                'label' => $bulan->translatedFormat('M Y'),
                'jumlah' => Surat::whereYear('created_at', $bulan->year)->whereMonth('created_at', $bulan->month)->count(),
            ];
        })->values();

        $suratTerbaru = Surat::with(['jenisSurat', 'siswa'])
            ->orderByDesc('tanggal')->limit(8)->get();

        return view('surat.dashboard', compact('ringkasan', 'statistik', 'suratTerbaru'));
    }
}
