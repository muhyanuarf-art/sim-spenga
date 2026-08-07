<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSiswa;
use App\Models\JadwalPelajaran;
use App\Models\JurnalMengajar;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MengajarController extends Controller
{
    /**
     * Step 1: Guru memilih hari & melihat jadwal (kelas + jam ke-berapa) miliknya.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tahunAjaran = TahunAjaran::aktif();
        $hari = $request->get('hari', $this->hariIniIndonesia());

        $jadwal = collect();
        if ($tahunAjaran) {
            $jadwal = JadwalPelajaran::with(['kelas', 'mapel', 'jamPelajaran'])
                ->where('guru_id', $user->id)
                ->where('tahun_ajaran_id', $tahunAjaran->id)
                ->where('hari', $hari)
                ->orderBy('jam_pelajaran_id')
                ->get();

            // tandai slot yang sudah diisi jurnalnya hari ini (jika hari = hari ini)
            if ($hari === $this->hariIniIndonesia()) {
                $sudahDiisi = JurnalMengajar::where('guru_id', $user->id)
                    ->whereDate('tanggal', now()->toDateString())
                    ->pluck('jadwal_pelajaran_id')
                    ->toArray();
                $jadwal->each(function ($j) use ($sudahDiisi) {
                    $j->sudah_diisi = in_array($j->id, $sudahDiisi);
                });
            }
        }

        $hariList = JadwalPelajaran::HARI_LIST();

        return view('absensi.pilih-kelas', compact('jadwal', 'hari', 'hariList', 'tahunAjaran'));
    }

    /**
     * Step 2: Form isi jurnal mengajar + absensi siswa untuk 1 slot jadwal.
     */
    public function form(Request $request, JadwalPelajaran $jadwal)
    {
        $this->authorizeJadwal($request, $jadwal);

        $tanggal = $request->get('tanggal', now()->toDateString());

        $jurnal = JurnalMengajar::with('absensi.siswa')
            ->where('jadwal_pelajaran_id', $jadwal->id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        $siswas = $jadwal->kelas->siswas()->where('is_active', true)->orderBy('nama')->get();

        $absensiTersimpan = [];
        if ($jurnal) {
            foreach ($jurnal->absensi as $a) {
                $absensiTersimpan[$a->siswa_id] = $a->status;
            }
        }

        return view('absensi.form', compact('jadwal', 'jurnal', 'siswas', 'absensiTersimpan', 'tanggal'));
    }

    /**
     * Simpan Jurnal Mengajar + Absensi Siswa sekaligus (langsung terintegrasi
     * ke Jurnal Kelas & Absensi Kelas milik Wali Kelas, serta monitoring Kurikulum).
     */
    public function store(Request $request, JadwalPelajaran $jadwal)
    {
        $this->authorizeJadwal($request, $jadwal);

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'materi' => ['required', 'string'],
            'kegiatan' => ['nullable', 'string'],
            'keterangan' => ['nullable', 'string'],
            'absensi' => ['required', 'array'],
            'absensi.*' => ['required', 'in:Hadir,Sakit,Izin,Alfa'],
        ]);

        DB::transaction(function () use ($validated, $jadwal, $request) {
            $jurnal = JurnalMengajar::updateOrCreate(
                [
                    'jadwal_pelajaran_id' => $jadwal->id,
                    'tanggal' => $validated['tanggal'],
                ],
                [
                    'guru_id' => $jadwal->guru_id,
                    'kelas_id' => $jadwal->kelas_id,
                    'mata_pelajaran_id' => $jadwal->mata_pelajaran_id,
                    'jam_pelajaran_id' => $jadwal->jam_pelajaran_id,
                    'materi' => $validated['materi'],
                    'kegiatan' => $validated['kegiatan'] ?? null,
                    'keterangan' => $validated['keterangan'] ?? null,
                ]
            );

            $rekap = ['Hadir' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];

            foreach ($validated['absensi'] as $siswaId => $status) {
                AbsensiSiswa::updateOrCreate(
                    [
                        'jurnal_mengajar_id' => $jurnal->id,
                        'siswa_id' => $siswaId,
                    ],
                    [
                        'kelas_id' => $jadwal->kelas_id,
                        'tanggal' => $validated['tanggal'],
                        'status' => $status,
                    ]
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

        return redirect()->route('mengajar.index')
            ->with('success', "Absensi & Jurnal untuk kelas {$jadwal->kelas->nama_kelas} berhasil disimpan.");
    }

    private function authorizeJadwal(Request $request, JadwalPelajaran $jadwal): void
    {
        $user = $request->user();
        if ($user->role !== 'admin' && $jadwal->guru_id !== $user->id) {
            abort(403, 'Jadwal ini bukan milik Anda.');
        }
    }

    private function hariIniIndonesia(): string
    {
        $map = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'];
        return $map[now()->dayOfWeek] ?? 'Senin';
    }
}
