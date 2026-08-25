<?php

namespace App\Http\Controllers;

use App\Models\DisposisiSurat;
use App\Models\Surat;
use Carbon\Carbon;

class SuratDashboardController extends Controller
{
    public function index()
    {
        $bulanIni = now()->startOfMonth();

        $ringkasan = [
            'masuk' => Surat::where('arah', 'masuk')->count(),
            'masuk_menunggu_disposisi' => DisposisiSurat::whereHas('surat', fn ($q) => $q->where('arah', 'masuk'))
                ->where('status', 'menunggu')->count(),
            'keluar' => Surat::where('arah', 'keluar')->count(),
            'keluar_bulan_ini' => Surat::where('arah', 'keluar')->where('created_at', '>=', $bulanIni)->count(),
            'disposisi_aktif' => DisposisiSurat::whereIn('status', ['dibaca', 'diproses'])->count(),
            'selesai' => Surat::where('status', 'selesai')->count(),
            'selesai_bulan_ini' => Surat::where('status', 'selesai')->where('updated_at', '>=', $bulanIni)->count(),
            'diarsipkan' => Surat::where('status', 'diarsipkan')->count(),
        ];

        // Statistik 6 bulan terakhir (untuk grafik) — dihitung dari
        // created_at, konsisten dengan cara ringkasan di atas dihitung.
        $statistik = collect(range(5, 0))->map(function ($i) {
            $bulan = now()->subMonths($i);
            return [
                'label' => $bulan->translatedFormat('M Y'),
                'masuk' => Surat::where('arah', 'masuk')
                    ->whereYear('created_at', $bulan->year)->whereMonth('created_at', $bulan->month)->count(),
                'keluar' => Surat::where('arah', 'keluar')
                    ->whereYear('created_at', $bulan->year)->whereMonth('created_at', $bulan->month)->count(),
                'disposisi_aktif' => DisposisiSurat::whereYear('created_at', $bulan->year)
                    ->whereMonth('created_at', $bulan->month)->count(),
            ];
        })->values();

        $disposisiTerbaru = DisposisiSurat::with(['surat.jenisSurat', 'dariUser', 'kepadaUser'])
            ->orderByDesc('created_at')->limit(4)->get();

        $suratMasukTerbaru = Surat::with(['jenisSurat', 'disposisiTerbaru'])
            ->where('arah', 'masuk')
            ->orderByDesc('tanggal')->limit(5)->get();

        $pengingat = [
            'deadline' => DisposisiSurat::whereNotIn('status', ['selesai', 'ditolak'])
                ->whereNotNull('batas_waktu')
                ->whereBetween('batas_waktu', [now()->toDateString(), now()->addDays(3)->toDateString()])
                ->count(),
            'belum_diarsipkan' => Surat::where('status', 'selesai')->count(),
            'draft' => Surat::where('status', 'draft')->count(),
        ];

        return view('surat.dashboard', compact('ringkasan', 'statistik', 'disposisiTerbaru', 'suratMasukTerbaru', 'pengingat'));
    }
}
