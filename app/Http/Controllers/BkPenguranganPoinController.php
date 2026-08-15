<?php

namespace App\Http\Controllers;

use App\Models\PenguranganPoinSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\PoinSiswaService;
use App\Support\BkAccessScope;
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
            $query->whereHas('siswa', fn ($q) => $q->whereIn('kelas_id', $kelasIds));
        }

        $data = $query->paginate(20)->withQueryString();
        return view('bk.pengurangan.index', compact('data'));
    }

    /**
     * Simpan pengurangan poin — WAJIB divalidasi terhadap poin aktif
     * TERKINI (dihitung ulang di server, bukan percaya angka dari form),
     * dan dibungkus DB transaction supaya tidak ada data setengah jadi
     * (Bagian 12 & 25 spec).
     */
    public function store(Request $request, PoinSiswaService $poinService)
    {
        $tahunAjaran = TahunAjaran::aktif();
        abort_if(!$tahunAjaran, 422, 'Tidak ada tahun ajaran aktif.');

        $validated = $request->validate([
            'siswa_id' => ['required', 'exists:siswas,id'],
            'tanggal' => ['required', 'date'],
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
