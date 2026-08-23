<?php

namespace App\Http\Controllers;

use App\Models\AbsensiEkskulPeserta;
use App\Models\Ekstrakurikuler;
use App\Models\EkstrakurikulerPembina;
use App\Models\Siswa;
use App\Support\RentangBulan;
use Illuminate\Http\Request;

/**
 * Rekap absensi bulanan 1 kegiatan ekstrakurikuler — siswa x tanggal 1-31,
 * pola sama seperti WaliKelasController::absensiBulanan (rekap kelas).
 * Bisa dilihat Kesiswaan/Admin (semua kegiatan) atau pembina internal
 * kegiatan itu sendiri (guru/guru_bk yang membina kegiatan ini).
 *
 * (2026-08-23, revisi) — kehadiran PEMBINA juga direkap (dulu cuma siswa),
 * di tabel terpisah dengan format harian yang sama (S/I/A per tanggal).
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
        $rentang = RentangBulan::dari($tahun, $bulan);

        $rekap = $this->rekapSiswa($ekstrakurikuler, $tahun, $bulan, $rentang, $jumlahHari);
        $rekapPembina = $this->rekapPembina($ekstrakurikuler, $rentang, $jumlahHari);

        return view('ekstrakurikuler.rekap-bulanan', compact('ekstrakurikuler', 'rekap', 'rekapPembina', 'bulan', 'tahun', 'jumlahHari'));
    }

    private function rekapSiswa(Ekstrakurikuler $ekstrakurikuler, int $tahun, int $bulan, array $rentang, int $jumlahHari)
    {
        $absensiRaw = AbsensiEkskulPeserta::whereNotNull('siswa_id')
            ->whereHas('absensiEkskul', function ($q) use ($ekstrakurikuler, $rentang) {
                $q->where('ekstrakurikuler_id', $ekstrakurikuler->id)
                  ->whereBetween('tanggal', $rentang);
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
        $siswas = Siswa::with('kelas')->whereIn('id', $idSiswaSekarang->merge($idSiswaHistoris)->unique())
            ->orderBy('nama')
            ->get();

        return $siswas->map(function ($siswa) use ($absensiRaw, $jumlahHari) {
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
                'nama' => $siswa->nama,
                'nis' => $siswa->nis,
                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                'harian' => $data,
                'sakit' => $sakit, 'izin' => $izin, 'alfa' => $alfa,
                'jumlah' => $sakit + $izin + $alfa,
            ];
        });
    }

    /**
     * Sama seperti rekapSiswa(), tapi untuk PEMBINA (internal maupun
     * eksternal — keduanya dicatat di tabel `ekstrakurikuler_pembinas`,
     * lihat App\Models\EkstrakurikulerPembina). "NIS" diganti label jenis
     * (Sekolah/Luar Sekolah) karena pembina tidak punya NIS.
     */
    private function rekapPembina(Ekstrakurikuler $ekstrakurikuler, array $rentang, int $jumlahHari)
    {
        $absensiRaw = AbsensiEkskulPeserta::whereNotNull('ekstrakurikuler_pembina_id')
            ->whereHas('absensiEkskul', function ($q) use ($ekstrakurikuler, $rentang) {
                $q->where('ekstrakurikuler_id', $ekstrakurikuler->id)
                  ->whereBetween('tanggal', $rentang);
            })
            ->with('absensiEkskul')
            ->get()
            ->groupBy('ekstrakurikuler_pembina_id');

        // Sama prinsipnya dengan siswa: gabungan pembina SEKARANG + yang
        // PERNAH tercatat absen bulan ini (kalau sempat dihapus dari daftar
        // pembina setelah tercatat absen).
        $idPembinaSekarang = $ekstrakurikuler->pembinas()->pluck('id');
        $idPembinaHistoris = $absensiRaw->keys();
        $pembinas = EkstrakurikulerPembina::with('user')
            ->whereIn('id', $idPembinaSekarang->merge($idPembinaHistoris)->unique())
            ->get()
            ->sortBy(fn ($p) => $p->namaTampil())
            ->values();

        return $pembinas->map(function ($pembina) use ($absensiRaw, $jumlahHari) {
            $data = array_fill(1, $jumlahHari, '');
            $sakit = $izin = $alfa = 0;

            foreach ($absensiRaw->get($pembina->id, collect()) as $r) {
                $tgl = (int) $r->absensiEkskul->tanggal->format('j');
                $data[$tgl] = match ($r->status) {
                    'Sakit' => 'S', 'Izin' => 'I', 'Alfa' => 'A', default => '.',
                };
                if ($r->status === 'Sakit') $sakit++;
                if ($r->status === 'Izin') $izin++;
                if ($r->status === 'Alfa') $alfa++;
            }

            return [
                'nama' => $pembina->namaTampil(),
                'jenis' => $pembina->isEksternal() ? 'Luar Sekolah' : 'Sekolah',
                'harian' => $data,
                'sakit' => $sakit, 'izin' => $izin, 'alfa' => $alfa,
                'jumlah' => $sakit + $izin + $alfa,
            ];
        });
    }
}
