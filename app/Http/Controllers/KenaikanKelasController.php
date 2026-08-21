<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\RiwayatKelasSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Support\PeriodeAkademik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KenaikanKelasController extends Controller
{
    /**
     * Form Kenaikan Kelas.
     *
     * PERBAIKAN (setelah laporan admin): versi sebelumnya MEMAKSA "Tahun
     * Ajaran Asal" selalu = periode yang sedang aktif. Itu keliru kalau
     * admin sudah mengaktifkan tahun ajaran baru LEBIH DULU sebelum
     * memindahkan siswa — akibatnya "Kelas Asal" menunjuk ke kelas tahun
     * baru yang masih kosong (siswa belum ada di situ), dan siswa
     * kelihatan "hilang" padahal sebenarnya masih di tahun sebelumnya.
     *
     * Sekarang admin MEMILIH SENDIRI Tahun Ajaran Asal lewat dropdown
     * (default ditebak otomatis: tahun SEBELUM periode aktif kalau ada,
     * atau periode aktif itu sendiri kalau tidak — tapi admin bebas
     * mengganti). Tahun Ajaran TUJUAN tetap dihitung OTOMATIS dari Tahun
     * Ajaran Asal yang dipilih (Bagian 19 STEP 4 tetap dipegang — admin
     * tidak bisa asal pilih tujuan sembarangan), jadi berapa pun urutan
     * aktivasi yang sudah dilakukan admin, fitur ini tetap bekerja benar.
     */
    public function index(Request $request)
    {
        $periodeAktif = TahunAjaran::aktif();
        $tahunAjaranList = TahunAjaran::where('semester', 'Ganjil')->orderByDesc('id')->get();

        $tahunAjaranAsal = null;
        if ($request->filled('tahun_ajaran_asal_id')) {
            $tahunAjaranAsal = TahunAjaran::where('id', $request->integer('tahun_ajaran_asal_id'))
                ->where('semester', 'Ganjil')->first();
        }
        if (! $tahunAjaranAsal && $periodeAktif) {
            // Default cerdas: tebak tahun SEBELUM periode aktif dulu (kasus
            // paling umum kalau tahun baru sudah keburu diaktifkan) — kalau
            // tidak ada, baru jatuh ke periode aktif itu sendiri (kasus
            // kenaikan kelas dijalankan SEBELUM aktivasi, sesuai alur asli).
            $anchor = $periodeAktif->semester === 'Ganjil' ? $periodeAktif : TahunAjaran::where('nama', $periodeAktif->nama)->where('semester', 'Ganjil')->first();
            $tahunAjaranAsal = $anchor?->tahunAjaranSebelumnya() ?? $anchor;
        }

        $namaTahunTujuan = $tahunAjaranAsal ? TahunAjaran::namaTahunAjaranBerikutnya($tahunAjaranAsal->nama) : null;
        $tahunAjaranTujuan = $namaTahunTujuan
            ? TahunAjaran::where('nama', $namaTahunTujuan)->where('semester', 'Ganjil')->first()
            : null;

        // STEP 5 Bagian 20 — Kelas Asal HARUS dari Tahun Ajaran Asal yang
        // dipilih, Kelas Tujuan HARUS dari Tahun Ajaran Tujuan (dihitung
        // otomatis dari Tahun Ajaran Asal). Dua daftar terpisah supaya
        // admin tidak mungkin memilih kelas dari tahun ajaran yang salah.
        $kelasList = $tahunAjaranAsal
            ? Kelas::untukTahunAjaran($tahunAjaranAsal)->orderBy('tingkat')->orderBy('nama_kelas')->get()
            : collect();
        $kelasListTujuan = $tahunAjaranTujuan
            ? Kelas::untukTahunAjaran($tahunAjaranTujuan)->orderBy('tingkat')->orderBy('nama_kelas')->get()
            : collect();

        $kelasAsal = null;
        $siswas = collect();

        if ($request->filled('kelas_asal_id')) {
            $kelasAsal = Kelas::findOrFail($request->integer('kelas_asal_id'));
            $siswas = $kelasAsal->siswas()->where('is_active', true)->orderBy('nama')->get();
        }

        return view('kenaikan-kelas.index', compact(
            'kelasList', 'kelasListTujuan', 'kelasAsal', 'siswas',
            'periodeAktif', 'tahunAjaranList', 'tahunAjaranAsal', 'namaTahunTujuan', 'tahunAjaranTujuan'
        ));
    }

    /**
     * Proses kenaikan kelas untuk siswa terpilih. Siswa yang TIDAK dicentang
     * dianggap tinggal di kelas asal (tidak dicatat/tidak dipindah) — kalau
     * memang mau mencatat siswa yang TINGGAL KELAS secara eksplisit
     * (Bagian 12), jalankan proses ini SEKALI LAGI untuk kelas asal yang
     * sama dengan Kelas Tujuan = kelas yang sama persis (diizinkan, lihat
     * catatan di bawah).
     *
     * Tahun Ajaran Asal & Tujuan DIVALIDASI ULANG DI SERVER dari input
     * tahun_ajaran_asal_id (bukan dipercaya begitu saja dari kelas yang
     * dipilih) — pertahanan terhadap manipulasi/kondisi data berubah di
     * antara form dibuka & submit (Bagian 19 & 26).
     *
     * unique(siswa_id, tahun_ajaran_id) di level database mencegah siswa
     * yang sama tercatat naik kelas dua kali pada tahun ajaran yang sama —
     * kalau itu terjadi baris tsb dilewati (skip) dan NAMANYA disebutkan di
     * pesan supaya admin sadar, bukan diam-diam dilewati (Bagian 11).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tahun_ajaran_asal_id' => ['required', 'exists:tahun_ajarans,id'],
            'kelas_asal_id' => ['required', 'exists:kelas,id'],
            'kelas_tujuan_id' => ['required', 'exists:kelas,id'],
            'siswa_ids' => ['required', 'array', 'min:1'],
            'siswa_ids.*' => ['integer', 'exists:siswas,id'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $tahunAjaranAsal = TahunAjaran::findOrFail($validated['tahun_ajaran_asal_id']);
        $namaTahunTujuan = TahunAjaran::namaTahunAjaranBerikutnya($tahunAjaranAsal->nama);
        $tahunAjaran = $namaTahunTujuan
            ? TahunAjaran::where('nama', $namaTahunTujuan)->where('semester', 'Ganjil')->first()
            : null;

        if (! $tahunAjaran) {
            return back()->with('error', 'Tahun ajaran tujuan tidak dapat ditentukan. Muat ulang halaman dan coba lagi.');
        }

        // Validasi ulang kelas_asal_id & kelas_tujuan_id benar-benar dari
        // tahun ajaran yang sesuai (Bagian 20) — dicek manual di sini
        // (bukan lewat Rule::exists di atas) supaya pesan error bisa
        // menyebutkan nama tahun ajarannya dengan jelas.
        $kelasAsalValid = Kelas::untukTahunAjaran($tahunAjaranAsal)->pluck('id')->contains((int) $validated['kelas_asal_id']);
        if (! $kelasAsalValid) {
            return back()->with('error', "Kelas asal tidak valid atau bukan dari Tahun Ajaran {$tahunAjaranAsal->nama}.")->withInput();
        }

        $kelasTujuanValid = Kelas::untukTahunAjaran($tahunAjaran)->pluck('id')->contains((int) $validated['kelas_tujuan_id']);
        if (! $kelasTujuanValid) {
            return back()->with('error', "Kelas tujuan tidak valid atau bukan dari Tahun Ajaran {$tahunAjaran->nama}.")->withInput();
        }

        // STEP 5 Bagian 8/26 — cek lock berdasarkan periode TUJUAN (bukan
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

        return redirect()->route('kenaikan-kelas.index', ['tahun_ajaran_asal_id' => $tahunAjaranAsal->id])
            ->with($berhasil > 0 ? 'success' : 'error', $pesan);
    }

    /** Riwayat kelas seorang siswa, bernomor, urut dari periode paling awal. */
    public function riwayat(Siswa $siswa)
    {
        $riwayat = $siswa->riwayatKelas()->with(['tahunAjaran', 'kelasAsal', 'kelas', 'dicatatOleh'])->get();

        return view('kenaikan-kelas.riwayat', compact('siswa', 'riwayat'));
    }
}
