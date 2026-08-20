<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSiswa;
use App\Models\JadwalPelajaran;
use App\Models\JurnalMengajar;
use App\Models\JurnalMengajarSlot;
use App\Models\NotifikasiAlfaTerkirim;
use App\Models\TahunAjaran;
use App\Support\PeriodeAkademik;
use App\Support\SesiMengajarGrouper;
use App\Jobs\KirimNotifikasiAlfaWhatsapp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MengajarController extends Controller
{
    /**
     * Step 1: Guru memilih hari & melihat jadwal miliknya, sudah dikelompokkan
     * per SESI mengajar (jam-jam berurutan dengan kelas & mapel yang sama
     * digabung jadi 1 kartu, bukan 1 kartu per jam).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tahunAjaran = TahunAjaran::aktif();
        $hari = $request->get('hari', $this->hariIniIndonesia());

        $sesiList = collect();
        if ($tahunAjaran) {
            $jadwal = JadwalPelajaran::with(['kelas', 'mapel', 'jamPelajaran'])
                ->where('guru_id', $user->id)
                ->where('tahun_ajaran_id', $tahunAjaran->id)
                ->where('hari', $hari)
                ->get();

            $sesiList = SesiMengajarGrouper::kelompokkan($jadwal);

            // tandai sesi yang sudah diisi jurnalnya hari ini (jika hari = hari ini)
            if ($hari === $this->hariIniIndonesia()) {
                $sesiList = SesiMengajarGrouper::tandaiSudahDiisi($sesiList, $jadwal);
            }
        }

        $hariList = JadwalPelajaran::HARI_LIST();

        return view('absensi.pilih-kelas', compact('sesiList', 'hari', 'hariList', 'tahunAjaran'));
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
        $siswas = $jadwalAwal->kelas->siswas()->where('is_active', true)->orderBy('nama')->get();

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

        $siswaIdsKelas = $jadwalAwal->kelas->siswas()->where('is_active', true)->pluck('id');
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
            // wasRecentlyCreated = false, artinya sudah pernah diantrikan.
            $baris = NotifikasiAlfaTerkirim::firstOrCreate(
                ['siswa_id' => $siswaId, 'tanggal' => $tanggal],
                [
                    'status_kirim' => 'pending',
                    'mata_pelajaran_id' => $final->jurnal?->mata_pelajaran_id,
                    'jam_ke' => $final->jurnal?->jamPelajaranAkhir?->jam_ke ?? $final->jurnal?->jamPelajaran?->jam_ke,
                ]
            );

            if (!$baris->wasRecentlyCreated) {
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
