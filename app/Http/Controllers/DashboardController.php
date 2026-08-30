<?php

namespace App\Http\Controllers;

use App\Support\KonteksPeriode;
use App\Models\AbsensiKegiatan;
use App\Models\AbsensiSiswa;
use App\Models\Ekstrakurikuler;
use App\Models\GuruMengajarKelas;
use App\Models\JadwalPelajaran;
use App\Models\JenisSurat;
use App\Models\JurnalMengajar;
use App\Models\KegiatanSekolah;
use App\Models\KasusSiswa;
use App\Models\Kelas;
use App\Models\PemanggilanOrangTua;
use App\Models\PembinaanSiswa;
use App\Models\Siswa;
use App\Models\Surat;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\OnboardingChecklistService;
use App\Support\SesiMengajarGrouper;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tahunAjaran = KonteksPeriode::pilihan();

        return match ($user->role) {
            'tu' => $this->dashboardTu(),
            'admin', 'kepala_sekolah' => $this->dashboardSekolah($user, $tahunAjaran),
            'kurikulum' => $this->dashboardKurikulum($user, $tahunAjaran),
            'kesiswaan' => $this->dashboardKesiswaan($tahunAjaran),
            'guru_bk' => $this->dashboardGuruBk($user, $tahunAjaran),
            default => $this->dashboardGuru($user, $tahunAjaran),
        };
    }

    /**
     * TU (Tata Usaha).
     *
     * PERBAIKAN BUG: sebelumnya role ini di-redirect ke route
     * 'surat.dashboard' yang middleware-nya `role:guru_bk,admin` — artinya
     * setiap TU yang login LANGSUNG mendapat halaman 403 dan sama sekali
     * tidak bisa memakai aplikasi. Sekarang TU punya dashboard sendiri
     * yang hanya berisi cakupan kerjanya: master Jenis Surat.
     */
    private function dashboardTu()
    {
        $totalJenis = JenisSurat::periodeAktif()->count();
        $totalJenisAktif = JenisSurat::periodeAktif()->where('is_aktif', true)->count();
        $jenisTerbaru = JenisSurat::periodeAktif()->orderByDesc('id')->limit(8)->get();

        // Jumlah pemakaian tiap jenis surat — 1 query GROUP BY, bukan
        // 1 query per jenis.
        $pemakaian = Surat::selectRaw('jenis_surat_id, COUNT(*) as jumlah')
            ->groupBy('jenis_surat_id')->pluck('jumlah', 'jenis_surat_id');

        return view('dashboard.tu', compact('totalJenis', 'totalJenisAktif', 'jenisTerbaru', 'pemakaian'));
    }

    /** Admin & Kepala Sekolah: ringkasan sekolah menyeluruh. */
    private function dashboardSekolah(User $user, ?TahunAjaran $tahunAjaran)
    {
        $totalSiswa = Siswa::periodeAktif()->where('is_active', true)->count();
        $totalSiswaLaki = Siswa::periodeAktif()->where('is_active', true)->where('jenis_kelamin', 'L')->count();
        $totalSiswaPerempuan = Siswa::periodeAktif()->where('is_active', true)->where('jenis_kelamin', 'P')->count();

        $totalGuru = User::where('role', 'guru')->count();
        $totalGuruAktif = User::where('role', 'guru')->where('is_active', true)->count();
        $totalGuruTidakAktif = $totalGuru - $totalGuruAktif;

        $totalKelas = Kelas::aktif()->count();
        $totalKelasTidakAktif = $tahunAjaran
            ? Kelas::untukTahunAjaran($tahunAjaran)->where('status', '!=', Kelas::STATUS_AKTIF)->count()
            : 0;

        // Satu query dipakai untuk dua keperluan: rekap status absensi hari
        // ini sekaligus daftar siswa Alfa.
        $absensiHariIniRaw = AbsensiSiswa::whereDate('tanggal', now()->toDateString())
            ->with(array_merge(['siswa', 'kelas'], AbsensiSiswa::RELASI_KONTEKS))
            ->get()
            ->groupBy('siswa_id');

        // Rekap dihitung dari status FINAL per siswa per hari (jam paling
        // akhir), supaya 1 siswa tidak terhitung berkali-kali.
        $rekapHariIni = $absensiHariIniRaw
            ->map(fn ($recordsSiswa) => AbsensiSiswa::finalPerHari($recordsSiswa)->first())
            ->groupBy('status')
            ->map->count();

        $siswaAlfaHariIni = AbsensiSiswa::alfaDariRecordsPerSiswa($absensiHariIniRaw);

        $jurnalHariIni = JurnalMengajar::whereDate('tanggal', now()->toDateString())->count();
        $jadwalHariIni = $tahunAjaran
            ? JadwalPelajaran::where('tahun_ajaran_id', $tahunAjaran->id)->where('hari', $this->hariIndonesia())->count()
            : 0;
        $persenJurnal = $jadwalHariIni > 0 ? (int) round($jurnalHariIni / $jadwalHariIni * 100) : 0;

        // 1 query GROUP BY untuk semua kelas (bukan 1 query per kelas).
        $terisiPerKelas = AbsensiSiswa::whereDate('tanggal', now()->toDateString())
            ->selectRaw('kelas_id, COUNT(DISTINCT siswa_id) as jumlah')
            ->groupBy('kelas_id')->pluck('jumlah', 'kelas_id');

        $rekapPerKelas = Kelas::aktif()->with('waliKelas')
            ->withCount(['siswas' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('nama_kelas')
            ->get()
            ->map(function ($kelas) use ($terisiPerKelas) {
                $terisi = (int) ($terisiPerKelas[$kelas->id] ?? 0);

                return [
                    'kelas' => $kelas->nama_kelas,
                    'wali_kelas' => $kelas->waliKelas->name ?? '-',
                    'jumlah_siswa' => $kelas->siswas_count,
                    'terisi' => $terisi,
                    'persentase' => $kelas->siswas_count > 0 ? (int) round($terisi / $kelas->siswas_count * 100) : 0,
                    'sudah_diabsen' => $terisi > 0,
                ];
            });

        $kelasBelumDiabsen = $rekapPerKelas->where('sudah_diabsen', false)->count();

        // Tren kehadiran 7 hari terakhir (dari baris mentah absensi — cukup
        // akurat untuk tren visual; angkanya bisa sedikit berbeda dari kartu
        // "hari ini" yang memakai status final per siswa).
        $statistikMingguan = collect(range(6, 0))->map(function ($i) {
            $tanggal = now()->subDays($i);
            $perStatus = AbsensiSiswa::whereDate('tanggal', $tanggal->toDateString())
                ->selectRaw('status, COUNT(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');

            return [
                'label' => $tanggal->translatedFormat('d M'),
                'Hadir' => (int) ($perStatus['Hadir'] ?? 0),
                'Sakit' => (int) ($perStatus['Sakit'] ?? 0),
                'Izin' => (int) ($perStatus['Izin'] ?? 0),
                'Alfa' => (int) ($perStatus['Alfa'] ?? 0),
            ];
        })->values();

        // Checklist persiapan hanya untuk yang MENGERJAKAN setup (Admin).
        // Kepala Sekolah tidak diberi checklist karena seluruh tautannya
        // menuju halaman yang tidak boleh ia buka (dulu berujung 403).
        $checklistOnboarding = $user->role === 'admin'
            ? (new OnboardingChecklistService)->status($user->role)
            : null;

        // "Aktivitas Terbaru" — BUKAN audit log (aplikasi tidak mencatat
        // siapa mengubah apa untuk data non-surat), melainkan gabungan
        // record terbaru beberapa tabel utama yang disusun jadi 1 linimasa.
        $aktivitasTerbaru = collect()
            ->merge(JurnalMengajar::with(['kelas', 'guru'])->latest('created_at')->limit(5)->get()
                ->map(fn ($j) => [
                    'teks' => "Jurnal mengajar {$j->kelas?->nama_kelas} oleh {$j->guru?->name}",
                    'waktu' => $j->created_at,
                    'tag' => 'Jurnal',
                    'ikon' => 'fa-pen-to-square',
                    'warna' => 'emerald',
                ]))
            ->merge(KasusSiswa::with('siswa')->latest('created_at')->limit(3)->get()
                ->map(fn ($k) => [
                    'teks' => "Kasus dicatat untuk {$k->siswa?->nama} ({$k->nama_pelanggaran})",
                    'waktu' => $k->created_at,
                    'tag' => 'BK',
                    'ikon' => 'fa-triangle-exclamation',
                    'warna' => 'rose',
                ]))
            ->merge(Siswa::periodeAktif()->latest('created_at')->limit(3)->get()
                ->map(fn ($s) => [
                    'teks' => "Data siswa {$s->nama} ditambahkan",
                    'waktu' => $s->created_at,
                    'tag' => 'Siswa',
                    'ikon' => 'fa-user-graduate',
                    'warna' => 'indigo',
                ]))
            ->sortByDesc('waktu')->take(8)->values();

        return view('dashboard.admin', compact(
            'totalSiswa', 'totalSiswaLaki', 'totalSiswaPerempuan',
            'totalGuru', 'totalGuruAktif', 'totalGuruTidakAktif',
            'totalKelas', 'totalKelasTidakAktif',
            'rekapHariIni', 'siswaAlfaHariIni', 'jurnalHariIni', 'jadwalHariIni', 'persenJurnal',
            'rekapPerKelas', 'kelasBelumDiabsen', 'statistikMingguan', 'aktivitasTerbaru',
            'tahunAjaran', 'checklistOnboarding'
        ));
    }

    /** Kurikulum: monitoring jurnal & kehadiran mengajar seluruh guru. */
    private function dashboardKurikulum(User $user, ?TahunAjaran $tahunAjaran)
    {
        $jurnalHariIni = JurnalMengajar::with(['guru', 'kelas', 'mapel'])
            ->whereDate('tanggal', now()->toDateString())
            ->latest()
            ->take(10)
            ->get();

        $totalJadwalHariIni = $tahunAjaran
            ? JadwalPelajaran::where('tahun_ajaran_id', $tahunAjaran->id)->where('hari', $this->hariIndonesia())->count()
            : 0;
        $totalJurnalHariIni = JurnalMengajar::whereDate('tanggal', now()->toDateString())->count();
        $persenJurnal = $totalJadwalHariIni > 0 ? (int) round($totalJurnalHariIni / $totalJadwalHariIni * 100) : 0;

        $totalGuru = User::where('role', 'guru')->count();
        $totalMappingKelas = $tahunAjaran ? GuruMengajarKelas::where('tahun_ajaran_id', $tahunAjaran->id)->count() : 0;
        $siswaAlfaHariIni = AbsensiSiswa::siswaAlfaHariIni();

        // INTI "monitoring guru": guru yang hari ini punya jadwal tapi belum
        // satu pun mengisi jurnal. Cukup 3 query untuk seluruh guru.
        $guruBelumMengisi = collect();
        if ($tahunAjaran) {
            $guruTerjadwal = JadwalPelajaran::where('tahun_ajaran_id', $tahunAjaran->id)
                ->where('hari', $this->hariIndonesia())
                ->distinct()->pluck('guru_id');

            $guruSudahMengisi = JurnalMengajar::whereDate('tanggal', now()->toDateString())
                ->distinct()->pluck('guru_id');

            $guruBelumMengisi = User::whereIn('id', $guruTerjadwal->diff($guruSudahMengisi))
                ->orderBy('name')->get(['id', 'name']);
        }

        $checklistOnboarding = (new OnboardingChecklistService)->status($user->role);

        return view('dashboard.kurikulum', compact(
            'jurnalHariIni', 'totalJadwalHariIni', 'totalJurnalHariIni', 'persenJurnal',
            'totalGuru', 'totalMappingKelas', 'siswaAlfaHariIni', 'guruBelumMengisi',
            'tahunAjaran', 'checklistOnboarding'
        ));
    }

    /** Kesiswaan: monitoring kehadiran & pelanggaran se-sekolah (view-only). */
    private function dashboardKesiswaan(?TahunAjaran $tahunAjaran)
    {
        $totalSiswa = Siswa::where('is_active', true)->count();
        $siswaAlfaHariIni = AbsensiSiswa::siswaAlfaHariIni();

        $alfaPerKelasHariIni = AbsensiSiswa::whereDate('tanggal', now()->toDateString())
            ->where('status', 'Alfa')
            ->selectRaw('kelas_id, COUNT(DISTINCT siswa_id) as jumlah')
            ->groupBy('kelas_id')->pluck('jumlah', 'kelas_id');

        $rekapPerKelas = Kelas::aktif()
            ->withCount(['siswas' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('nama_kelas')->get()
            ->map(fn ($kelas) => [
                'kelas' => $kelas,
                'total_siswa' => $kelas->siswas_count,
                'alfa_hari_ini' => (int) ($alfaPerKelasHariIni[$kelas->id] ?? 0),
            ]);

        $kelasBermasalah = $rekapPerKelas->where('alfa_hari_ini', '>', 0)->count();
        $totalEkskulAktif = Ekstrakurikuler::periodeAktif()->where('is_aktif', true)->count();
        $kasusBulanIni = KasusSiswa::aktif()
            ->whereBetween('tanggal_kejadian', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();

        return view('dashboard.kesiswaan', compact(
            'totalSiswa', 'siswaAlfaHariIni', 'rekapPerKelas', 'kelasBermasalah',
            'totalEkskulAktif', 'kasusBulanIni', 'tahunAjaran'
        ));
    }

    /** Guru BK: monitoring absensi + penanganan kasus di kelas binaannya. */
    private function dashboardGuruBk(User $user, ?TahunAjaran $tahunAjaran)
    {
        $kelasBk = $user->kelasBk();
        $kelasBkIds = $kelasBk->pluck('id');

        $siswaAlfaHariIni = collect();
        $rekapPerKelasBk = collect();
        $kasusBulanIni = 0;
        $siswaDalamPembinaan = 0;
        $pemanggilanMenunggu = 0;
        $totalSiswaBinaan = 0;

        if ($kelasBkIds->isNotEmpty()) {
            $siswaAlfaHariIni = AbsensiSiswa::siswaAlfaHariIni()
                ->filter(fn ($a) => $kelasBkIds->contains($a['kelas']?->id))
                ->values();

            // 1 query GROUP BY untuk semua kelas binaan sekaligus.
            $totalSiswaPerKelas = Kelas::whereIn('id', $kelasBkIds)
                ->withCount(['siswas' => fn ($q) => $q->where('is_active', true)])
                ->get()->pluck('siswas_count', 'id');
            $alfaPerKelasHariIni = AbsensiSiswa::whereIn('kelas_id', $kelasBkIds)
                ->whereDate('tanggal', now()->toDateString())
                ->where('status', 'Alfa')
                ->selectRaw('kelas_id, COUNT(DISTINCT siswa_id) as jumlah')
                ->groupBy('kelas_id')->pluck('jumlah', 'kelas_id');

            $rekapPerKelasBk = $kelasBk->map(fn ($kelas) => [
                'kelas' => $kelas,
                'total_siswa' => (int) ($totalSiswaPerKelas[$kelas->id] ?? 0),
                'alfa_hari_ini' => (int) ($alfaPerKelasHariIni[$kelas->id] ?? 0),
            ]);

            $totalSiswaBinaan = (int) $totalSiswaPerKelas->sum();

            $kasusBulanIni = KasusSiswa::aktif()
                ->whereIn('kelas_id', $kelasBkIds)
                ->whereBetween('tanggal_kejadian', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->count();

            $siswaDalamPembinaan = PembinaanSiswa::where('status', 'Pembinaan')
                ->whereHas('siswa', fn ($q) => $q->diKelasIn($kelasBkIds))
                ->distinct()->count('siswa_id');

            $pemanggilanMenunggu = PemanggilanOrangTua::where('status', PemanggilanOrangTua::STATUS_MENUNGGU_PERTEMUAN)
                ->whereHas('siswa', fn ($q) => $q->diKelasIn($kelasBkIds))
                ->count();
        }

        [$tugas, $sisaTugas] = $this->tugasBk($kelasBkIds);

        return view('dashboard.guru-bk', compact(
            'kelasBk', 'siswaAlfaHariIni', 'rekapPerKelasBk', 'totalSiswaBinaan',
            'kasusBulanIni', 'siswaDalamPembinaan', 'pemanggilanMenunggu', 'tahunAjaran',
            'tugas', 'sisaTugas'
        ));
    }

    /**
     * PEKERJAAN BK YANG MENUNGGU DIKERJAKAN.
     *
     * =================================================================
     * KENAPA TIGA JENIS INI SAJA
     * =================================================================
     * Yang masuk daftar hanya pekerjaan yang PUNYA PENANDA JELAS di
     * database bahwa ia tertunda — bukan tafsiran. Menambahkan dugaan
     * ("siswa ini poinnya tinggi, mungkin perlu dibina") akan membuat
     * daftarnya panjang oleh hal yang belum tentu tugas, dan begitu satu
     * baris terasa tidak relevan, seluruh daftarnya berhenti dipercaya.
     *
     *   1. Kasus berstatus "Baru" — sudah dilaporkan, belum ditangani.
     *      Statusnya berubah sendiri jadi "Dalam Pembinaan" begitu ada
     *      pembinaan dicatat, jadi baris ini hilang dengan sendirinya.
     *
     *   2. Pembinaan yang tanggal evaluasi berikutnya sudah lewat.
     *      Tanggal itu diisi sendiri oleh Guru BK saat mencatat pembinaan
     *      — jadi ini janji yang ia buat sendiri, bukan aturan yang
     *      dipaksakan sistem. Sebelum ini tidak ada satu halaman pun yang
     *      mengingatkannya.
     *
     *   3. Pemanggilan orang tua yang tanggal pertemuannya sudah tiba
     *      tetapi hasilnya belum dicatat.
     *
     * =================================================================
     * KENAPA DIBATASI
     * =================================================================
     * Berbeda dengan jurnal mengajar, pekerjaan BK tidak kedaluwarsa —
     * kasus yang belum ditangani sejak dua bulan lalu tetap harus
     * ditangani. Karena itu tidak ada batas hari; yang dibatasi jumlah
     * yang DITAMPILKAN. Sisanya disebut sebagai angka dengan tautan ke
     * daftar lengkapnya, supaya dashboard tetap bisa dikerjakan dan tidak
     * berubah jadi dinding keluhan.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: int}
     */
    private function tugasBk($kelasBkIds, int $maksTampil = 8): array
    {
        if ($kelasBkIds->isEmpty() || ! KonteksPeriode::bolehTulis()) {
            return [collect(), 0];
        }

        $hariIni = now()->toDateString();

        $kasusBaru = KasusSiswa::with(['siswa', 'kelas', 'jenisPelanggaran'])
            ->aktif()
            ->where('status', KasusSiswa::STATUS_BARU)
            ->whereIn('kelas_id', $kelasBkIds)
            ->get()
            ->map(fn ($k) => [
                'jenis' => 'kasus',
                'tanggal' => $k->tanggal_kejadian,
                'judul' => $k->siswa?->nama ?? 'Siswa',
                'rincian' => ($k->kelas?->nama_kelas ? 'Kelas '.$k->kelas->nama_kelas.' · ' : '')
                    .($k->jenisPelanggaran?->nama ?? 'Kasus belum ditangani'),
                'tombol' => 'Tangani Kasus',
                'url' => $k->siswa_id ? route('bk.siswa.show', $k->siswa_id) : route('bk.kasus.index'),
            ]);

        $evaluasiJatuhTempo = PembinaanSiswa::with('siswa')
            ->where('status', 'Pembinaan')
            ->whereNotNull('tanggal_evaluasi_berikutnya')
            ->whereDate('tanggal_evaluasi_berikutnya', '<=', $hariIni)
            ->whereHas('siswa', fn ($q) => $q->diKelasIn($kelasBkIds))
            ->get()
            ->map(fn ($p) => [
                'jenis' => 'evaluasi',
                'tanggal' => $p->tanggal_evaluasi_berikutnya,
                'judul' => $p->siswa?->nama ?? 'Siswa',
                'rincian' => 'Evaluasi pembinaan '.($p->jenis_pembinaan ?? '').' sudah jatuh tempo',
                'tombol' => 'Catat Evaluasi',
                'url' => $p->siswa_id ? route('bk.siswa.show', $p->siswa_id) : route('bk.pembinaan.index'),
            ]);

        $pemanggilan = PemanggilanOrangTua::with('siswa')
            ->where('status', PemanggilanOrangTua::STATUS_MENUNGGU_PERTEMUAN)
            ->whereDate('tanggal', '<=', $hariIni)
            ->whereHas('siswa', fn ($q) => $q->diKelasIn($kelasBkIds))
            ->get()
            ->map(fn ($p) => [
                'jenis' => 'pemanggilan',
                'tanggal' => $p->tanggal,
                'judul' => $p->siswa?->nama ?? 'Siswa',
                'rincian' => 'Pertemuan dengan orang tua sudah berlalu, hasilnya belum dicatat',
                'tombol' => 'Catat Hasil Pertemuan',
                'url' => route('bk.pemanggilan.hasil.edit', $p),
            ]);

        // Yang paling lama tertunda didahulukan — itu yang paling
        // mendesak, sekaligus paling mudah terlupakan.
        $semua = $kasusBaru->concat($evaluasiJatuhTempo)->concat($pemanggilan)
            ->sortBy(fn ($t) => $t['tanggal'] ? \Illuminate\Support\Carbon::parse($t['tanggal'])->timestamp : 0)
            ->values();

        return [$semua->take($maksTampil), max(0, $semua->count() - $maksTampil)];
    }

    /** Guru mapel (termasuk yang merangkap Wali Kelas). */
    private function dashboardGuru(User $user, ?TahunAjaran $tahunAjaran)
    {
        $jadwalHariIniMentah = $tahunAjaran
            ? JadwalPelajaran::with(['kelas', 'mapel', 'jamPelajaran'])
                ->where('guru_id', $user->id)
                ->where('tahun_ajaran_id', $tahunAjaran->id)
                ->where('hari', $this->hariIndonesia())
                ->get()
            : collect();

        // Jam berurutan dengan kelas & mapel sama digabung jadi 1 sesi,
        // konsisten dengan halaman "Absensi & Jurnal Mengajar".
        $jadwalHariIni = SesiMengajarGrouper::kelompokkan($jadwalHariIniMentah);
        $jadwalHariIni = SesiMengajarGrouper::tandaiSudahDiisi($jadwalHariIni, $jadwalHariIniMentah);

        $totalSesiHariIni = $jadwalHariIni->count();
        $sesiTerisiHariIni = $jadwalHariIni->filter(fn ($s) => $s['sudah_diisi'] ?? false)->count();
        $sesiBelumTerisi = $totalSesiHariIni - $sesiTerisiHariIni;

        $jurnalBulanIni = JurnalMengajar::where('guru_id', $user->id)
            ->whereBetween('tanggal', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();

        $jurnalTerakhir = JurnalMengajar::with(['kelas', 'mapel'])
            ->where('guru_id', $user->id)
            ->latest('tanggal')
            ->take(5)
            ->get();

        $kelasWali = $user->kelasWali;
        $siswaAlfaHariIni = $kelasWali ? AbsensiSiswa::siswaAlfaHariIni($kelasWali->id) : collect();

        // Kegiatan sekolah di luar jam KBM (lomba, asesmen, classmeeting,
        // pesantren Ramadan) yang absensinya menjadi tanggung jawab wali
        // kelas ini hari ini — lengkap dengan status sudah/belum diisi,
        // supaya tidak terlewat hanya karena hari itu tidak ada jadwal KBM.
        $kegiatanHariIni = collect();
        if ($kelasWali) {
            $tanggalHariIni = now()->toDateString();
            $sudahDiisi = AbsensiKegiatan::whereDate('tanggal', $tanggalHariIni)
                ->where('kelas_id', $kelasWali->id)
                ->get()
                ->keyBy('kegiatan_sekolah_id');

            $kegiatanHariIni = KegiatanSekolah::berlangsungPadaTanggal($tanggalHariIni)
                ->filter(fn ($k) => $k->mencakupKelas($kelasWali))
                ->map(fn ($k) => [
                    'kegiatan' => $k,
                    'sudah_diisi' => $sudahDiisi->has($k->id),
                ])
                ->values();
        }

        // Daftar pekerjaan yang benar-benar harus dikerjakan — lihat
        // tugasTertunda() untuk alasan bentuknya.
        $tugas = $this->tugasTertunda($user, $tahunAjaran);

        return view('dashboard.guru', compact(
            'jadwalHariIni', 'jurnalTerakhir', 'kelasWali', 'siswaAlfaHariIni', 'tahunAjaran',
            'totalSesiHariIni', 'sesiTerisiHariIni', 'sesiBelumTerisi', 'jurnalBulanIni', 'kegiatanHariIni',
            'tugas'
        ));
    }

    /**
     * PEKERJAAN YANG BELUM SELESAI, DALAM 7 HARI TERAKHIR.
     *
     * =================================================================
     * KENAPA ADA
     * =================================================================
     * Dashboard sebelumnya sudah menampilkan jadwal hari ini beserta
     * tanda "Terisi"/"Belum diisi". Isinya benar, tetapi bentuknya
     * LAPORAN: guru harus membacanya dulu, menafsirkan tandanya, lalu
     * menyimpulkan sendiri apa yang harus diklik. Bagi guru yang tidak
     * terbiasa dengan aplikasi, langkah menafsirkan itulah yang membuat
     * ragu — bukan kurangnya menu.
     *
     * Daftar ini membalik urutannya: yang tampil lebih dulu adalah
     * PEKERJAANNYA, satu baris satu tombol, tanpa ada yang perlu
     * disimpulkan sendiri.
     *
     * =================================================================
     * KENAPA 7 HARI, BUKAN HARI INI SAJA
     * =================================================================
     * Jurnal yang terlewat kemarin tetap harus diisi. Kalau dashboard
     * hanya menampilkan hari ini, pekerjaan yang tertinggal jadi tidak
     * terlihat sama sekali — guru baru tahu saat ditegur Kurikulum.
     *
     * Batas 7 hari dipilih supaya daftarnya tetap bisa diselesaikan:
     * tanpa batas, guru yang sebulan tertinggal akan membuka dashboard
     * dan melihat 40 baris merah — yang bukannya menolong, malah membuat
     * orang menyerah sebelum mulai.
     *
     * =================================================================
     * YANG SENGAJA TIDAK DIHITUNG
     * =================================================================
     * Hari yang ada Kegiatan Sekolah untuk kelas itu dikecualikan, persis
     * seperti pengingat WhatsApp. Pada hari lomba/classmeeting/pesantren,
     * KBM tidak berjalan dan yang mengisi kehadiran adalah wali kelas —
     * jurnal yang kosong di hari itu memang sudah semestinya.
     *
     * Begitu pula saat pengguna sedang menengok periode lampau: di sana
     * tidak ada yang bisa dikerjakan, jadi menampilkan daftar tugas hanya
     * akan menyesatkan.
     */
    private function tugasTertunda(User $user, ?TahunAjaran $tahunAjaran, int $hariKeBelakang = 7)
    {
        if (! $tahunAjaran || ! KonteksPeriode::bolehTulis()) {
            return collect();
        }

        $mulai = now()->copy()->subDays($hariKeBelakang - 1)->startOfDay();

        // Jangan menagih hari sebelum periodenya sendiri dimulai.
        if ($tahunAjaran->tanggal_mulai && $mulai->lt($tahunAjaran->tanggal_mulai)) {
            $mulai = $tahunAjaran->tanggal_mulai->copy()->startOfDay();
        }

        $jadwal = JadwalPelajaran::with(['kelas', 'mapel', 'jamPelajaran'])
            ->where('guru_id', $user->id)
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->whereHas('jamPelajaran', fn ($q) => $q->where('is_active', true))
            ->get();

        if ($jadwal->isEmpty()) {
            return collect();
        }

        // Satu query untuk seluruh rentang, bukan satu query per hari.
        $terisi = \App\Models\JurnalMengajarSlot::whereIn('jadwal_pelajaran_id', $jadwal->pluck('id'))
            ->whereBetween('tanggal', [$mulai->toDateString(), now()->toDateString()])
            ->get()
            ->map(fn ($s) => $s->jadwal_pelajaran_id.'|'.\Illuminate\Support\Carbon::parse($s->tanggal)->toDateString())
            ->flip();

        $hasil = collect();
        $kursor = now()->copy()->startOfDay();

        while ($kursor->gte($mulai)) {
            $hari = $this->hariIndonesia($kursor);

            if ($hari === 'Minggu') {
                $kursor->subDay();

                continue;
            }

            $milikHariItu = $jadwal->where('hari', $hari);

            if ($milikHariItu->isEmpty()) {
                $kursor->subDay();

                continue;
            }

            $kelasBerkegiatan = KegiatanSekolah::kelasIdBerkegiatanPada($kursor);
            $tanggal = $kursor->toDateString();

            foreach (SesiMengajarGrouper::kelompokkan($milikHariItu) as $sesi) {
                $pertama = $sesi['slots']->first();

                if (in_array($pertama->kelas_id, $kelasBerkegiatan, true)) {
                    continue;
                }

                // Satu sesi selalu disimpan sekaligus, jadi jam pertama
                // terisi berarti seluruh sesinya terisi.
                if ($terisi->has($pertama->id.'|'.$tanggal)) {
                    continue;
                }

                // Sesi hari ini yang jamnya BELUM selesai bukan pekerjaan
                // yang tertunda — guru mungkin masih mengajar saat ini.
                if ($kursor->isToday()) {
                    $selesai = $kursor->copy()->setTimeFromTimeString($sesi['jam_akhir']->jam_selesai);
                    if ($selesai->gt(now())) {
                        continue;
                    }
                }

                $hasil->push([
                    'tanggal' => $kursor->copy(),
                    'hari_ini' => $kursor->isToday(),
                    'ids' => $sesi['ids'],
                    'kelas' => $sesi['kelas'],
                    'mapel' => $sesi['mapel'],
                    'jam_awal' => $sesi['jam_awal'],
                    'jam_akhir' => $sesi['jam_akhir'],
                ]);
            }

            $kursor->subDay();
        }

        return $hasil;
    }

    private function hariIndonesia(?\Illuminate\Support\Carbon $tanggal = null): string
    {
        $map = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'];

        return $map[($tanggal ?? now())->dayOfWeek] ?? 'Senin';
    }
}
