<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSiswa;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\JurnalMengajar;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\SesiMengajarGrouper;
use App\Support\RentangBulan;
use Illuminate\Http\Request;

class RekapController extends Controller
{
    /**
     * Rekapitulasi menyeluruh untuk Admin, Kurikulum & Kepala Sekolah.
     *
     * Rekapitulasi Jurnal Mengajar ditampilkan dalam format bulanan
     * (tanggal 1 s.d akhir bulan) sama seperti Rekap Absensi Bulanan:
     * - "Seharusnya" dihitung dari JADWAL PELAJARAN guru, dikelompokkan
     *   per SESI mengajar (bukan per jam) — karena 1 sesi (meski 1, 2,
     *   atau 3 jam berurutan) hanya butuh 1 jurnal. Lalu dikalikan
     *   berapa kali hari itu jatuh dalam bulan yang dipilih.
     * - "Terisi" dihitung dari jurnal_mengajars yang benar-benar ada
     *   pada tanggal tsb untuk sesi yang bersangkutan.
     */
    public function index(Request $request)
    {
        // Bulan & tahun default SELALU mengikuti tanggal server saat ini
        // (now()), bukan nilai tetap — supaya otomatis pindah bulan/tahun
        // dengan sendirinya begitu kalender berganti.
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);
        // PERBAIKAN PERFORMA — dihitung SEKALI, dipakai ulang di semua query bulan ini di bawah (lihat App\Support\RentangBulan).
        [$awalBulan, $akhirBulan] = RentangBulan::dari($tahun, $bulan);
        $tahunAjaran = TahunAjaran::aktif();
        $jumlahHari = \Carbon\Carbon::create($tahun, $bulan, 1)->daysInMonth;

        // Peta "nama hari Indonesia" -> daftar tanggal (1..N) yang jatuh pada
        // hari itu di bulan yang dipilih. Dipakai untuk menghitung berapa kali
        // sesi hari Senin (misal) seharusnya terjadi bulan ini.
        $tanggalPerHari = [];
        for ($t = 1; $t <= $jumlahHari; $t++) {
            $namaHari = \Carbon\Carbon::create($tahun, $bulan, $t)->translatedFormat('l');
            $tanggalPerHari[$namaHari][] = $t;
        }

        $rekapGuru = collect();

        if ($tahunAjaran) {
            $guruList = User::where('role', 'guru')->orderBy('name')->get();

            $jadwalSemua = JadwalPelajaran::with(['kelas', 'mapel', 'jamPelajaran'])
                ->where('tahun_ajaran_id', $tahunAjaran->id)
                ->whereIn('guru_id', $guruList->pluck('id'))
                ->get()
                ->groupBy('guru_id');

            // Semua jurnal bulan ini diambil SEKALI, dikelompokkan per
            // jadwal_pelajaran_id (= slot jam AWAL sesi), supaya tidak query
            // berulang per guru/per sesi (hindari N+1).
            $jurnalBulanIni = JurnalMengajar::whereBetween('tanggal', [$awalBulan, $akhirBulan])
                ->get(['id', 'jadwal_pelajaran_id', 'tanggal'])
                ->groupBy('jadwal_pelajaran_id');

            $rekapGuru = $guruList->map(function ($guru) use ($jadwalSemua, $tanggalPerHari, $jurnalBulanIni, $jumlahHari) {
                $jadwalGuru = $jadwalSemua->get($guru->id, collect());

                // Kelompokkan jadi sesi PER HARI (grouping mengasumsikan 1
                // hari sekaligus, karena jam_ke berulang tiap hari).
                $sesiList = $jadwalGuru->groupBy('hari')->flatMap(
                    fn ($jadwalHari, $hari) => SesiMengajarGrouper::kelompokkan($jadwalHari)
                        ->map(function ($sesi) use ($hari) {
                            $sesi['hari'] = $hari;
                            return $sesi;
                        })
                );

                $harian = array_fill(1, $jumlahHari, ['seharusnya' => 0, 'terisi' => 0]);
                $totalSeharusnya = 0;
                $totalTerisi = 0;

                foreach ($sesiList as $sesi) {
                    $tanggalCocok = $tanggalPerHari[$sesi['hari']] ?? [];
                    $idAwal = $sesi['slots']->first()->id;

                    $tanggalTerisi = ($jurnalBulanIni->get($idAwal) ?? collect())
                        ->map(fn ($j) => (int) $j->tanggal->format('j'))
                        ->toArray();

                    foreach ($tanggalCocok as $t) {
                        $totalSeharusnya++;
                        $harian[$t]['seharusnya']++;
                        if (in_array($t, $tanggalTerisi, true)) {
                            $totalTerisi++;
                            $harian[$t]['terisi']++;
                        }
                    }
                }

                return [
                    'guru' => $guru,
                    'harian' => $harian,
                    'total_terisi' => $totalTerisi,
                    'total_seharusnya' => $totalSeharusnya,
                    'persen' => $totalSeharusnya > 0 ? round($totalTerisi / $totalSeharusnya * 100) : null,
                ];
            });
        }

        $rekapKelas = Kelas::aktif()->withCount(['siswas' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('nama_kelas')
            ->get()
            ->map(function ($kelas) use ($awalBulan, $akhirBulan) {
                $jumlahJurnal = JurnalMengajar::where('kelas_id', $kelas->id)
                    ->whereBetween('tanggal', [$awalBulan, $akhirBulan])->count();

                // Pakai status final per hari (bukan mentah semua mapel), supaya
                // siswa yang tercatat Alfa oleh 2 guru mapel di hari yang sama
                // tidak dihitung 2x. Konsisten dengan Rekap Absensi Bulanan Wali Kelas.
                $absensiKelas = AbsensiSiswa::where('kelas_id', $kelas->id)
                    ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
                    ->with(['jurnal.jamPelajaran', 'jurnal.jamPelajaranAkhir'])
                    ->get()
                    ->groupBy('siswa_id');

                $totalAlfa = $absensiKelas->sum(
                    fn ($recordsSiswa) => AbsensiSiswa::finalPerHari($recordsSiswa)
                        ->where('status', 'Alfa')->count()
                );

                return [
                    'kelas' => $kelas,
                    'jumlah_jurnal' => $jumlahJurnal,
                    'total_alfa' => $totalAlfa,
                ];
            });

        return view('rekap.index', compact('rekapGuru', 'rekapKelas', 'bulan', 'tahun', 'jumlahHari', 'tahunAjaran'));
    }
}
