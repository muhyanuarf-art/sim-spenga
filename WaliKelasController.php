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
            $kelas = $this->resolveKelasTerpilih($request, $kelas);
            if (! $kelas) {
                return redirect()->route('dashboard')
                    ->with('error', 'Belum ada data kelas. Silakan tambahkan kelas terlebih dahulu di menu Data Kelas.');
            }
        } else {
            $this->authorizeWali($user, $kelas);
        }

        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);
        $jumlahHari = \Carbon\Carbon::create($tahun, $bulan, 1)->daysInMonth;

        $siswas = $kelas->siswas()->where('is_active', true)->orderBy('nama')->get();

        // Eager-load info jam (awal & akhir sesi) tiap jurnal, supaya bisa
        // menentukan "sesi mana yang jam-nya paling akhir pada hari itu"
        // tanpa query tambahan per baris.
        $absensiRaw = AbsensiSiswa::where('kelas_id', $kelas->id)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->with(['jurnal.jamPelajaran', 'jurnal.jamPelajaranAkhir', 'jurnal.mapel'])
            ->get()
            ->groupBy('siswa_id');

        $rekap = $siswas->map(function ($siswa) use ($absensiRaw, $jumlahHari) {
            $data = array_fill(1, $jumlahHari, '');
            $keterangan = array_fill(1, $jumlahHari, '');
            $sakit = $izin = $alfa = 0;

            $records = $absensiRaw->get($siswa->id, collect());

            // Absensi Kelas mengikuti aturan: kalau siswa tercatat di lebih dari
            // 1 mapel pada hari yang sama, status dari GURU MAPEL DENGAN JAM
            // PALING AKHIR pada hari itu yang dipakai (lihat AbsensiSiswa::finalPerHari).
            // Laporan per guru mapel sendiri tetap utuh (lihat LaporanGuruController),
            // ini hanya memengaruhi rekap kelas.
            foreach (\App\Models\AbsensiSiswa::finalPerHari($records) as $final) {
                $tgl = (int) $final->tanggal->format('j');
                $kode = match ($final->status) {
                    'Sakit' => 'S',
                    'Izin' => 'I',
                    'Alfa' => 'A',
                    default => '.',
                };
                $data[$tgl] = $kode;

                $mapelNama = $final->jurnal?->mapel?->nama_mapel ?? '-';
                $jamKe = $final->jurnal?->jamPelajaranAkhir?->jam_ke ?? $final->jurnal?->jamPelajaran?->jam_ke;
                $keterangan[$tgl] = "{$final->status} \u{2014} {$mapelNama}" . ($jamKe ? " (jam ke-{$jamKe})" : '');

                if ($final->status === 'Sakit') $sakit++;
                if ($final->status === 'Izin') $izin++;
                if ($final->status === 'Alfa') $alfa++;
            }

            return [
                'siswa' => $siswa,
                'harian' => $data,
                'keterangan' => $keterangan,
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
            $kelas = $this->resolveKelasTerpilih($request, $kelas);
            if (! $kelas) {
                return redirect()->route('dashboard')
                    ->with('error', 'Belum ada data kelas. Silakan tambahkan kelas terlebih dahulu di menu Data Kelas.');
            }
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

    /**
     * Tentukan kelas yang dilihat oleh Admin/Kurikulum/Kepala Sekolah:
     * 1. pakai ?kelas_id= dari query string kalau valid;
     * 2. kalau tidak ada/tidak valid, pakai kelas walinya sendiri (kalau dia
     *    kebetulan juga wali kelas) atau kelas pertama yang ada di database;
     * 3. kalau memang belum ada satupun kelas di database, kembalikan null
     *    (pemanggil akan redirect dengan pesan yang jelas, bukan error 404 mentah).
     */
    private function resolveKelasTerpilih(Request $request, ?Kelas $kelasDefault): ?Kelas
    {
        $kelasId = $request->get('kelas_id');
        if ($kelasId) {
            $kelas = Kelas::find($kelasId);
            if ($kelas) {
                return $kelas;
            }
        }

        return $kelasDefault ?? Kelas::orderBy('nama_kelas')->first();
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
