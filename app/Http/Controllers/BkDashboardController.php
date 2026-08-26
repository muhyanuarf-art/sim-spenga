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

        // (2026-08-26) — pembanding "bulan lalu" untuk kartu ringkasan.
        // HANYA "Total Kasus Bulan Ini" yang punya pembanding SUNGGUHAN
        // (dihitung dari tanggal_kejadian, sama-sama apple-to-apple).
        // 3 kartu lainnya (Siswa Kasus Aktif, Sedang Pembinaan, Perlu
        // Pemanggilan) adalah ANGKA KONDISI SAAT INI (status sekarang),
        // BUKAN kejadian bertanggal — sistem ini tidak menyimpan snapshot
        // harian/bulanan dari status-status itu, jadi "dari X bulan lalu"
        // untuk ketiganya TIDAK BISA dihitung akurat. Dilihat lagi di
        // view: ketiganya sengaja TIDAK diberi badge persentase naik/turun
        // supaya tidak menampilkan angka yang terlihat presisi padahal
        // sebenarnya perkiraan/tidak valid.
        [$awalBulanLalu, $akhirBulanLalu] = RentangBulan::dari(
            (int) now()->subMonthNoOverflow()->year, (int) now()->subMonthNoOverflow()->month
        );
        $totalKasusBulanLalu = KasusSiswa::aktif()
            ->when($kelasIds !== null, fn ($q) => $q->whereIn('kelas_id', $kelasIds))
            ->whereBetween('tanggal_kejadian', [$awalBulanLalu, $akhirBulanLalu])
            ->count();

        $kasusTerbaru = KasusSiswa::aktif()
            ->when($kelasIds !== null, fn ($q) => $q->whereIn('kelas_id', $kelasIds))
            ->with(['siswa.kelas', 'jenisPelanggaran', 'pembinaanTerbaru'])
            ->orderByDesc('tanggal_kejadian')->orderByDesc('id')
            ->limit(5)->get();

        // Tren 6 bulan — "Aktif" di sini = dari kasus yang TERJADI pada
        // bulan itu (berdasar tanggal_kejadian), berapa yang STATUSNYA
        // SEKARANG belum Selesai. Beda dari kartu "Siswa Kasus Aktif" di
        // atas (yang scope-nya semua kasus aktif, bukan cuma bulan itu).
        $statistikTren = collect(range(5, 0))->map(function ($i) use ($kelasIds) {
            $bulan = now()->subMonths($i);
            [$awal, $akhir] = RentangBulan::dari((int) $bulan->year, (int) $bulan->month);
            $q = KasusSiswa::aktif()
                ->when($kelasIds !== null, fn ($q) => $q->whereIn('kelas_id', $kelasIds))
                ->whereBetween('tanggal_kejadian', [$awal, $akhir]);
            $total = (clone $q)->count();
            $selesai = (clone $q)->where('status', 'Selesai')->count();
            return [
                'label' => $bulan->translatedFormat('M Y'),
                'total' => $total,
                'aktif' => $total - $selesai,
                'selesai' => $selesai,
            ];
        })->values();

        return view('bk.dashboard', compact(
            'totalKasusBulanIni', 'totalKasusBulanLalu', 'siswaKasusAktifIds', 'sebaranTahap',
            'siswaPoinTertinggi', 'siswaKasusBelumSelesai', 'siswaDalamPembinaan',
            'butuhPemanggilanOrtu', 'siswaMembaik', 'kasusTerbaru', 'statistikTren'
        ));
    }
}
