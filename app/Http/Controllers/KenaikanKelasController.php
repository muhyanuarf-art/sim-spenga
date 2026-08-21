<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\RiwayatKelasSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Support\PeriodeAkademik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KenaikanKelasController extends Controller
{
    /**
     * Form Kenaikan Kelas. Kalau kelas_asal_id sudah dipilih (query string),
     * langsung tampilkan daftar siswa aktif di kelas tsb supaya Kurikulum/
     * Admin bisa pilih siapa saja yang naik & kelas tujuannya sebelum
     * membuka modal preview (Alpine.js) dan submit.
     *
     * STEP 4 Bagian 19 — Tahun Ajaran TUJUAN TIDAK LAGI dipilih bebas oleh
     * admin lewat dropdown (celah lama). Sistem menghitungnya SENDIRI dari
     * Tahun Ajaran yang sedang AKTIF (mis. aktif 2026/2027 → tujuan otomatis
     * 2027/2028). Kalau tahun ajaran tujuan itu belum dibuat, form kenaikan
     * kelas disembunyikan dan admin diarahkan ke menu Tahun Ajaran dulu.
     */
    public function index(Request $request)
    {
        $kelasList = Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get();

        $periodeAktif = TahunAjaran::aktif();
        $namaTahunTujuan = $periodeAktif ? TahunAjaran::namaTahunAjaranBerikutnya($periodeAktif->nama) : null;
        $tahunAjaranTujuan = $namaTahunTujuan
            ? TahunAjaran::where('nama', $namaTahunTujuan)->where('semester', 'Ganjil')->first()
            : null;

        $kelasAsal = null;
        $siswas = collect();

        if ($request->filled('kelas_asal_id')) {
            $kelasAsal = Kelas::findOrFail($request->integer('kelas_asal_id'));
            $siswas = $kelasAsal->siswas()->where('is_active', true)->orderBy('nama')->get();
        }

        return view('kenaikan-kelas.index', compact(
            'kelasList', 'kelasAsal', 'siswas', 'periodeAktif', 'namaTahunTujuan', 'tahunAjaranTujuan'
        ));
    }

    /**
     * Proses kenaikan kelas untuk siswa terpilih. Siswa yang TIDAK dicentang
     * dianggap tinggal di kelas asal (tidak dicatat/tidak dipindah) — kalau
     * memang mau mencatat siswa yang TINGGAL KELAS secara eksplisit
     * (Bagian 12), jalankan proses ini SEKALI LAGI untuk kelas asal yang
     * sama dengan Kelas Tujuan = kelas yang sama persis (sekarang diizinkan
     * — lihat catatan di bawah).
     *
     * Tahun Ajaran tujuan DIHITUNG ULANG DI SERVER dari periode aktif SAAT
     * INI (bukan dipercaya dari input tersembunyi begitu saja) — pertahanan
     * terhadap kondisi periode aktif berubah di antara form dibuka & submit
     * (Bagian 19 & 26).
     *
     * unique(siswa_id, tahun_ajaran_id) di level database mencegah siswa
     * yang sama tercatat naik kelas dua kali pada tahun ajaran yang sama —
     * kalau itu terjadi baris tsb dilewati (skip) dan NAMANYA disebutkan di
     * pesan supaya admin sadar, bukan diam-diam dilewati (Bagian 11).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_asal_id' => ['required', 'exists:kelas,id'],
            // STEP 4 Bagian 12 — 'different:kelas_asal_id' SENGAJA DIHAPUS:
            // kelas tujuan BOLEH sama dengan kelas asal untuk mencatat
            // siswa yang TINGGAL KELAS (tetap di kelas yang sama, tahun
            // ajaran berbeda), bukan hanya untuk siswa yang naik/pindah.
            'kelas_tujuan_id' => ['required', 'exists:kelas,id'],
            'siswa_ids' => ['required', 'array', 'min:1'],
            'siswa_ids.*' => ['integer', 'exists:siswas,id'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $periodeAktif = TahunAjaran::aktif();
        $namaTahunTujuan = $periodeAktif ? TahunAjaran::namaTahunAjaranBerikutnya($periodeAktif->nama) : null;
        $tahunAjaran = $namaTahunTujuan
            ? TahunAjaran::where('nama', $namaTahunTujuan)->where('semester', 'Ganjil')->first()
            : null;

        if (! $tahunAjaran) {
            return back()->with('error', 'Tahun ajaran tujuan tidak dapat ditentukan (mungkin periode aktif sudah berubah sejak halaman ini dibuka). Muat ulang halaman dan coba lagi.');
        }

        // STEP 4 Bagian 8/26 — cek lock berdasarkan periode TUJUAN (bukan
        // periode aktif lama, lihat catatan di routes/web.php). Secara
        // praktis tahun ajaran baru hampir tidak pernah terkunci, tapi ini
        // jaring pengaman kalau admin membuka kunci lalu menutupnya lagi
        // di tengah proses, atau kasus tepi lainnya.
        PeriodeAkademik::pastikanTidakTerkunci($tahunAjaran);

        $kelasTujuan = Kelas::findOrFail($validated['kelas_tujuan_id']);

        $berhasil = 0;
        $dilewati = [];

        DB::transaction(function () use ($validated, $kelasTujuan, $tahunAjaran, &$berhasil, &$dilewati) {
            foreach ($validated['siswa_ids'] as $siswaId) {
                $sudahAda = RiwayatKelasSiswa::where('siswa_id', $siswaId)
                    ->where('tahun_ajaran_id', $tahunAjaran->id)
                    ->exists();

                $siswa = Siswa::findOrFail($siswaId);

                if ($sudahAda) {
                    $dilewati[] = $siswa->nama;
                    continue;
                }

                RiwayatKelasSiswa::create([
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'kelas_asal_id' => $validated['kelas_asal_id'],
                    'kelas_id' => $kelasTujuan->id,
                    'keterangan' => $validated['keterangan'] ?? null,
                    'dicatat_oleh_id' => auth()->id(),
                ]);

                $siswa->update(['kelas_id' => $kelasTujuan->id]);
                $berhasil++;
            }
        });

        $pesan = "{$berhasil} siswa berhasil dicatat ke kelas {$kelasTujuan->nama_kelas} untuk Tahun Ajaran {$tahunAjaran->nama}.";
        if (! empty($dilewati)) {
            $daftar = implode(', ', $dilewati);
            $pesan .= " " . count($dilewati) . " siswa DILEWATI karena sudah tercatat naik kelas pada tahun ajaran {$tahunAjaran->nama} sebelumnya: {$daftar}.";
        }

        return redirect()->route('kenaikan-kelas.index')->with($berhasil > 0 ? 'success' : 'error', $pesan);
    }

    /** Riwayat kelas seorang siswa, bernomor, urut dari periode paling awal. */
    public function riwayat(Siswa $siswa)
    {
        $riwayat = $siswa->riwayatKelas()->with(['tahunAjaran', 'kelasAsal', 'kelas', 'dicatatOleh'])->get();

        return view('kenaikan-kelas.riwayat', compact('siswa', 'riwayat'));
    }
}
