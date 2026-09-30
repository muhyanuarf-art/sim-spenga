<?php

namespace App\Http\Controllers;

use App\Support\KonteksPeriode;
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
        $tahunAjaran = KonteksPeriode::pilihan();
        $jumlahHari = \Carbon\Carbon::create($tahun, $bulan, 1)->daysInMonth;

        // Peta "nama hari Indonesia" -> daftar tanggal (1..N) yang jatuh pada
        // hari itu di bulan yang dipilih. Dipakai untuk menghitung berapa kali
        // sesi hari Senin (misal) seharusnya terjadi bulan ini.
        // HARI LIBUR TIDAK IKUT DIHITUNG.
        //
        // Peta ini yang menentukan "sesi hari Senin seharusnya terjadi
        // berapa kali bulan ini". Tanggal libur dikeluarkan dari sini,
        // sehingga hari itu tidak pernah masuk ke penyebut — bukan
        // dihitung lalu dimaafkan. Tanpa ini, guru yang jadwalnya jatuh
        // pada tanggal merah terlihat lebih tidak patuh daripada guru
        // yang jadwalnya kebetulan tidak.
        $tanggalLibur = \App\Models\HariLibur::tanggalDalam(
            \Carbon\Carbon::create($tahun, $bulan, 1)->startOfDay(),
            \Carbon\Carbon::create($tahun, $bulan, $jumlahHari)->startOfDay()
        );

        $tanggalPerHari = [];
        for ($t = 1; $t <= $jumlahHari; $t++) {
            $hariIni = \Carbon\Carbon::create($tahun, $bulan, $t);

            if (in_array($hariIni->toDateString(), $tanggalLibur, true)) {
                continue;
            }

            $tanggalPerHari[$hariIni->translatedFormat('l')][] = $t;
        }

        $rekapGuru = collect();

        if ($tahunAjaran) {
            $jadwalSemua = JadwalPelajaran::with(['kelas', 'mapel', 'jamPelajaran'])
                ->where('tahun_ajaran_id', $tahunAjaran->id)
                ->get();

            // Semua jurnal bulan ini diambil SEKALI, supaya tidak query
            // berulang per guru/per sesi (hindari N+1). `guru_id` ikut
            // diambil karena itulah penentu SIAPA yang benar-benar menulis
            // jurnal itu — lihat penjelasan panjang di bawah.
            $jurnalBulanIni = JurnalMengajar::whereBetween('tanggal', [$awalBulan, $akhirBulan])
                ->get(['id', 'jadwal_pelajaran_id', 'guru_id', 'tanggal']);

            // DAFTAR PENGAJARNYA TIDAK LAGI DIAMBIL DARI PERAN.
            //
            // Dulu `User::where('role', 'guru')`. Dua akibatnya:
            //
            //   1. Pengguna berperan Kurikulum, Kesiswaan, atau Guru BK
            //      yang mengampu mata pelajaran tidak muncul sama sekali —
            //      jurnalnya tercatat tetapi kepatuhannya tidak terpantau.
            //   2. Guru yang sudah keluar tetapi masih punya jurnal bulan
            //      ini bisa hilang bila perannya diubah.
            //
            // Sekarang: siapa pun yang PUNYA JADWAL di periode ini, atau
            // PUNYA JURNAL di bulan ini. Itulah definisi "mengajar".
            $idPengajar = $jadwalSemua->pluck('guru_id')
                ->merge($jurnalBulanIni->pluck('guru_id'))
                ->filter()
                ->unique();

            $guruList = User::whereIn('id', $idPengajar)->orderBy('name')->get();

            // Siapa pemegang tiap slot jadwal SEKARANG — dipakai untuk
            // mengenali jurnal yang ditulis pemegang sebelumnya.
            $pemegangSlot = $jadwalSemua->pluck('guru_id', 'id');

            // Peta: slot => tanggal => id guru yang menulis jurnalnya.
            $penulisJurnal = [];
            foreach ($jurnalBulanIni as $j) {
                $penulisJurnal[$j->jadwal_pelajaran_id][(int) $j->tanggal->format('j')] = $j->guru_id;
            }

            $jurnalPerGuru = $jurnalBulanIni->groupBy('guru_id');
            $jadwalPerGuru = $jadwalSemua->groupBy('guru_id');

            $rekapGuru = $guruList->map(function ($guru) use (
                $jadwalPerGuru, $tanggalPerHari, $jumlahHari, $tahun, $bulan,
                $penulisJurnal, $jurnalPerGuru, $pemegangSlot
            ) {
                $jadwalGuru = $jadwalPerGuru->get($guru->id, collect());

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

                // ==========================================================
                // SIAPA YANG DIHITUNG UNTUK SEBUAH TANGGAL
                // ==========================================================
                // Aturannya: YANG MENULIS JURNALNYA. Bukan yang memegang
                // jadwalnya hari ini.
                //
                // Ini yang membereskan pergantian guru di tengah semester.
                // Dulu "seharusnya" seluruhnya diambil dari jadwal yang
                // berlaku sekarang, sehingga saat jadwal Iftikhoor
                // dialihkan ke guru pengganti:
                //
                //   - Iftikhoor jatuh ke 0 dari 0, seolah tidak pernah
                //     mengajar sepanjang semester;
                //   - guru pengganti menanggung sebulan penuh, DAN jurnal
                //     yang Iftikhoor isi awal Oktober ikut terhitung
                //     sebagai miliknya — karena keduanya menunjuk baris
                //     jadwal yang sama.
                //
                // Sekarang tanggal yang jurnalnya ditulis orang lain
                // dilewati, dan tanggal setelah seorang guru berhenti
                // tidak lagi dibebankan kepadanya.
                $nonaktifSejak = $guru->nonaktif_sejak;

                foreach ($sesiList as $sesi) {
                    $tanggalCocok = $tanggalPerHari[$sesi['hari']] ?? [];
                    $idAwal = $sesi['slots']->first()->id;

                    foreach ($tanggalCocok as $t) {
                        $penulis = $penulisJurnal[$idAwal][$t] ?? null;

                        // Jurnalnya sudah ditulis orang lain — tanggal itu
                        // miliknya, bukan milik pemegang jadwal sekarang.
                        if ($penulis !== null && $penulis !== $guru->id) {
                            continue;
                        }

                        // Sudah berhenti sebelum tanggal ini: tidak boleh
                        // lagi dituntut mengisi jurnal.
                        if ($penulis === null && $nonaktifSejak
                            && \Carbon\Carbon::create($tahun, $bulan, $t)->gt($nonaktifSejak)) {
                            continue;
                        }

                        $totalSeharusnya++;
                        $harian[$t]['seharusnya']++;

                        if ($penulis === $guru->id) {
                            $totalTerisi++;
                            $harian[$t]['terisi']++;
                        }
                    }
                }

                // JURNAL PADA SLOT YANG KINI DIPEGANG ORANG LAIN.
                //
                // Guru yang keluar dan jadwalnya sudah dialihkan tidak
                // punya sesi lagi di perulangan atas, sehingga pekerjaannya
                // akan hilang sama sekali dari laporan. Padahal jurnalnya
                // ada, bertanggal, dan atas namanya.
                //
                // Baris-baris itu ditambahkan di sini: dihitung sebagai
                // seharusnya DAN terisi, karena memang dikerjakan.
                foreach ($jurnalPerGuru->get($guru->id, collect()) as $j) {
                    if (($pemegangSlot[$j->jadwal_pelajaran_id] ?? null) === $guru->id) {
                        continue; // sudah dihitung di perulangan atas
                    }

                    $t = (int) $j->tanggal->format('j');
                    $totalSeharusnya++;
                    $totalTerisi++;
                    $harian[$t]['seharusnya']++;
                    $harian[$t]['terisi']++;
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
                    ->with(AbsensiSiswa::RELASI_KONTEKS)
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
