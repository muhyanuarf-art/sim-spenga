<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSiswa;
use App\Support\RentangBulan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class OrangTuaDashboardController extends Controller
{
    public function index(Request $request)
    {
        $orangTua = Auth::guard('orangtua')->user();
        $siswa = $orangTua->siswa()->with('kelas')->firstOrFail();

        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        [$awalBulan, $akhirBulan] = RentangBulan::dari($tahun, $bulan);
        $absensiRaw = AbsensiSiswa::where('siswa_id', $siswa->id)
            ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
            ->with(['jurnal.jamPelajaran', 'jurnal.jamPelajaranAkhir', 'jurnal.mapel'])
            ->get();

        $rekapHarian = AbsensiSiswa::finalPerHari($absensiRaw)
            ->sortKeys()
            ->map(fn ($r) => [
                'tanggal' => $r->tanggal->translatedFormat('d M Y'),
                'status' => $r->status,
                'keterangan' => $r->keterangan,
                'mapel' => $r->jurnal?->mapel?->nama_mapel,
            ])
            ->values();

        $ringkasan = [
            'hadir' => $rekapHarian->where('status', 'Hadir')->count(),
            'sakit' => $rekapHarian->where('status', 'Sakit')->count(),
            'izin' => $rekapHarian->where('status', 'Izin')->count(),
            'alfa' => $rekapHarian->where('status', 'Alfa')->count(),
        ];

        $kasusBk = $siswa->kasusBk()->aktif()->latest('tanggal_kejadian')->get();
        $poinTerpakai = $kasusBk->sum('poin');
        $penguranganPoin = $siswa->penguranganPoinBk()->aktif()->sum('jumlah');
        $poinBersih = max(0, $poinTerpakai - $penguranganPoin);

        // (Revisi permintaan admin) — fitur Kenaikan Kelas dihapus, sekolah
        // memindahkan siswa antar kelas lewat Import Excel. Supaya orang tua
        // tetap bisa melihat riwayat kelas anaknya (naik kelas/pindah kelas
        // dari tahun ke tahun), tampilkan Riwayat Kelas di dashboard ini.
        $riwayatKelas = $siswa->riwayatKelas()->with(['tahunAjaran', 'kelasAsal', 'kelas'])->get();

        return view('orangtua.dashboard', compact(
            'siswa', 'rekapHarian', 'ringkasan', 'kasusBk', 'poinBersih', 'bulan', 'tahun', 'riwayatKelas'
        ));
    }

    public function gantiPasswordForm()
    {
        return view('orangtua.ganti-password');
    }

    public function gantiPassword(Request $request)
    {
        $request->validate([
            'password_lama' => ['required', 'string'],
            'password_baru' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $orangTua = Auth::guard('orangtua')->user();

        if (! Hash::check($request->password_lama, $orangTua->password)) {
            return back()->withErrors(['password_lama' => 'Password lama salah.']);
        }

        $orangTua->update([
            'password' => $request->password_baru,
            'password_diubah_at' => now(),
        ]);

        return back()->with('success', 'Password berhasil diganti.');
    }
}
