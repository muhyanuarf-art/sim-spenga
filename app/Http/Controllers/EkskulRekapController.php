<?php

namespace App\Http\Controllers;

use App\Models\AbsensiEkskulPeserta;
use App\Models\Ekstrakurikuler;
use App\Support\RentangBulan;
use Illuminate\Http\Request;

/**
 * Rekap absensi bulanan 1 kegiatan ekstrakurikuler — siswa x tanggal 1-31,
 * pola sama seperti WaliKelasController::absensiBulanan (rekap kelas).
 * Bisa dilihat Kesiswaan/Admin (semua kegiatan) atau pembina internal
 * kegiatan itu sendiri (guru/guru_bk yang membina kegiatan ini).
 */
class EkskulRekapController extends Controller
{
    public function bulanan(Request $request, Ekstrakurikuler $ekstrakurikuler)
    {
        $user = $request->user();
        if (!in_array($user->role, ['kesiswaan', 'admin']) && !$ekstrakurikuler->isPembinaInternal($user->id)) {
            abort(403, 'Anda bukan pembina kegiatan ini.');
        }

        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);
        $jumlahHari = \Carbon\Carbon::create($tahun, $bulan, 1)->daysInMonth;

        $absensiRaw = AbsensiEkskulPeserta::whereNotNull('siswa_id')
            ->whereHas('absensiEkskul', function ($q) use ($ekstrakurikuler, $tahun, $bulan) {
                $q->where('ekstrakurikuler_id', $ekstrakurikuler->id)
                  ->whereBetween('tanggal', RentangBulan::dari($tahun, $bulan));
            })
            ->with('absensiEkskul')
            ->get()
            ->groupBy('siswa_id');

        // Siswa yang direkap: gabungan anggota SEKARANG + siapa saja yang
        // PERNAH tercatat absen di kegiatan ini bulan tsb (siswa bisa saja
        // sudah dikeluarkan dari anggota tapi datanya tetap harus muncul
        // untuk bulan saat dia masih ikut — sama prinsipnya dengan rekap
        // absensi kelas).
        $idSiswaSekarang = $ekstrakurikuler->anggotas()->pluck('siswa_id');
        $idSiswaHistoris = $absensiRaw->keys();
        $siswas = \App\Models\Siswa::whereIn('id', $idSiswaSekarang->merge($idSiswaHistoris)->unique())
            ->orderBy('nama')
            ->get();

        $rekap = $siswas->map(function ($siswa) use ($absensiRaw, $jumlahHari) {
            $data = array_fill(1, $jumlahHari, '');
            $sakit = $izin = $alfa = 0;

            foreach ($absensiRaw->get($siswa->id, collect()) as $r) {
                $tgl = (int) $r->absensiEkskul->tanggal->format('j');
                $data[$tgl] = match ($r->status) {
                    'Sakit' => 'S', 'Izin' => 'I', 'Alfa' => 'A', default => '.',
                };
                if ($r->status === 'Sakit') $sakit++;
                if ($r->status === 'Izin') $izin++;
                if ($r->status === 'Alfa') $alfa++;
            }

            return [
                'siswa' => $siswa,
                'harian' => $data,
                'sakit' => $sakit, 'izin' => $izin, 'alfa' => $alfa,
                'jumlah' => $sakit + $izin + $alfa,
            ];
        });

        return view('ekstrakurikuler.rekap-bulanan', compact('ekstrakurikuler', 'rekap', 'bulan', 'tahun', 'jumlahHari'));
    }
}
