<?php

namespace App\Http\Controllers;

use App\Models\KasusSiswa;
use App\Models\PemanggilanOrangTua;
use App\Models\PembinaanSiswa;
use App\Models\PenguranganPoinSiswa;
use App\Models\Siswa;
use App\Services\PoinSiswaService;
use App\Support\BkAccessScope;
use App\Support\RentangBulan;
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

        // PERBAIKAN PERFORMA — lihat App\Support\RentangBulan.
        [$awalBulanIni, $akhirBulanIni] = RentangBulan::dari((int) now()->year, (int) now()->month);
        $totalKasusBulanIni = (clone $kasusQuery)
            ->whereBetween('tanggal_kejadian', [$awalBulanIni, $akhirBulanIni])
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

        $siswaDalamPembinaanIds = (clone $pembinaanQuery)->where('status', 'Pembinaan')->distinct()->pluck('siswa_id');

        $siswaMembaikIds = PenguranganPoinSiswa::aktif()
            ->when($kelasIds !== null, fn ($q) => $q->whereHas('siswa', fn ($q2) => $q2->whereIn('kelas_id', $kelasIds)))
            ->where('tanggal', '>=', now()->subDays(30))
            ->distinct()->pluck('siswa_id');

        // PERBAIKAN PERFORMA (N+1): dulu ringkasan poin dihitung SATU PER SATU
        // per siswa lewat $poinService->ringkasan($s) di 5 tempat berbeda —
        // bisa >9 query PER SISWA, dan siswa yang sama bisa muncul di >1
        // daftar sehingga dihitung berkali-kali. Sekarang: kumpulkan SEMUA
        // siswa_id yang relevan sekaligus, hitung ringkasannya dalam jumlah
        // query TETAP lewat ringkasanBanyak() (lihat PoinSiswaService), baru
        // susun masing-masing daftar dari hasil yang sama.
        $semuaSiswaIds = $siswaIdsRelevan
            ->merge($siswaKasusAktifIds)
            ->merge($siswaDalamPembinaanIds)
            ->merge($siswaMembaikIds)
            ->unique()->values();

        $ringkasanPerSiswa = $poinService->ringkasanBanyak($semuaSiswaIds);
        $siswaMap = Siswa::with('kelas')->whereIn('id', $semuaSiswaIds)->get()->keyBy('id');

        $susunDaftar = function ($ids) use ($siswaMap, $ringkasanPerSiswa) {
            return collect($ids)
                ->map(fn ($id) => isset($siswaMap[$id])
                    ? ['siswa' => $siswaMap[$id], ...($ringkasanPerSiswa[$id] ?? [])]
                    : null)
                ->filter()
                ->values();
        };

        $siswaPoinTertinggi = $susunDaftar($siswaIdsRelevan)->sortByDesc('poin_aktif')->take(10)->values();
        $siswaKasusBelumSelesai = $susunDaftar($siswaKasusAktifIds)->sortByDesc('poin_aktif')->values();
        $siswaDalamPembinaan = $susunDaftar($siswaDalamPembinaanIds)->values();
        $butuhPemanggilanOrtu = $susunDaftar($siswaIdsRelevan)
            ->filter(fn ($r) => in_array($r['rekomendasi_tahap'], [4, 5]))
            ->sortByDesc('poin_aktif')->values();
        $siswaMembaik = $susunDaftar($siswaMembaikIds)->values();

        return view('bk.dashboard', compact(
            'totalKasusBulanIni', 'siswaKasusAktifIds', 'sebaranTahap',
            'siswaPoinTertinggi', 'siswaKasusBelumSelesai', 'siswaDalamPembinaan',
            'butuhPemanggilanOrtu', 'siswaMembaik'
        ));
    }
}
