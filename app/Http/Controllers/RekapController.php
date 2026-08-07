<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\JurnalMengajar;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Http\Request;

class RekapController extends Controller
{
    /**
     * Rekapitulasi menyeluruh untuk Kurikulum & Kepala Sekolah:
     * kepatuhan pengisian jurnal per guru per kelas per bulan.
     */
    public function index(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $rekapGuru = User::where('role', 'guru')
            ->withCount(['jurnalMengajar as jurnal_bulan_ini' => function ($q) use ($bulan, $tahun) {
                $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
            }])
            ->orderBy('name')
            ->get();

        $rekapKelas = Kelas::withCount(['siswas' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('nama_kelas')
            ->get()
            ->map(function ($kelas) use ($bulan, $tahun) {
                $jumlahJurnal = JurnalMengajar::where('kelas_id', $kelas->id)
                    ->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->count();
                $totalAlfa = \App\Models\AbsensiSiswa::where('kelas_id', $kelas->id)
                    ->where('status', 'Alfa')
                    ->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->count();
                return [
                    'kelas' => $kelas,
                    'jumlah_jurnal' => $jumlahJurnal,
                    'total_alfa' => $totalAlfa,
                ];
            });

        return view('rekap.index', compact('rekapGuru', 'rekapKelas', 'bulan', 'tahun'));
    }
}
