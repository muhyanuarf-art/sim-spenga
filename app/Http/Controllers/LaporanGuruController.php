<?php

namespace App\Http\Controllers;

use App\Support\KonteksPeriode;
use App\Models\AbsensiSiswa;
use App\Models\GuruMengajarKelas;
use App\Models\JadwalPelajaran;
use App\Models\JurnalMengajar;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\RentangBulan;
use Illuminate\Http\Request;

class LaporanGuruController extends Controller
{
    /**
     * Jurnal Mengajar Guru Tiap Mapel:
     * Daftar jurnal mengajar seorang guru, difilter per mata pelajaran yang ia ampu.
     * - Guru: hanya bisa melihat datanya sendiri.
     * - Kurikulum/Admin: bebas pilih guru & mapel manapun.
     * - Kepala Sekolah: bebas pilih, khusus lihat (read-only, tidak ada aksi edit/hapus di halaman ini).
     */
    public function jurnalMapel(Request $request)
    {
        $user = $request->user();
        $isGuru = $user->role === 'guru';
        $tahunAjaran = KonteksPeriode::pilihan();

        $guru = $this->resolveGuru($request, $user, $isGuru);
        $guruList = $isGuru ? collect() : User::where('role', 'guru')->orderBy('name')->get();

        $mapelDiampu = $this->mapelDiampuOleh($guru, $tahunAjaran);
        $mapelId = (int) $request->get('mapel_id', $mapelDiampu->first()->id ?? 0);
        if (! $mapelDiampu->contains('id', $mapelId)) {
            $mapelId = $mapelDiampu->first()->id ?? 0;
        }

        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $jurnal = collect();
        $ringkasan = ['pertemuan' => 0, 'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alfa' => 0];

        if ($guru && $mapelId) {
            [$awalBulan, $akhirBulan] = RentangBulan::dari($tahun, $bulan);
            $jurnal = JurnalMengajar::with(['kelas', 'jamPelajaran'])
                ->where('guru_id', $guru->id)
                ->where('mata_pelajaran_id', $mapelId)
                ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
                ->orderBy('tanggal')
                ->orderBy('jam_pelajaran_id')
                ->get();

            $ringkasan = [
                'pertemuan' => $jurnal->count(),
                'hadir' => $jurnal->sum('jumlah_hadir'),
                'sakit' => $jurnal->sum('jumlah_sakit'),
                'izin' => $jurnal->sum('jumlah_izin'),
                'alfa' => $jurnal->sum('jumlah_alfa'),
            ];
        }

        $mapelAktif = $mapelDiampu->firstWhere('id', $mapelId);

        return view('laporan.jurnal-guru', compact(
            'guru', 'guruList', 'isGuru', 'mapelDiampu', 'mapelId', 'mapelAktif',
            'bulan', 'tahun', 'jurnal', 'ringkasan'
        ));
    }

    /**
     * Absensi Guru Tiap Mapel:
     * Rekap kehadiran siswa (format matrix tanggal 1-31) berdasarkan pertemuan
     * mata pelajaran tertentu yang diajarkan oleh guru tertentu.
     */
    public function absensiMapel(Request $request)
    {
        $user = $request->user();
        $isGuru = $user->role === 'guru';
        $tahunAjaran = KonteksPeriode::pilihan();

        $guru = $this->resolveGuru($request, $user, $isGuru);
        $guruList = $isGuru ? collect() : User::where('role', 'guru')->orderBy('name')->get();

        $mapelDiampu = $this->mapelDiampuOleh($guru, $tahunAjaran);
        $mapelId = (int) $request->get('mapel_id', $mapelDiampu->first()->id ?? 0);
        if (! $mapelDiampu->contains('id', $mapelId)) {
            $mapelId = $mapelDiampu->first()->id ?? 0;
        }

        $kelasDiampu = $this->kelasDiampuOleh($guru, $mapelId, $tahunAjaran);
        $kelasId = (int) $request->get('kelas_id', $kelasDiampu->first()->id ?? 0);
        if (! $kelasDiampu->contains('id', $kelasId)) {
            $kelasId = $kelasDiampu->first()->id ?? 0;
        }

        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);
        $jumlahHari = \Carbon\Carbon::create($tahun, $bulan, 1)->daysInMonth;

        $rekap = collect();
        if ($guru && $mapelId && $kelasId) {
            [$awalBulan, $akhirBulan] = RentangBulan::dari($tahun, $bulan);
            $absensiRaw = AbsensiSiswa::where('kelas_id', $kelasId)
                ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
                ->whereHas('jurnal', function ($q) use ($guru, $mapelId) {
                    $q->where('guru_id', $guru->id)->where('mata_pelajaran_id', $mapelId);
                })
                ->get()
                ->groupBy('siswa_id');

            // (2026-08-23) — PERBAIKAN BUG sama seperti di WaliKelasController
            // ::absensiBulanan(): absensi_siswas.kelas_id adalah SNAPSHOT
            // kelas SAAT sesi mengajar itu terjadi, bukan mengikuti kelas
            // siswa sekarang. Sebelumnya daftar siswa di sini diambil dari
            // Siswa::where('kelas_id', $kelasId) (kelas siswa SAAT INI),
            // sehingga siswa yang sudah pindah kelas hilang dari laporan
            // bulan-bulan sebelum ia pindah. Gabungkan siapa saja yang
            // PERNAH tercatat (dari $absensiRaw) dengan siswa yang SEKARANG
            // terdaftar di kelas ini (supaya kelas tetap tampil lengkap
            // untuk bulan berjalan sebelum ada absensi diinput).
            $siswaIdHistoris = $absensiRaw->keys();
            $siswaIdSekarang = Siswa::diKelas($kelasId)->where('is_active', true)->pluck('id');
            $siswas = Siswa::whereIn('id', $siswaIdHistoris->merge($siswaIdSekarang)->unique())
                ->orderBy('nama')
                ->get();

            $rekap = $siswas->map(function ($siswa) use ($absensiRaw, $jumlahHari) {
                $data = array_fill(1, $jumlahHari, '');
                $hadir = $sakit = $izin = $alfa = 0;

                foreach ($absensiRaw->get($siswa->id, collect()) as $r) {
                    $tgl = (int) $r->tanggal->format('j');
                    $kode = match ($r->status) {
                        'Sakit' => 'S',
                        'Izin' => 'I',
                        'Alfa' => 'A',
                        default => '.',
                    };
                    $data[$tgl] = $kode;
                    if ($r->status === 'Sakit') $sakit++;
                    if ($r->status === 'Izin') $izin++;
                    if ($r->status === 'Alfa') $alfa++;
                    if ($r->status === 'Hadir') $hadir++;
                }

                return [
                    'siswa' => $siswa,
                    'harian' => $data,
                    'hadir' => $hadir,
                    'sakit' => $sakit,
                    'izin' => $izin,
                    'alfa' => $alfa,
                    'jumlah' => $sakit + $izin + $alfa,
                ];
            });
        }

        $mapelAktif = $mapelDiampu->firstWhere('id', $mapelId);
        $kelasAktif = $kelasDiampu->firstWhere('id', $kelasId);

        return view('laporan.absensi-guru', compact(
            'guru', 'guruList', 'isGuru', 'mapelDiampu', 'mapelId', 'mapelAktif',
            'kelasDiampu', 'kelasId', 'kelasAktif', 'bulan', 'tahun', 'jumlahHari', 'rekap'
        ));
    }

    private function resolveGuru(Request $request, User $user, bool $isGuru): ?User
    {
        if ($isGuru) {
            return $user;
        }

        $guruId = $request->get('guru_id');
        if ($guruId) {
            return User::where('role', 'guru')->find($guruId);
        }

        return User::where('role', 'guru')->orderBy('name')->first();
    }

    private function mapelDiampuOleh(?User $guru, ?TahunAjaran $tahunAjaran)
    {
        if (! $guru) {
            return collect();
        }

        // Gabungkan 3 sumber data agar tidak bergantung pada satu tempat input saja:
        // 1) mapping manual "Guru Mengajar Kelas" (menu Kurikulum)
        // 2) jadwal pelajaran yang sudah disusun (menu Jadwal Pelajaran)
        // 3) jurnal yang sudah benar-benar pernah diisi guru (data riil, jaring pengaman)
        $dariMapping = GuruMengajarKelas::where('guru_id', $guru->id)
            ->when($tahunAjaran, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
            ->pluck('mata_pelajaran_id');

        $dariJadwal = JadwalPelajaran::where('guru_id', $guru->id)
            ->when($tahunAjaran, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
            ->pluck('mata_pelajaran_id');

        $dariJurnal = JurnalMengajar::where('guru_id', $guru->id)->pluck('mata_pelajaran_id');

        $mapelIds = $dariMapping->merge($dariJadwal)->merge($dariJurnal)->unique();

        return MataPelajaran::whereIn('id', $mapelIds)->orderBy('nama_mapel')->get();
    }

    private function kelasDiampuOleh(?User $guru, ?int $mapelId, ?TahunAjaran $tahunAjaran)
    {
        if (! $guru || ! $mapelId) {
            return collect();
        }

        $dariMapping = GuruMengajarKelas::where('guru_id', $guru->id)
            ->where('mata_pelajaran_id', $mapelId)
            ->when($tahunAjaran, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
            ->pluck('kelas_id');

        $dariJadwal = JadwalPelajaran::where('guru_id', $guru->id)
            ->where('mata_pelajaran_id', $mapelId)
            ->when($tahunAjaran, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
            ->pluck('kelas_id');

        $dariJurnal = JurnalMengajar::where('guru_id', $guru->id)
            ->where('mata_pelajaran_id', $mapelId)
            ->pluck('kelas_id');

        $kelasIds = $dariMapping->merge($dariJadwal)->merge($dariJurnal)->unique();

        return Kelas::whereIn('id', $kelasIds)->orderBy('nama_kelas')->get();
    }
}
