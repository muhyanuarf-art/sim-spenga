<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSiswa;
use App\Models\JadwalPelajaran;
use App\Models\JurnalMengajar;
use App\Models\JurnalMengajarSlot;
use App\Models\NotifikasiAlfaTerkirim;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\KeanggotaanKelas;
use App\Support\PeriodeAkademik;
use App\Support\SesiMengajarGrouper;
use App\Jobs\KirimNotifikasiAlfaWhatsapp;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MengajarController extends Controller
{
    /**
     * Step 1: Guru memilih hari & melihat jadwal miliknya, sudah dikelompokkan
     * per SESI mengajar (jam-jam berurutan dengan kelas & mapel yang sama
     * digabung jadi 1 kartu, bukan 1 kartu per jam).
     *
     * Selain periode AKTIF, guru juga bisa pindah ke periode LAIN yang
     * sudah dibuka kuncinya oleh admin (mis. Semester Ganjil yang sempat
     * terlewat lalu dibuka lagi setelah Semester Genap jadi periode aktif),
     * lewat query string `periode` (id tahun_ajaran). Ini supaya jurnal/
     * absensi yang tertinggal di periode lampau tetap bisa diisi tanpa
     * harus mengaktifkan ulang periode itu (yang justru akan mengacaukan
     * periode aktif sekolah saat ini).
     *
     * Admin juga bisa membuka halaman ini untuk MEWAKILI guru tertentu
     * (mis. guru berhalangan/lupa mengisi) lewat query string `guru_id` —
     * admin memilih guru dulu, baru daftar periode & jadwal di bawah
     * mengikuti guru yang dipilih itu, persis seperti guru itu sendiri yang
     * membuka halamannya. Guru biasa tidak melihat pemilih ini sama sekali
     * (selalu mewakili dirinya sendiri).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $guruList = collect();
        $guru = $user;

        if ($isAdmin) {
            $guruList = User::where('role', 'guru')->orderBy('name')->get();
            $guruId = (int) $request->get('guru_id', 0);
            $guru = $guruList->firstWhere('id', $guruId);
        }

        $tahunAjaranAktif = TahunAjaran::aktif();
        $hari = $request->get('hari', $this->hariIniIndonesia());

        $periodeList = collect();
        $tahunAjaran = null;
        $sesiList = collect();

        if ($guru) {
            // Periode yang boleh dipilih untuk guru ini: periode AKTIF
            // (SELALU ditampilkan kalau ada, TERLEPAS dari apakah guru ini
            // sudah punya baris jadwal di periode itu — mis. baru saja
            // diaktifkan & jadwalnya belum di-"Salin Data" dari semester
            // sebelumnya, guru tetap perlu lihat itu sebagai periode aktif)
            // + periode lain di mana guru ini punya jadwal DAN periode itu
            // sedang tidak terkunci. Periode non-aktif yang masih terkunci
            // sengaja tidak dimasukkan supaya tidak muncul pilihan yang
            // ujung-ujungnya gagal disimpan (store() tetap menolaknya lewat
            // PeriodeAkademik::pastikanTidakTerkunci(), tapi lebih baik
            // tidak diberi pilihan yang pasti gagal).
            $periodeIdMilikGuru = JadwalPelajaran::where('guru_id', $guru->id)
                ->distinct()
                ->pluck('tahun_ajaran_id');

            $periodeList = TahunAjaran::where(function ($q) use ($periodeIdMilikGuru, $tahunAjaranAktif) {
                    $q->where(function ($q2) use ($periodeIdMilikGuru) {
                        $q2->whereIn('id', $periodeIdMilikGuru)->where('terkunci', false);
                    });
                    if ($tahunAjaranAktif) {
                        $q->orWhere('id', $tahunAjaranAktif->id);
                    }
                })
                ->orderByDesc('tanggal_mulai')
                ->get();

            $periodeId = $request->get('periode');
            $tahunAjaran = $periodeId ? $periodeList->firstWhere('id', (int) $periodeId) : null;
            $tahunAjaran = $tahunAjaran ?? $tahunAjaranAktif;

            if ($tahunAjaran) {
                $jadwal = JadwalPelajaran::with(['kelas', 'mapel', 'jamPelajaran'])
                    ->where('guru_id', $guru->id)
                    ->where('tahun_ajaran_id', $tahunAjaran->id)
                    ->where('hari', $hari)
                    ->get();

                $sesiList = SesiMengajarGrouper::kelompokkan($jadwal);

                // tandai sesi yang sudah diisi jurnalnya hari ini — hanya
                // relevan kalau yang sedang dilihat memang periode AKTIF &
                // harinya hari ini juga (berlaku sama baik dibuka oleh guru
                // sendiri maupun oleh admin yang mewakilinya, karena yang
                // dicek adalah jurnal milik GURU tsb, bukan milik admin).
                $sedangLihatPeriodeAktif = $tahunAjaranAktif && $tahunAjaran->id === $tahunAjaranAktif->id;
                if ($hari === $this->hariIniIndonesia() && $sedangLihatPeriodeAktif) {
                    $sesiList = SesiMengajarGrouper::tandaiSudahDiisi($sesiList, $jadwal);
                }
            }
        }

        $hariList = JadwalPelajaran::HARI_LIST();

        return view('absensi.pilih-kelas', compact(
            'sesiList', 'hari', 'hariList', 'tahunAjaran', 'tahunAjaranAktif',
            'periodeList', 'isAdmin', 'guruList', 'guru'
        ));
    }

    /**
     * Step 2: Form isi jurnal mengajar + absensi siswa untuk 1 SESI
     * (bisa mencakup beberapa jam pelajaran berurutan sekaligus).
     *
     * $ids = daftar id jadwal_pelajarans dipisah koma, mis. "12,13,14".
     */
    public function form(Request $request, string $ids)
    {
        $slotJadwal = $this->resolveSesi($request, $ids);
        $tanggal = $request->get('tanggal', now()->toDateString());

        $jurnal = $this->cariJurnalUntukSesi($slotJadwal, $tanggal);

        $jadwalAwal = $slotJadwal->first();
        $jadwalAkhir = $slotJadwal->last();

        // (2026-08-23) — pakai keanggotaan kelas PADA TANGGAL sesi ini,
        // bukan kelas siswa saat ini. Supaya kalau ada siswa yang sudah
        // pindah kelas, dia tetap muncul di form ini untuk tanggal SEBELUM
        // dia pindah (mis. guru baru sempat isi absensi 3 hari kemudian).
        // Lihat App\Support\KeanggotaanKelas untuk penjelasan lengkap.
        $siswas = KeanggotaanKelas::anggotaPadaTanggal($jadwalAwal->kelas, $tanggal);

        $absensiTersimpan = [];
        if ($jurnal) {
            $jurnal->load('absensi');
            foreach ($jurnal->absensi as $a) {
                $absensiTersimpan[$a->siswa_id] = $a->status;
            }
        }

        return view('absensi.form', [
            'ids' => $ids,
            'jadwalAwal' => $jadwalAwal,
            'jadwalAkhir' => $jadwalAkhir,
            'jumlahJam' => $slotJadwal->count(),
            'jurnal' => $jurnal,
            'siswas' => $siswas,
            'absensiTersimpan' => $absensiTersimpan,
            'tanggal' => $tanggal,
        ]);
    }

    /**
     * Simpan Jurnal Mengajar + Absensi Siswa sekaligus untuk 1 SESI
     * (1x submit mencakup seluruh jam dalam sesi tsb, bukan per jam).
     */
    public function store(Request $request, string $ids)
    {
        $slotJadwal = $this->resolveSesi($request, $ids);

        // STEP 2 Bagian 8: cek periode MILIK JADWAL yang sedang diisi (bukan
        // cuma periode aktif global — middleware 'periode-aktif' di route
        // sudah menutup jalur biasa, tapi ini jaga-jaga kalau id jadwal yang
        // dikirim ternyata milik tahun ajaran lain yang sudah terkunci).
        PeriodeAkademik::pastikanTidakTerkunci($slotJadwal->first()->tahunAjaran);

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'materi' => ['required', 'string'],
            'kegiatan' => ['nullable', 'string'],
            'keterangan' => ['nullable', 'string'],
            'absensi' => ['required', 'array'],
            'absensi.*' => ['required', 'in:Hadir,Sakit,Izin,Alfa'],
        ]);

        $jadwalAwal = $slotJadwal->first();
        $jadwalAkhir = $slotJadwal->last();

        // (2026-08-23) — sama seperti di form(): validasi "anggota kelas"
        // memakai keanggotaan PADA TANGGAL yang diisi, bukan kelas siswa
        // saat ini, supaya submit untuk tanggal lampau (yang formnya sudah
        // menampilkan siswa yang saat itu masih di kelas ini) tidak ditolak.
        $siswaIdsKelas = KeanggotaanKelas::anggotaPadaTanggal($jadwalAwal->kelas, $validated['tanggal'])->pluck('id');
        $siswaIdsAsing = collect(array_keys($validated['absensi']))
            ->map(fn ($id) => (int) $id)
            ->diff($siswaIdsKelas);
        if ($siswaIdsAsing->isNotEmpty()) {
            abort(422, 'Ada siswa pada data absensi yang bukan anggota kelas ini.');
        }

        DB::transaction(function () use ($validated, $slotJadwal, $jadwalAwal, $jadwalAkhir) {
            $jurnal = $this->cariJurnalUntukSesi($slotJadwal, $validated['tanggal']);

            if (!$jurnal) {
                $jurnal = new JurnalMengajar();
            }

            $jurnal->fill([
                'jadwal_pelajaran_id' => $jadwalAwal->id,
                'guru_id' => $jadwalAwal->guru_id,
                'kelas_id' => $jadwalAwal->kelas_id,
                'mata_pelajaran_id' => $jadwalAwal->mata_pelajaran_id,
                'jam_pelajaran_id' => $jadwalAwal->jam_pelajaran_id,
                'jam_pelajaran_id_akhir' => $jadwalAkhir->jam_pelajaran_id,
                'tanggal' => $validated['tanggal'],
                'materi' => $validated['materi'],
                'kegiatan' => $validated['kegiatan'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
            ]);
            $jurnal->save();

            // Kaitkan setiap jam dalam sesi ini ke jurnal yang sama.
            // unique(jadwal_pelajaran_id, tanggal) memastikan 1 jam pada
            // 1 tanggal tidak bisa "kepakai" jurnal lain.
            foreach ($slotJadwal as $j) {
                JurnalMengajarSlot::updateOrCreate(
                    ['jadwal_pelajaran_id' => $j->id, 'tanggal' => $validated['tanggal']],
                    ['jurnal_mengajar_id' => $jurnal->id]
                );
            }
            // Bersihkan slot lama milik jurnal ini yang sudah tidak relevan (jarang terjadi).
            JurnalMengajarSlot::where('jurnal_mengajar_id', $jurnal->id)
                ->whereNotIn('jadwal_pelajaran_id', $slotJadwal->pluck('id'))
                ->delete();

            $rekap = ['Hadir' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];

            foreach ($validated['absensi'] as $siswaId => $status) {
                AbsensiSiswa::updateOrCreate(
                    ['jurnal_mengajar_id' => $jurnal->id, 'siswa_id' => $siswaId],
                    ['kelas_id' => $jadwalAwal->kelas_id, 'tanggal' => $validated['tanggal'], 'status' => $status]
                );
                $rekap[$status] = ($rekap[$status] ?? 0) + 1;
            }

            $jurnal->update([
                'jumlah_hadir' => $rekap['Hadir'],
                'jumlah_sakit' => $rekap['Sakit'],
                'jumlah_izin' => $rekap['Izin'],
                'jumlah_alfa' => $rekap['Alfa'],
            ]);
        });

        // Cek & kirim notifikasi WA ke orang tua siswa Alfa. Bagian ini HANYA
        // melakukan query database ringan (cepat) — pengiriman WA yang
        // sesungguhnya (lambat, tergantung jaringan/API luar) didorong ke
        // job antrian (queue), tidak dijalankan di sini. Jadi guru tetap
        // langsung selesai menyimpan tanpa menunggu proses WA.
        $this->prosesNotifikasiAlfa($validated['absensi'], $validated['tanggal']);

        $labelJam = $jadwalAwal->jam_pelajaran_id === $jadwalAkhir->jam_pelajaran_id
            ? '1 jam'
            : "{$slotJadwal->count()} jam sekaligus";

        return redirect()->route('mengajar.index')
            ->with('success', "Absensi & Jurnal untuk kelas {$jadwalAwal->kelas->nama_kelas} ({$labelJam}) berhasil disimpan.");
    }

    /**
     * Untuk siswa yang statusnya Alfa pada sesi yang BARU disimpan ini,
     * cek apakah status Alfa itu memang status FINAL hari ini (dari sesi
     * dengan jam paling akhir — aturan "Absensi Kelas" yang sama seperti di
     * AbsensiSiswa::finalPerHari). Kalau ya, dan belum pernah dikirimi
     * notifikasi hari ini (dicegah lewat unique siswa_id+tanggal), antrikan
     * 1 job pengiriman WA. Kalau statusnya BUKAN status final (ada guru
     * mapel dengan jam lebih akhir yang sudah mengisi status berbeda),
     * tidak dikirim notifikasi dari sesi ini — biarkan sesi paling akhir
     * yang menentukan.
     *
     * PENTING: notifikasi WA HANYA relevan untuk absensi tanggal HARI INI
     * (saat guru menyimpannya). Kalau guru baru mengisi jurnal/absensi
     * untuk tanggal yang SUDAH LEWAT (telat/lupa beberapa hari, baru
     * diisi belakangan — termasuk lewat "Buka Kunci" periode lampau),
     * status Alfa TETAP dicatat seperti biasa tapi WA-nya SENGAJA TIDAK
     * dikirim: mengabari orang tua "anak Alfa" untuk kejadian berhari-hari
     * yang lalu sudah tidak berguna/relevan lagi, dan berisiko membuat
     * orang tua bingung atau panik tanpa alasan. Barisnya tetap dibuat di
     * notifikasi_alfa_terkirims dengan status 'dilewati' supaya tetap
     * tercatat di histori (bukan disembunyikan begitu saja) & anti-duplikat
     * (siswa_id+tanggal unik) tetap berlaku seperti biasa.
     *
     * Catatan: kalau nanti sesi ini "dikalahkan" oleh sesi lain yang jam-nya
     * lebih akhir dan mengoreksi jadi Hadir, sistem TIDAK mengirim pesan
     * "koreksi/pembatalan" — notifikasi yang sudah terlanjur terkirim tetap
     * seperti itu. Ini simplifikasi yang disengaja untuk menjaga fitur tetap
     * ringan; kalau dibutuhkan fitur koreksi otomatis, bisa dikembangkan lagi.
     */
    private function prosesNotifikasiAlfa(array $absensi, string $tanggal): void
    {
        $siswaAlfaDiSesiIni = collect($absensi)->filter(fn ($status) => $status === 'Alfa')->keys();
        if ($siswaAlfaDiSesiIni->isEmpty()) {
            return;
        }

        // Dibandingkan SEKALI di luar loop: apakah tanggal absensi yang
        // baru disimpan ini sama dengan tanggal SEKARANG (server), atau
        // ini pengisian susulan untuk tanggal yang sudah lewat.
        $tanggalBukanHariIni = ! Carbon::parse($tanggal)->isToday();

        foreach ($siswaAlfaDiSesiIni as $siswaId) {
            $records = AbsensiSiswa::where('siswa_id', $siswaId)
                ->whereDate('tanggal', $tanggal)
                ->with(['jurnal.jamPelajaran', 'jurnal.jamPelajaranAkhir', 'jurnal.mapel'])
                ->get();

            $final = AbsensiSiswa::finalPerHari($records)->first();
            if (!$final || $final->status !== 'Alfa') {
                continue;
            }

            // Anti-duplikat: 1 siswa hanya diproses 1x per tanggal. Kalau
            // baris sudah ada (dibuat oleh penyimpanan sebelumnya hari ini),
            // wasRecentlyCreated = false, artinya sudah pernah diantrikan
            // (atau sudah sengaja dilewati) — tidak perlu diapa-apakan lagi.
            $baris = NotifikasiAlfaTerkirim::firstOrCreate(
                ['siswa_id' => $siswaId, 'tanggal' => $tanggal],
                [
                    'status_kirim' => $tanggalBukanHariIni ? 'dilewati' : 'pending',
                    'keterangan_gagal' => $tanggalBukanHariIni
                        ? 'Tidak dikirim: jurnal/absensi diisi terlambat (untuk tanggal '
                            .Carbon::parse($tanggal)->translatedFormat('d M Y')
                            .', bukan tanggal saat diisi). Kejadian Alfa tetap tercatat, hanya notifikasi WA yang sengaja dilewati.'
                        : null,
                    'mata_pelajaran_id' => $final->jurnal?->mata_pelajaran_id,
                    'jam_ke' => $final->jurnal?->jamPelajaranAkhir?->jam_ke ?? $final->jurnal?->jamPelajaran?->jam_ke,
                ]
            );

            if (!$baris->wasRecentlyCreated || $tanggalBukanHariIni) {
                continue;
            }

            KirimNotifikasiAlfaWhatsapp::dispatch(
                (int) $siswaId,
                $tanggal,
                $final->jurnal?->mapel?->nama_mapel,
                $final->jurnal?->jamPelajaranAkhir?->jam_ke ?? $final->jurnal?->jamPelajaran?->jam_ke,
            );
        }
    }

    /**
     * Ambil & validasi daftar JadwalPelajaran dari string id "12,13,14":
     * - semua id harus ada & milik guru yang login (kecuali admin)
     * - harus persis membentuk 1 sesi valid (kelas & mapel sama, jam berurutan)
     *   supaya URL tidak bisa "diakali" jadi gabungan sembarang jam.
     */
    private function resolveSesi(Request $request, string $ids)
    {
        $idArray = collect(explode(',', $ids))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        if ($idArray->isEmpty()) {
            abort(404);
        }

        $slotJadwal = JadwalPelajaran::with(['kelas', 'mapel', 'jamPelajaran'])
            ->whereIn('id', $idArray)
            ->get()
            ->sortBy(fn ($j) => $j->jamPelajaran->jam_ke)
            ->values();

        if ($slotJadwal->count() !== $idArray->count()) {
            abort(404, 'Sebagian jadwal tidak ditemukan.');
        }

        $user = $request->user();
        $guruId = $slotJadwal->first()->guru_id;
        if ($user->role !== 'admin' && $guruId !== $user->id) {
            abort(403, 'Jadwal ini bukan milik Anda.');
        }
        if ($slotJadwal->pluck('guru_id')->unique()->count() > 1) {
            abort(403, 'Sesi tidak valid.');
        }

        // Pastikan kombinasi id di URL memang 1 sesi utuh yang valid (bukan gabungan acak).
        $sesiTerhitung = SesiMengajarGrouper::kelompokkan(
            JadwalPelajaran::with(['kelas', 'mapel', 'jamPelajaran'])
                ->where('guru_id', $guruId)
                ->where('hari', $slotJadwal->first()->hari)
                ->where('tahun_ajaran_id', $slotJadwal->first()->tahun_ajaran_id)
                ->get()
        );
        $cocok = $sesiTerhitung->first(fn ($s) => $s['slots']->pluck('id')->sort()->values()
            ->all() === $slotJadwal->pluck('id')->sort()->values()->all());

        if (!$cocok) {
            abort(404, 'Sesi mengajar tidak valid.');
        }

        return $slotJadwal;
    }

    /**
     * Cari jurnal mengajar yang sudah pernah dibuat untuk sesi (kumpulan jam) &
     * tanggal ini. Dicari lewat 2 jalur supaya lebih tahan terhadap kondisi
     * data yang tidak ideal:
     * 1) Lewat tabel jurnal_mengajar_slots (jalur utama).
     * 2) Fallback: langsung ke jurnal_mengajars berdasarkan jadwal_pelajaran_id
     *    (slot AWAL sesi) + tanggal — jalur ini konsisten dengan bagaimana
     *    store() selalu mengisi kolom itu, jadi tetap ketemu meski baris di
     *    tabel slot untuk beberapa sebab tidak lengkap.
     */
    private function cariJurnalUntukSesi($slotJadwal, string $tanggal): ?JurnalMengajar
    {
        $slotId = JurnalMengajarSlot::whereIn('jadwal_pelajaran_id', $slotJadwal->pluck('id'))
            ->whereDate('tanggal', $tanggal)
            ->value('jurnal_mengajar_id');

        if ($slotId) {
            return JurnalMengajar::find($slotId);
        }

        return JurnalMengajar::where('jadwal_pelajaran_id', $slotJadwal->first()->id)
            ->whereDate('tanggal', $tanggal)
            ->first();
    }

    private function hariIniIndonesia(): string
    {
        $map = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'];
        return $map[now()->dayOfWeek] ?? 'Senin';
    }
}
