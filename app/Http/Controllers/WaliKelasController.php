<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSiswa;
use App\Models\Kelas;
use App\Models\JurnalMengajar;
use Illuminate\Http\Request;

class WaliKelasController extends Controller
{
    /**
     * Rekap absensi bulanan 1 lembar: NIS, Nama, Tanggal 1-31, Sakit, Izin, Alfa, Jumlah.
     * Bisa dipilih bulan berapapun sepanjang tahun ajaran berjalan.
     */
    public function absensiBulanan(Request $request, ?Kelas $kelas = null)
    {
        $user = $request->user();
        $kelas = $kelas ?? $this->resolveKelasWali($user);

        if ($user->role === 'admin' || $user->role === 'kurikulum' || $user->role === 'kepala_sekolah') {
            // boleh pilih kelas manapun
            $kelasId = $request->get('kelas_id', $kelas?->id);
            $kelas = Kelas::findOrFail($kelasId);
        } else {
            $this->authorizeWali($user, $kelas);
        }

        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);
        $jumlahHari = \Carbon\Carbon::create($tahun, $bulan, 1)->daysInMonth;

        $siswas = $kelas->siswas()->where('is_active', true)->orderBy('nama')->get();

        $absensiRaw = AbsensiSiswa::where('kelas_id', $kelas->id)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->get()
            ->groupBy('siswa_id');

        $rekap = $siswas->map(function ($siswa) use ($absensiRaw, $jumlahHari) {
            $data = array_fill(1, $jumlahHari, '');
            $sakit = $izin = $alfa = 0;

            $records = $absensiRaw->get($siswa->id, collect());
            foreach ($records as $r) {
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
            }

            return [
                'siswa' => $siswa,
                'harian' => $data,
                'sakit' => $sakit,
                'izin' => $izin,
                'alfa' => $alfa,
                'jumlah' => $sakit + $izin + $alfa,
            ];
        });

        $daftarKelas = Kelas::orderBy('nama_kelas')->get();

        return view('walikelas.absensi-bulanan', compact('kelas', 'rekap', 'bulan', 'tahun', 'jumlahHari', 'daftarKelas'));
    }

    /**
     * Monitoring Jurnal Mengajar untuk kelas walinya.
     */
    public function jurnalKelas(Request $request, ?Kelas $kelas = null)
    {
        $user = $request->user();
        $kelas = $kelas ?? $this->resolveKelasWali($user);

        if ($user->role === 'admin' || $user->role === 'kurikulum' || $user->role === 'kepala_sekolah') {
            $kelasId = $request->get('kelas_id', $kelas?->id);
            $kelas = Kelas::findOrFail($kelasId);
        } else {
            $this->authorizeWali($user, $kelas);
        }

        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $jurnal = JurnalMengajar::with(['guru', 'mapel', 'jamPelajaran'])
            ->where('kelas_id', $kelas->id)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->orderBy('tanggal')
            ->orderBy('jam_pelajaran_id')
            ->get();

        $daftarKelas = Kelas::orderBy('nama_kelas')->get();

        return view('walikelas.jurnal-kelas', compact('kelas', 'jurnal', 'bulan', 'tahun', 'daftarKelas'));
    }

    private function resolveKelasWali($user): ?Kelas
    {
        return Kelas::where('wali_kelas_id', $user->id)->first();
    }

    private function authorizeWali($user, ?Kelas $kelas): void
    {
        if (! $kelas || $kelas->wali_kelas_id !== $user->id) {
            abort(403, 'Anda bukan Wali Kelas untuk kelas ini.');
        }
    }
}
