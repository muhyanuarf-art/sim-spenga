<?php

namespace App\Http\Controllers;

use App\Rules\DalamPeriode;
use App\Models\Kelas;
use App\Models\PenguranganPoinSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\PoinSiswaService;
use App\Support\BkAccessScope;
use App\Support\RentangBulan;
use App\Support\PeriodeAkademik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BkPenguranganPoinController extends Controller
{
    use BkAccessScope;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = PenguranganPoinSiswa::with(['siswa.kelas', 'petugas'])->orderByDesc('tanggal');

        $kelasIds = $this->bkKelasIdsUntukUser($user);
        if ($kelasIds !== null) {
            $query->whereHas('siswa', fn ($q) => $q->diKelasIn($kelasIds));
        }

        if ($request->filled('bulan') && $request->filled('tahun')) {
            [$awal, $akhir] = RentangBulan::dari((int) $request->tahun, (int) $request->bulan);
            $query->whereBetween('tanggal', [$awal, $akhir]);
        } elseif ($request->filled('bulan')) {
            $query->whereMonth('tanggal', $request->bulan);
        } elseif ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->tahun);
        }
        if ($request->filled('status')) {
            $request->status === 'Dibatalkan' ? $query->whereNotNull('dibatalkan_at') : $query->whereNull('dibatalkan_at');
        }
        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', fn ($q) => $q->diKelas($request->kelas_id));
        }

        $data = $query->paginate(20)->withQueryString();

        $kelasList = in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah'])
            ? Kelas::aktif()->orderBy('nama_kelas')->get()
            : ($user->role === 'guru_bk' ? $user->kelasBk() : collect());

        return view('bk.pengurangan.index', compact('data', 'kelasList'));
    }

    /**
     * Simpan pengurangan poin — WAJIB divalidasi terhadap poin aktif
     * TERKINI (dihitung ulang di server, bukan percaya angka dari form),
     * dan dibungkus DB transaction supaya tidak ada data setengah jadi
     * (Bagian 12 & 25 spec).
     */
    /**
     * Form catat pengurangan poin baru.
     *
     * Sama seperti Pembinaan: sebelumnya HANYA bisa lewat modal di halaman
     * Profil Perilaku Siswa (daftar ini bahkan menuliskan "buka profil siswa
     * terkait"), sehingga pencatatan BK tersebar di dua tempat. Sekarang
     * berpangkal dari Buku Catatan BK dengan langkah yang seragam.
     */
    public function create(Request $request, PoinSiswaService $poinService)
    {
        $user = $request->user();
        $kelasIds = $this->bkKelasIdsUntukUser($user);

        $siswaTerpilih = null;
        $hasilCari = collect();
        $ringkasan = null;

        if ($request->filled('siswa_id')) {
            $siswaTerpilih = Siswa::with('kelas')->find($request->get('siswa_id'));
            if ($siswaTerpilih) {
                $this->bkPastikanSiswaSesuaiCakupan($user, $siswaTerpilih);
                $ringkasan = $poinService->ringkasan($siswaTerpilih);
            }
        } elseif ($request->filled('cari')) {
            $hasilCari = Siswa::periodeAktif()->with('kelas')->where('is_active', true)
                ->when($kelasIds !== null, fn ($q) => $q->diKelasIn($kelasIds))
                ->where(function ($q) use ($request) {
                    $q->where('nama', 'like', "%{$request->cari}%")
                      ->orWhere('nis', 'like', "%{$request->cari}%");
                })
                ->orderBy('nama')->limit(20)->get();
        }

        return view('bk.pengurangan.create', compact('siswaTerpilih', 'hasilCari', 'ringkasan'));
    }

    public function store(Request $request, PoinSiswaService $poinService)
    {
        $tahunAjaran = TahunAjaran::aktif();
        abort_if(!$tahunAjaran, 422, 'Tidak ada tahun ajaran aktif.');

        $validated = $request->validate([
            'siswa_id' => ['required', 'exists:siswas,id'],
            'tanggal' => ['required', 'date', new DalamPeriode(sebutan: 'pengurangan poin')],
            'jumlah' => ['required', 'integer', 'min:1'],
            'alasan' => ['required', 'string'],
            'dasar_rekomendasi' => ['nullable', 'string'],
            'catatan' => ['nullable', 'string'],
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);
        $this->bkPastikanSiswaSesuaiCakupan($request->user(), $siswa);

        return DB::transaction(function () use ($validated, $siswa, $tahunAjaran, $request, $poinService) {
            // Kunci baris siswa ini selama transaksi (SELECT ... FOR UPDATE)
            // supaya request pengurangan poin lain untuk siswa yang sama
            // yang datang nyaris bersamaan HARUS menunggu transaksi ini
            // selesai (baru boleh baca poin aktif terkini), bukan sama-sama
            // membaca angka lama dan sama-sama lolos validasi saldo.
            $siswa = Siswa::where('id', $siswa->id)->lockForUpdate()->firstOrFail();

            $poinAktifTerkini = $poinService->poinAktif($siswa);

            if ($validated['jumlah'] > $poinAktifTerkini) {
                return back()->withInput()->withErrors([
                    'jumlah' => "Pengurangan ({$validated['jumlah']}) melebihi poin aktif siswa saat ini ({$poinAktifTerkini}). Transaksi ditolak.",
                ]);
            }

            PenguranganPoinSiswa::create([
                ...$validated,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'petugas_id' => $request->user()->id,
            ]);

            return redirect()->route('bk.siswa.show', $siswa)
                ->with('success', "Pengurangan {$validated['jumlah']} poin berhasil dicatat untuk {$siswa->nama}.");
        });
    }

    public function batalkan(Request $request, PenguranganPoinSiswa $pengurangan)
    {
        PeriodeAkademik::pastikanTidakTerkunci($pengurangan->tahunAjaran);

        $validated = $request->validate(['alasan_pembatalan' => ['required', 'string']]);
        abort_if($pengurangan->dibatalkan_at, 422, 'Transaksi ini sudah dibatalkan sebelumnya.');

        $pengurangan->update([
            'dibatalkan_at' => now(),
            'dibatalkan_oleh_id' => $request->user()->id,
            'alasan_pembatalan' => $validated['alasan_pembatalan'],
        ]);

        return back()->with('success', 'Transaksi pengurangan poin dibatalkan (riwayat tetap tersimpan).');
    }
}
