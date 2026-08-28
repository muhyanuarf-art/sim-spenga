<?php

namespace App\Http\Controllers;

use App\Models\EvaluasiPembinaan;
use App\Models\KasusSiswa;
use App\Models\Kelas;
use App\Models\PembinaanSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\PoinSiswaService;
use App\Support\BkAccessScope;
use App\Support\RentangBulan;
use App\Support\PeriodeAkademik;
use Illuminate\Http\Request;

class BkPembinaanController extends Controller
{
    use BkAccessScope;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = PembinaanSiswa::with(['siswa.kelas', 'petugas', 'kasus'])->orderByDesc('tanggal');

        $kelasIds = $this->bkKelasIdsUntukUser($user);
        if ($kelasIds !== null) {
            $query->whereHas('siswa', fn ($q) => $q->whereIn('kelas_id', $kelasIds));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $request->kelas_id));
        }
        if ($request->filled('bulan') && $request->filled('tahun')) {
            [$awal, $akhir] = RentangBulan::dari((int) $request->tahun, (int) $request->bulan);
            $query->whereBetween('tanggal', [$awal, $akhir]);
        } elseif ($request->filled('bulan')) {
            $query->whereMonth('tanggal', $request->bulan);
        } elseif ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->tahun);
        }

        // Tanpa pagination — 1 tabel dipakai untuk tampilan layar sekaligus
        // cetak/PDF, sesuai konvensi halaman Rekapitulasi.
        $data = $query->get();

        $kelasList = in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah'])
            ? Kelas::aktif()->orderBy('nama_kelas')->get()
            : ($user->role === 'guru_bk' ? $user->kelasBk() : collect());

        $guruBk = $this->bkGuruBkUntukCetak($user, $request->filled('kelas_id') ? (int) $request->kelas_id : null);

        return view('bk.pembinaan.index', compact('data', 'kelasList', 'guruBk'));
    }

    /**
     * Form catat pembinaan baru.
     *
     * Sebelumnya pembinaan HANYA bisa dicatat lewat modal di halaman Profil
     * Perilaku Siswa — halaman profil jadi menanggung tugas pencatatan
     * sekaligus penyajian riwayat, dan pengguna kebingungan karena tombol
     * pencatatan tersebar di dua tempat. Sekarang seluruh pencatatan BK
     * berpangkal dari Buku Catatan BK, dengan langkah yang seragam:
     * cari siswa dulu, baru isi formulirnya.
     */
    public function create(Request $request, PoinSiswaService $poinService)
    {
        $user = $request->user();
        $kelasIds = $this->bkKelasIdsUntukUser($user);

        $siswaTerpilih = null;
        $hasilCari = collect();
        $kasusAktifTerbuka = collect();
        $ringkasan = null;

        if ($request->filled('siswa_id')) {
            $siswaTerpilih = Siswa::with('kelas')->find($request->get('siswa_id'));
            if ($siswaTerpilih) {
                $this->bkPastikanSiswaSesuaiCakupan($user, $siswaTerpilih);

                $kasusAktifTerbuka = KasusSiswa::aktif()
                    ->where('siswa_id', $siswaTerpilih->id)
                    ->where('status', '!=', KasusSiswa::STATUS_SELESAI)
                    ->orderByDesc('tanggal_kejadian')->get();

                $ringkasan = $poinService->ringkasan($siswaTerpilih);
            }
        } elseif ($request->filled('cari')) {
            $hasilCari = Siswa::with('kelas')->where('is_active', true)
                ->when($kelasIds !== null, fn ($q) => $q->whereIn('kelas_id', $kelasIds))
                ->where(function ($q) use ($request) {
                    $q->where('nama', 'like', "%{$request->cari}%")
                      ->orWhere('nis', 'like', "%{$request->cari}%");
                })
                ->orderBy('nama')->limit(20)->get();
        }

        return view('bk.pembinaan.create', compact('siswaTerpilih', 'hasilCari', 'kasusAktifTerbuka', 'ringkasan'));
    }

    public function store(Request $request, PoinSiswaService $poinService)
    {
        $tahunAjaran = TahunAjaran::aktif();
        abort_if(!$tahunAjaran, 422, 'Tidak ada tahun ajaran aktif.');

        $validated = $request->validate([
            'siswa_id' => ['required', 'exists:siswas,id'],
            'kasus_siswa_id' => ['nullable', 'exists:kasus_siswas,id'],
            'tanggal' => ['required', 'date'],
            // Tahap TIDAK diterima dari form — selalu dihitung otomatis di
            // bawah dari poin aktif siswa (App\Services\PoinSiswaService).
            'jenis_pembinaan' => ['required', 'string'],
            'catatan_bk' => ['required', 'string'],
            'status' => ['required', 'in:Pembinaan,Selesai'],
            'hasil_pembinaan' => ['nullable', 'string'],
            'bukti_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'], // maks 5MB
            'tanggal_evaluasi_berikutnya' => ['nullable', 'date'],
            'ruang_refleksi_selesai' => ['nullable', 'date'],
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);
        $this->bkPastikanSiswaSesuaiCakupan($request->user(), $siswa);
        // Tahap otomatis dari sistem, berdasarkan poin aktif TERKINI siswa.
        // Minimal Tahap 1 kalau poin aktif belum masuk rentang manapun.
        $tahap = $poinService->rekomendasiTahap($poinService->poinAktif($siswa)) ?? 1;

        if ($request->hasFile('bukti_file')) {
            $validated['bukti_file'] = $request->file('bukti_file')->store('bk/bukti-pembinaan', 'public');
        }

        $pembinaan = PembinaanSiswa::create([
            ...$validated,
            'tahap' => $tahap,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'petugas_id' => $request->user()->id,
        ]);

        // Kasus terkait ikut ter-update statusnya: kalau pembinaan ini
        // langsung dicatat "Selesai", kasus juga langsung "Selesai" (supaya
        // hilang dari daftar "Kasus Belum Selesai"). Kalau masih berjalan,
        // kasus jadi "Dalam Pembinaan".
        if ($pembinaan->kasus_siswa_id) {
            KasusSiswa::where('id', $pembinaan->kasus_siswa_id)
                ->where('status', '!=', 'Selesai')
                ->update(['status' => $pembinaan->status === 'Selesai' ? 'Selesai' : 'Dalam Pembinaan']);
        }

        return redirect()->route('bk.siswa.show', $pembinaan->siswa_id)
            ->with('success', 'Pembinaan berhasil dicatat.');
    }

    public function update(Request $request, PembinaanSiswa $pembinaan)
    {
        PeriodeAkademik::pastikanTidakTerkunci($pembinaan->tahunAjaran);

        $validated = $request->validate([
            'status' => ['required', 'in:Pembinaan,Selesai'],
            // Hasil pembinaan sengaja dibuat opsional (bukan wajib) supaya
            // status bisa langsung ditandai "Selesai" dengan 1 klik dari
            // halaman Profil Perilaku Siswa — hasilnya boleh dilengkapi
            // belakangan.
            'hasil_pembinaan' => ['nullable', 'string'],
            'tanggal_evaluasi_berikutnya' => ['nullable', 'date'],
        ]);

        $pembinaan->update($validated);

        // Kasus terkait ikut mengikuti, DUA ARAH — supaya pengguna tidak
        // pernah perlu mengubah status di dua tempat terpisah:
        //
        // - Ditandai "Selesai"  → kasusnya ikut "Selesai" (hilang dari
        //   daftar Kasus Belum Selesai di Ringkasan BK).
        // - Dibuka kembali      → kasusnya ikut dibuka lagi menjadi
        //   "Dalam Pembinaan". Sebelumnya arah ini TIDAK ditangani, jadi
        //   pembinaan bisa berstatus belum selesai sementara kasusnya
        //   masih tertulis "Selesai" — dua halaman menampilkan hal yang
        //   saling bertentangan.
        if ($pembinaan->kasus_siswa_id) {
            $kasus = KasusSiswa::find($pembinaan->kasus_siswa_id);

            if ($kasus) {
                $kasus->update([
                    'status' => $pembinaan->isSelesai()
                        ? KasusSiswa::STATUS_SELESAI
                        : KasusSiswa::STATUS_DALAM_PEMBINAAN,
                ]);
            }
        }

        return back()->with('success', $pembinaan->isSelesai()
            ? 'Pembinaan ditandai selesai.'
            : 'Pembinaan dibuka kembali.');
    }

    /** Catat evaluasi harian untuk pembinaan jenis "Ruang refleksi" (maks 7 hari). */
    public function storeEvaluasiHarian(Request $request, PembinaanSiswa $pembinaan)
    {
        PeriodeAkademik::pastikanTidakTerkunci($pembinaan->tahunAjaran);

        $validated = $request->validate([
            'hari_ke' => ['required', 'integer', 'min:1', 'max:7'],
            'tanggal' => ['required', 'date'],
            'kondisi' => ['required', 'in:Baik,Perlu Perhatian,Kurang Baik'],
            'catatan' => ['required', 'string'],
        ]);

        EvaluasiPembinaan::updateOrCreate(
            ['pembinaan_siswa_id' => $pembinaan->id, 'hari_ke' => $validated['hari_ke']],
            [...$validated, 'petugas_id' => $request->user()->id]
        );

        return back()->with('success', "Evaluasi hari ke-{$validated['hari_ke']} berhasil disimpan.");
    }
}
