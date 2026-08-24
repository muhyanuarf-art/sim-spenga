<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSiswa;
use App\Models\Kelas;
use App\Models\JurnalMengajar;
use App\Models\Siswa;
use App\Support\RentangBulan;
use Illuminate\Http\Request;

class WaliKelasController extends Controller
{
    /**
     * Rekap absensi bulanan 1 lembar: NIS, Nama, Tanggal 1-31, Sakit, Izin, Alfa, Jumlah.
     * Bisa dipilih bulan berapapun sepanjang tahun ajaran berjalan.
     *
     * Dipakai oleh 4 kelompok pengguna:
     * - Admin/Kurikulum/Kepala Sekolah/Kesiswaan: bebas pilih kelas manapun.
     * - Guru (Wali Kelas): terkunci ke 1 kelas walinya sendiri.
     * - Guru BK: bebas pilih di antara kelas-kelas yang di-mapping-kan
     *   kepadanya (lihat menu Mapping Guru BK oleh Kurikulum/Admin).
     */
    public function absensiBulanan(Request $request, ?Kelas $kelas = null)
    {
        $user = $request->user();
        $kelas = $kelas ?? $this->resolveKelasDefault($user);
        $daftarKelas = $this->resolveDaftarKelasPilihan($user);

        if (in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah', 'kesiswaan'])) {
            $kelasId = $request->get('kelas_id', $kelas?->id);
            $kelas = Kelas::findOrFail($kelasId);
        } elseif ($user->role === 'guru_bk') {
            $kelas = $this->resolveKelasBkDipilih($request, $user, $kelas);
        } else {
            $this->authorizeWali($user, $kelas);
        }

        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);
        $jumlahHari = \Carbon\Carbon::create($tahun, $bulan, 1)->daysInMonth;

        // Eager-load info jam (awal & akhir sesi) tiap jurnal, supaya bisa
        // menentukan "sesi mana yang jam-nya paling akhir pada hari itu"
        // tanpa query tambahan per baris.
        $absensiRaw = AbsensiSiswa::where('kelas_id', $kelas->id)
            ->whereBetween('tanggal', RentangBulan::dari($tahun, $bulan))
            ->with(['jurnal.jamPelajaran', 'jurnal.jamPelajaranAkhir', 'jurnal.mapel'])
            ->get()
            ->groupBy('siswa_id');

        // (2026-08-23) — PERBAIKAN BUG "siswa pindah kelas hilang dari
        // laporan bulan lama". absensi_siswas.kelas_id adalah SNAPSHOT
        // permanen (dicatat saat itu juga), bukan sekadar mengikuti
        // siswa.kelas_id yang sekarang. Kalau daftar siswa di sini hanya
        // diambil dari kelas siswa SAAT INI ($kelas->siswas()), siswa yang
        // sudah pindah ke kelas lain tidak akan muncul lagi untuk bulan-
        // bulan sebelum ia pindah — padahal datanya masih ada di database.
        //
        // Solusinya: gabungkan (a) siapa saja yang PERNAH tercatat di kelas
        // ini pada bulan yang dilihat (dari $absensiRaw, sumbernya snapshot
        // kelas_id) dengan (b) siswa yang SEKARANG terdaftar di kelas ini
        // (supaya kelas tetap tampil lengkap untuk bulan berjalan/bulan
        // depan sebelum ada absensi sama sekali yang diinput).
        $siswaIdHistoris = $absensiRaw->keys();
        $siswaIdSekarang = $kelas->siswas()->where('is_active', true)->pluck('id');
        $siswas = Siswa::whereIn('id', $siswaIdHistoris->merge($siswaIdSekarang)->unique())
            ->orderBy('nama')
            ->get();

        $rekap = $siswas->map(function ($siswa) use ($absensiRaw, $jumlahHari) {
            $data = array_fill(1, $jumlahHari, '');
            $keterangan = array_fill(1, $jumlahHari, '');
            $hadir = $sakit = $izin = $alfa = 0;

            $records = $absensiRaw->get($siswa->id, collect());

            // Absensi Kelas mengikuti aturan: kalau siswa tercatat di lebih dari
            // 1 mapel pada hari yang sama, status dari GURU MAPEL DENGAN JAM
            // PALING AKHIR pada hari itu yang dipakai (lihat AbsensiSiswa::finalPerHari).
            // Laporan per guru mapel sendiri tetap utuh (lihat LaporanGuruController),
            // ini hanya memengaruhi rekap kelas.
            foreach (AbsensiSiswa::finalPerHari($records) as $final) {
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
                if ($final->status === 'Hadir') $hadir++;
            }

            return [
                'siswa' => $siswa,
                'harian' => $data,
                'keterangan' => $keterangan,
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alfa' => $alfa,
                'jumlah' => $sakit + $izin + $alfa,
            ];
        });

        return view('walikelas.absensi-bulanan', compact('kelas', 'rekap', 'bulan', 'tahun', 'jumlahHari', 'daftarKelas'));
    }

    /**
     * Monitoring Jurnal Mengajar untuk 1 kelas (wali kelas / BK / admin-kurikulum-kepsek).
     */
    public function jurnalKelas(Request $request, ?Kelas $kelas = null)
    {
        $user = $request->user();
        $kelas = $kelas ?? $this->resolveKelasDefault($user);
        $daftarKelas = $this->resolveDaftarKelasPilihan($user);

        if (in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah'])) {
            $kelasId = $request->get('kelas_id', $kelas?->id);
            $kelas = Kelas::findOrFail($kelasId);
        } elseif ($user->role === 'guru_bk') {
            $kelas = $this->resolveKelasBkDipilih($request, $user, $kelas);
        } else {
            $this->authorizeWali($user, $kelas);
        }

        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $jurnal = JurnalMengajar::with(['guru', 'mapel', 'jamPelajaran'])
            ->where('kelas_id', $kelas->id)
            ->whereBetween('tanggal', RentangBulan::dari($tahun, $bulan))
            ->orderBy('tanggal')
            ->orderBy('jam_pelajaran_id')
            ->get();

        return view('walikelas.jurnal-kelas', compact('kelas', 'jurnal', 'bulan', 'tahun', 'daftarKelas'));
    }

    private function resolveKelasWali($user): ?Kelas
    {
        // STEP 5 — pakai relasi yang sudah ter-scope ke tahun ajaran aktif
        // (lihat User::kelasWali()), bukan query manual di sini lagi.
        return $user->kelasWali;
    }

    /** Kelas default yang ditampilkan pertama kali (sebelum user memilih lewat dropdown/URL). */
    private function resolveKelasDefault($user): ?Kelas
    {
        if ($user->role === 'guru_bk') {
            return $user->kelasBk()->first();
        }
        if (in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah', 'kesiswaan'])) {
            return Kelas::aktif()->orderBy('nama_kelas')->first();
        }
        return $this->resolveKelasWali($user);
    }

    /** Daftar kelas yang boleh dipilih lewat dropdown (beda cakupan per role). */
    private function resolveDaftarKelasPilihan($user)
    {
        if ($user->role === 'guru_bk') {
            return $user->kelasBk();
        }
        if (in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah', 'kesiswaan'])) {
            // STEP 5 Bagian 23 — default TAHUN AJARAN AKTIF (halaman ini
            // untuk operasional harian, bukan histori).
            return Kelas::aktif()->orderBy('nama_kelas')->get();
        }
        return collect(); // wali kelas: terkunci ke 1 kelas, dropdown tidak dipakai
    }

    /** Validasi & tentukan kelas yang dipilih Guru BK, harus salah satu dari kelas mapping-nya. */
    private function resolveKelasBkDipilih(Request $request, $user, ?Kelas $kelasDefault): Kelas
    {
        $kelasBkIds = $user->kelasBk()->pluck('id');
        abort_if($kelasBkIds->isEmpty(), 403, 'Anda belum di-mapping ke kelas manapun. Hubungi Kurikulum/Admin.');

        $kelasId = $request->get('kelas_id', $kelasDefault?->id ?? $kelasBkIds->first());
        abort_unless($kelasBkIds->contains((int) $kelasId), 403, 'Anda tidak memiliki akses ke kelas ini.');

        return Kelas::findOrFail($kelasId);
    }

    private function authorizeWali($user, ?Kelas $kelas): void
    {
        if (! $kelas || $kelas->wali_kelas_id !== $user->id) {
            abort(403, 'Anda bukan Wali Kelas untuk kelas ini.');
        }
    }
}
