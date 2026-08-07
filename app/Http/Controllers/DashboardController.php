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

            $rekapHariIni = AbsensiSiswa::whereDate('tanggal', now()->toDateString())
                ->selectRaw('status, count(*) as jumlah')
                ->groupBy('status')
                ->pluck('jumlah', 'status');

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
                'totalSiswa', 'totalGuru', 'totalKelas', 'rekapHariIni', 'jurnalHariIni', 'jadwalHariIni', 'rekapPerKelas', 'tahunAjaran'
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

            return view('dashboard.kurikulum', compact(
                'jurnalHariIni', 'totalJadwalHariIni', 'totalJurnalHariIni', 'totalGuru', 'totalMappingKelas', 'tahunAjaran'
            ));
        }

        // Guru (termasuk Wali Kelas)
        $jadwalHariIni = $tahunAjaran
            ? JadwalPelajaran::with(['kelas', 'mapel', 'jamPelajaran'])
                ->where('guru_id', $user->id)
                ->where('tahun_ajaran_id', $tahunAjaran->id)
                ->where('hari', $this->hariIndonesia())
                ->orderBy('jam_pelajaran_id')
                ->get()
            : collect();

        $jurnalTerakhir = JurnalMengajar::with(['kelas', 'mapel'])
            ->where('guru_id', $user->id)
            ->latest('tanggal')
            ->take(5)
            ->get();

        $kelasWali = $user->kelasWali;

        return view('dashboard.guru', compact('jadwalHariIni', 'jurnalTerakhir', 'kelasWali', 'tahunAjaran'));
    }

    private function hariIndonesia(): string
    {
        $map = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'];
        return $map[now()->dayOfWeek] ?? 'Senin';
    }
}
