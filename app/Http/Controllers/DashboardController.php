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
use App\Support\SesiMengajarGrouper;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tahunAjaran = TahunAjaran::aktif();
        $today = now()->translatedFormat('l'); // nama hari

        // Admin & Kepala Sekolah: ringkasan sekolah menyeluruh
        if ($user->role === 'admin' || $user->role === 'kepala_sekolah') {
            $totalSiswa = Siswa::where('is_active', true)->count();
            $totalGuru = User::where('role', 'guru')->count();
            $totalKelas = Kelas::count();

            $absensiHariIniRaw = AbsensiSiswa::whereDate('tanggal', now()->toDateString())
                ->with(['jurnal.jamPelajaran', 'jurnal.jamPelajaranAkhir'])
                ->get()
                ->groupBy('siswa_id');

            // Rekap dihitung dari status FINAL per siswa per hari (jam paling
            // akhir), bukan dari mentah semua record mapel, supaya 1 siswa
            // tidak terhitung berkali-kali kalau diabsen lebih dari 1 mapel.
            $rekapHariIni = $absensiHariIniRaw
                ->map(fn ($recordsSiswa) => AbsensiSiswa::finalPerHari($recordsSiswa)->first())
                ->groupBy('status')
                ->map->count();

            $siswaAlfaHariIni = AbsensiSiswa::siswaAlfaHariIni();

            $jurnalHariIni = JurnalMengajar::whereDate('tanggal', now()->toDateString())->count();
            $jadwalHariIni = $tahunAjaran
                ? JadwalPelajaran::where('tahun_ajaran_id', $tahunAjaran->id)->where('hari', $this->hariIndonesia())->count()
                : 0;

            $rekapPerKelas = Kelas::withCount(['siswas' => fn ($q) => $q->where('is_active', true)])
                ->orderBy('nama_kelas')
                ->get()
                ->map(function ($kelas) {
                    $hadirHariIni = AbsensiSiswa::where('kelas_id', $kelas->id)
                        ->whereDate('tanggal', now()->toDateString())
                        ->count();
                    return [
                        'kelas' => $kelas->nama_kelas,
                        'wali_kelas' => $kelas->waliKelas->name ?? '-',
                        'jumlah_siswa' => $kelas->siswas_count,
                        'sudah_diabsen' => $hadirHariIni > 0,
                    ];
                });

            return view('dashboard.admin', compact(
                'totalSiswa', 'totalGuru', 'totalKelas', 'rekapHariIni', 'siswaAlfaHariIni', 'jurnalHariIni', 'jadwalHariIni', 'rekapPerKelas', 'tahunAjaran'
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

            return view('dashboard.kurikulum', compact(
                'jurnalHariIni', 'totalJadwalHariIni', 'totalJurnalHariIni', 'totalGuru', 'totalMappingKelas', 'siswaAlfaHariIni', 'tahunAjaran'
            ));
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

                $rekapPerKelasBk = $kelasBk->map(function ($kelas) {
                    $totalSiswa = $kelas->siswas()->where('is_active', true)->count();
                    $alfaHariIni = AbsensiSiswa::where('kelas_id', $kelas->id)
                        ->whereDate('tanggal', now()->toDateString())
                        ->where('status', 'Alfa')
                        ->distinct('siswa_id')
                        ->count('siswa_id');
                    return [
                        'kelas' => $kelas,
                        'total_siswa' => $totalSiswa,
                        'alfa_hari_ini' => $alfaHariIni,
                    ];
                });
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
