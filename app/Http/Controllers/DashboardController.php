<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSiswa;
use App\Models\GuruMengajarKelas;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\JurnalMengajar;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\OnboardingChecklistService;
use App\Support\SesiMengajarGrouper;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tahunAjaran = TahunAjaran::aktif();

        // Admin & Kepala Sekolah: ringkasan sekolah menyeluruh
        if ($user->role === 'admin' || $user->role === 'kepala_sekolah') {
            $totalSiswa = Siswa::where('is_active', true)->count();
            $totalGuru = User::where('role', 'guru')->count();
            // STEP 5 Bagian 23 — hitungan kelas default TAHUN AJARAN AKTIF.
            $totalKelas = Kelas::aktif()->count();

            // PERBAIKAN PERFORMA — sebelumnya absensi hari ini di-fetch DUA KALI
            // terpisah (1x di sini untuk rekap status, 1x lagi lewat
            // AbsensiSiswa::siswaAlfaHariIni() untuk daftar Alfa), padahal
            // sumber datanya sama persis (seluruh absensi hari ini). Sekarang
            // eager-load-nya digabung jadi satu query, lalu dipakai ulang
            // untuk kedua keperluan lewat AbsensiSiswa::alfaDariRecordsPerSiswa().
            $absensiHariIniRaw = AbsensiSiswa::whereDate('tanggal', now()->toDateString())
                ->with(['siswa', 'kelas', 'jurnal.mapel', 'jurnal.jamPelajaran', 'jurnal.jamPelajaranAkhir'])
                ->get()
                ->groupBy('siswa_id');

            // Rekap dihitung dari status FINAL per siswa per hari (jam paling
            // akhir), bukan dari mentah semua record mapel, supaya 1 siswa
            // tidak terhitung berkali-kali kalau diabsen lebih dari 1 mapel.
            $rekapHariIni = $absensiHariIniRaw
                ->map(fn ($recordsSiswa) => AbsensiSiswa::finalPerHari($recordsSiswa)->first())
                ->groupBy('status')
                ->map->count();

            $siswaAlfaHariIni = AbsensiSiswa::alfaDariRecordsPerSiswa($absensiHariIniRaw);

            $jurnalHariIni = JurnalMengajar::whereDate('tanggal', now()->toDateString())->count();
            $jadwalHariIni = $tahunAjaran
                ? JadwalPelajaran::where('tahun_ajaran_id', $tahunAjaran->id)->where('hari', $this->hariIndonesia())->count()
                : 0;

            // PERBAIKAN PERFORMA (N+1) — sebelumnya query "sudah diabsen hari
            // ini?" dijalankan SATU PER SATU per kelas di dalam map() (1 query
            // × jumlah kelas). Sekarang dihitung sekaligus lewat 1 query
            // GROUP BY, dan Wali Kelas di-eager-load (->with('waliKelas'))
            // supaya tidak lazy-load 1 query per kelas juga.
            $kelasSudahDiabsenIds = AbsensiSiswa::whereDate('tanggal', now()->toDateString())
                ->distinct()->pluck('kelas_id')->flip();

            $rekapPerKelas = Kelas::aktif()->with('waliKelas')
                ->withCount(['siswas' => fn ($q) => $q->where('is_active', true)])
                ->orderBy('nama_kelas')
                ->get()
                ->map(function ($kelas) use ($kelasSudahDiabsenIds) {
                    return [
                        'kelas' => $kelas->nama_kelas,
                        'wali_kelas' => $kelas->waliKelas->name ?? '-',
                        'jumlah_siswa' => $kelas->siswas_count,
                        'sudah_diabsen' => $kelasSudahDiabsenIds->has($kelas->id),
                    ];
                });

            $checklistOnboarding = (new OnboardingChecklistService)->status($user->role);

            return view('dashboard.admin', compact(
                'totalSiswa', 'totalGuru', 'totalKelas', 'rekapHariIni', 'siswaAlfaHariIni', 'jurnalHariIni', 'jadwalHariIni', 'rekapPerKelas', 'tahunAjaran', 'checklistOnboarding'
            ));
        }

        // Kurikulum: monitoring jurnal & absensi seluruh guru
        if ($user->role === 'kurikulum') {
            $jurnalHariIni = JurnalMengajar::with(['guru', 'kelas', 'mapel'])
                ->whereDate('tanggal', now()->toDateString())
                ->latest()
                ->take(10)
                ->get();

            $totalJadwalHariIni = $tahunAjaran
                ? JadwalPelajaran::where('tahun_ajaran_id', $tahunAjaran->id)->where('hari', $this->hariIndonesia())->count()
                : 0;
            $totalJurnalHariIni = JurnalMengajar::whereDate('tanggal', now()->toDateString())->count();
            $totalGuru = User::where('role', 'guru')->count();
            $totalMappingKelas = $tahunAjaran ? GuruMengajarKelas::where('tahun_ajaran_id', $tahunAjaran->id)->count() : 0;
            $siswaAlfaHariIni = AbsensiSiswa::siswaAlfaHariIni();

            $checklistOnboarding = (new OnboardingChecklistService)->status($user->role);

            return view('dashboard.kurikulum', compact(
                'jurnalHariIni', 'totalJadwalHariIni', 'totalJurnalHariIni', 'totalGuru', 'totalMappingKelas', 'siswaAlfaHariIni', 'tahunAjaran', 'checklistOnboarding'
            ));
        }

        // Kesiswaan: view-only, monitoring kehadiran siswa se-sekolah
        // (bukan per-mapping seperti Guru BK) + pantauan pelanggaran yang
        // datanya berasal dari inputan Guru BK (lihat modul BK, akses
        // view-only diatur lewat middleware role di routes).
        if ($user->role === 'kesiswaan') {
            $totalSiswa = Siswa::where('is_active', true)->count();
            $siswaAlfaHariIni = AbsensiSiswa::siswaAlfaHariIni();

            // PERBAIKAN PERFORMA (N+1) — 2 query per kelas (total siswa +
            // alfa hari ini) dijadikan 1 query GROUP BY masing-masing,
            // dipakai bersama untuk semua kelas sekaligus.
            $alfaPerKelasHariIni = AbsensiSiswa::whereDate('tanggal', now()->toDateString())
                ->where('status', 'Alfa')
                ->selectRaw('kelas_id, COUNT(DISTINCT siswa_id) as jumlah')
                ->groupBy('kelas_id')->pluck('jumlah', 'kelas_id');

            $rekapPerKelas = Kelas::aktif()
                ->withCount(['siswas' => fn ($q) => $q->where('is_active', true)])
                ->orderBy('nama_kelas')->get()
                ->map(fn ($kelas) => [
                    'kelas' => $kelas,
                    'total_siswa' => $kelas->siswas_count,
                    'alfa_hari_ini' => (int) ($alfaPerKelasHariIni[$kelas->id] ?? 0),
                ]);

            return view('dashboard.kesiswaan', compact('totalSiswa', 'siswaAlfaHariIni', 'rekapPerKelas', 'tahunAjaran'));
        }

        // Guru BK: monitoring absensi lintas kelas sesuai mapping-nya
        if ($user->role === 'guru_bk') {
            $kelasBk = $user->kelasBk();
            $kelasBkIds = $kelasBk->pluck('id');

            $siswaAlfaHariIni = collect();
            $rekapPerKelasBk = collect();

            if ($kelasBkIds->isNotEmpty()) {
                // Ambil siswa Alfa hari ini untuk SEMUA kelas mapping sekaligus,
                // lalu di-filter ke kelas yang relevan (siswaAlfaHariIni tanpa
                // parameter = se-sekolah, jadi difilter manual di sini).
                $siswaAlfaHariIni = AbsensiSiswa::siswaAlfaHariIni()
                    ->filter(fn ($a) => $kelasBkIds->contains($a['kelas']?->id))
                    ->values();

                // PERBAIKAN PERFORMA (N+1) — sama seperti Kesiswaan di atas:
                // 2 query per kelas dijadikan 1 query GROUP BY untuk semua
                // kelas mapping guru BK ini sekaligus.
                $totalSiswaPerKelas = Kelas::whereIn('id', $kelasBkIds)
                    ->withCount(['siswas' => fn ($q) => $q->where('is_active', true)])
                    ->get()->pluck('siswas_count', 'id');
                $alfaPerKelasHariIni = AbsensiSiswa::whereIn('kelas_id', $kelasBkIds)
                    ->whereDate('tanggal', now()->toDateString())
                    ->where('status', 'Alfa')
                    ->selectRaw('kelas_id, COUNT(DISTINCT siswa_id) as jumlah')
                    ->groupBy('kelas_id')->pluck('jumlah', 'kelas_id');

                $rekapPerKelasBk = $kelasBk->map(fn ($kelas) => [
                    'kelas' => $kelas,
                    'total_siswa' => (int) ($totalSiswaPerKelas[$kelas->id] ?? 0),
                    'alfa_hari_ini' => (int) ($alfaPerKelasHariIni[$kelas->id] ?? 0),
                ]);
            }

            return view('dashboard.guru-bk', compact('kelasBk', 'siswaAlfaHariIni', 'rekapPerKelasBk', 'tahunAjaran'));
        }

        // Guru (termasuk Wali Kelas)
        $jadwalHariIniMentah = $tahunAjaran
            ? JadwalPelajaran::with(['kelas', 'mapel', 'jamPelajaran'])
                ->where('guru_id', $user->id)
                ->where('tahun_ajaran_id', $tahunAjaran->id)
                ->where('hari', $this->hariIndonesia())
                ->get()
            : collect();

        // Dikelompokkan jadi sesi (jam berurutan, kelas & mapel sama = 1 kartu)
        // supaya konsisten dengan halaman "Absensi & Jurnal Mengajar".
        $jadwalHariIni = SesiMengajarGrouper::kelompokkan($jadwalHariIniMentah);

        // Tandai sesi yang sudah diisi jurnalnya hari ini — pakai helper yang
        // SAMA dengan halaman Absensi & Jurnal Mengajar, supaya status
        // "Terisi" di dashboard selalu konsisten (bukan logic duplikat).
        $jadwalHariIni = SesiMengajarGrouper::tandaiSudahDiisi($jadwalHariIni, $jadwalHariIniMentah);

        $jurnalTerakhir = JurnalMengajar::with(['kelas', 'mapel'])
            ->where('guru_id', $user->id)
            ->latest('tanggal')
            ->take(5)
            ->get();

        $kelasWali = $user->kelasWali;
        $siswaAlfaHariIni = $kelasWali ? AbsensiSiswa::siswaAlfaHariIni($kelasWali->id) : collect();

        return view('dashboard.guru', compact('jadwalHariIni', 'jurnalTerakhir', 'kelasWali', 'siswaAlfaHariIni', 'tahunAjaran'));
    }

    private function hariIndonesia(): string
    {
        $map = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'];
        return $map[now()->dayOfWeek] ?? 'Senin';
    }
}
