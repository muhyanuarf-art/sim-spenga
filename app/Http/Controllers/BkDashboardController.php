<?php

namespace App\Http\Controllers;

use App\Models\KasusSiswa;
use App\Models\PemanggilanOrangTua;
use App\Models\PembinaanSiswa;
use App\Models\PenguranganPoinSiswa;
use App\Models\Siswa;
use App\Services\PoinSiswaService;
use App\Support\BkAccessScope;
use Illuminate\Http\Request;

class BkDashboardController extends Controller
{
    use BkAccessScope;

    public function index(Request $request, PoinSiswaService $poinService)
    {
        $user = $request->user();
        $kelasIds = $this->bkKelasIdsUntukUser($user);

        $kasusQuery = KasusSiswa::aktif()->when($kelasIds !== null, fn ($q) => $q->whereIn('kelas_id', $kelasIds));
        $pembinaanQuery = PembinaanSiswa::when($kelasIds !== null,
            fn ($q) => $q->whereHas('siswa', fn ($q2) => $q2->whereIn('kelas_id', $kelasIds)));

        $totalKasusBulanIni = (clone $kasusQuery)
            ->whereMonth('tanggal_kejadian', now()->month)
            ->whereYear('tanggal_kejadian', now()->year)
            ->count();

        $siswaKasusAktifIds = (clone $kasusQuery)
            ->whereNotIn('status', ['Selesai'])
            ->distinct()->pluck('siswa_id');

        // Tahap SAAT INI per siswa = tahap dari pembinaan TERAKHIR siswa tsb.
        $pembinaanTerbaruPerSiswa = (clone $pembinaanQuery)
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->get(['siswa_id', 'tahap'])
            ->unique('siswa_id');

        $sebaranTahap = $pembinaanTerbaruPerSiswa->countBy('tahap'); // [1=>x, 2=>y, ...]

        // ==== Daftar perhatian ====
        $siswaIdsRelevan = KasusSiswa::aktif()
            ->when($kelasIds !== null, fn ($q) => $q->whereIn('kelas_id', $kelasIds))
            ->distinct()->pluck('siswa_id');

        $siswaPoinTertinggi = Siswa::with('kelas')->whereIn('id', $siswaIdsRelevan)->get()
            ->map(fn ($s) => ['siswa' => $s, ...$poinService->ringkasan($s)])
            ->sortByDesc('poin_aktif')->take(10)->values();

        $siswaKasusBelumSelesai = Siswa::with('kelas')->whereIn('id', $siswaKasusAktifIds)->get()
            ->map(fn ($s) => ['siswa' => $s, ...$poinService->ringkasan($s)])
            ->sortByDesc('poin_aktif')->values();

        $siswaDalamPembinaan = Siswa::with('kelas')->whereIn('id',
            (clone $pembinaanQuery)->where('status', 'Pembinaan')->distinct()->pluck('siswa_id')
        )->get()->map(fn ($s) => ['siswa' => $s, ...$poinService->ringkasan($s)])->values();

        $butuhPemanggilanOrtu = Siswa::with('kelas')->whereIn('id', $siswaIdsRelevan)->get()
            ->map(fn ($s) => ['siswa' => $s, ...$poinService->ringkasan($s)])
            ->filter(fn ($r) => in_array($r['rekomendasi_tahap'], [4, 5]))
            ->sortByDesc('poin_aktif')->values();

        $siswaMembaik = Siswa::with('kelas')->whereIn('id',
            PenguranganPoinSiswa::aktif()
                ->when($kelasIds !== null, fn ($q) => $q->whereHas('siswa', fn ($q2) => $q2->whereIn('kelas_id', $kelasIds)))
                ->where('tanggal', '>=', now()->subDays(30))
                ->distinct()->pluck('siswa_id')
        )->get()->map(fn ($s) => ['siswa' => $s, ...$poinService->ringkasan($s)])->values();

        return view('bk.dashboard', compact(
            'totalKasusBulanIni', 'siswaKasusAktifIds', 'sebaranTahap',
            'siswaPoinTertinggi', 'siswaKasusBelumSelesai', 'siswaDalamPembinaan',
            'butuhPemanggilanOrtu', 'siswaMembaik'
        ));
    }
}
