<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSiswa;
use App\Models\KasusSiswa;
use App\Models\PemanggilanOrangTua;
use App\Models\PembinaanSiswa;
use App\Models\Siswa;
use App\Services\PoinSiswaService;
use Illuminate\Http\Request;

/**
 * Portal khusus akun Orang Tua/Wali Siswa (role: orang_tua).
 * Read-only: hanya menampilkan Absensi & Pelanggaran anak yang ditautkan
 * ke akun tersebut (lihat User::anakAsuh / tabel orang_tua_siswa).
 * Tidak ada aksi lapor/edit apa pun di sini — itu tetap domain guru/BK.
 */
class OrangTuaController extends Controller
{
    /** Daftar anak yang ditautkan ke akun ini. Kalau cuma 1 anak, langsung ke profilnya. */
    public function index(Request $request)
    {
        $user = $request->user();
        $anakList = $user->anakAsuh()->with('kelas')->orderBy('nama')->get();

        if ($anakList->isEmpty()) {
            return view('ortu.index', ['anakList' => $anakList, 'ringkasanPerAnak' => collect()]);
        }

        if ($anakList->count() === 1) {
            return redirect()->route('ortu.show', $anakList->first());
        }

        $poinService = app(PoinSiswaService::class);
        $ringkasanPerAnak = $anakList->mapWithKeys(function ($anak) use ($poinService) {
            $bulan = now()->month;
            $tahun = now()->year;
            $absenBulanIni = AbsensiSiswa::where('siswa_id', $anak->id)
                ->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)
                ->with(['jurnal.jamPelajaran', 'jurnal.jamPelajaranAkhir'])
                ->get();
            $final = AbsensiSiswa::finalPerHari($absenBulanIni);

            return [$anak->id => [
                'sakit' => $final->where('status', 'Sakit')->count(),
                'izin' => $final->where('status', 'Izin')->count(),
                'alfa' => $final->where('status', 'Alfa')->count(),
                'poin_aktif' => $poinService->poinAktif($anak),
            ]];
        });

        return view('ortu.index', compact('anakList', 'ringkasanPerAnak'));
    }

    /** Profil 1 anak: rekap absensi bulanan + riwayat pelanggaran/pembinaan. */
    public function show(Request $request, Siswa $siswa, PoinSiswaService $poinService)
    {
        $user = $request->user();
        abort_unless($user->bisaAksesAnak($siswa), 403, 'Anda tidak memiliki akses ke data siswa ini.');

        $anakList = $user->anakAsuh()->orderBy('nama')->get();

        // ==== Rekap Absensi Bulanan ====
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);
        $jumlahHari = \Carbon\Carbon::create($tahun, $bulan, 1)->daysInMonth;

        $absensiRaw = AbsensiSiswa::where('siswa_id', $siswa->id)
            ->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)
            ->with(['jurnal.jamPelajaran', 'jurnal.jamPelajaranAkhir', 'jurnal.mapel'])
            ->get();

        $harian = array_fill(1, $jumlahHari, null);
        $sakit = $izin = $alfa = $hadir = 0;

        foreach (AbsensiSiswa::finalPerHari($absensiRaw) as $final) {
            $tgl = (int) $final->tanggal->format('j');
            $mapelNama = $final->jurnal?->mapel?->nama_mapel ?? '-';
            $harian[$tgl] = ['status' => $final->status, 'keterangan' => $final->keterangan, 'mapel' => $mapelNama];

            if ($final->status === 'Sakit') $sakit++;
            if ($final->status === 'Izin') $izin++;
            if ($final->status === 'Alfa') $alfa++;
        }
        $hadir = collect($harian)->filter()->count();

        // ==== Riwayat Pelanggaran & Pembinaan (ringkas, read-only) ====
        $ringkasan = $poinService->ringkasan($siswa);

        $kasus = KasusSiswa::with(['jenisPelanggaran'])
            ->where('siswa_id', $siswa->id)->aktif()->orderByDesc('tanggal_kejadian')->get();
        $pembinaan = PembinaanSiswa::with('petugas')->where('siswa_id', $siswa->id)->orderByDesc('tanggal')->get();
        $pemanggilan = PemanggilanOrangTua::with('petugas')->where('siswa_id', $siswa->id)->orderByDesc('tanggal')->get();

        $timeline = collect()
            ->concat($kasus->map(fn ($k) => ['tanggal' => $k->tanggal_kejadian, 'jenis' => 'kasus', 'data' => $k]))
            ->concat($pembinaan->map(fn ($p) => ['tanggal' => $p->tanggal, 'jenis' => 'pembinaan', 'data' => $p]))
            ->concat($pemanggilan->map(fn ($p) => ['tanggal' => $p->tanggal, 'jenis' => 'pemanggilan', 'data' => $p]))
            ->sortByDesc(fn ($item) => $item['tanggal']->format('Y-m-d') . '-' . $item['data']->id)
            ->values();

        return view('ortu.show', compact(
            'siswa', 'anakList', 'bulan', 'tahun', 'jumlahHari', 'harian',
            'sakit', 'izin', 'alfa', 'hadir', 'ringkasan', 'timeline'
        ));
    }
}
