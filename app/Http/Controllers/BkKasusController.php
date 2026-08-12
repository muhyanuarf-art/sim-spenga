<?php

namespace App\Http\Controllers;

use App\Models\JenisPelanggaran;
use App\Models\KasusSiswa;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\PoinSiswaService;
use App\Support\BkAccessScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BkKasusController extends Controller
{
    use BkAccessScope;

    public function index(Request $request)
    {
        $user = $request->user();

        $query = KasusSiswa::with(['siswa', 'kelas', 'jenisPelanggaran', 'guruPelapor'])
            ->orderByDesc('tanggal_kejadian');

        if ($user->role === 'guru' && !$user->isWaliKelas() && !$user->isGuruBk()) {
            // guru mapel biasa: hanya lihat kasus yang dia laporkan sendiri
            $query->where('guru_pelapor_id', $user->id);
        } else {
            $kelasIds = $this->bkKelasIdsUntukUser($user);
            if ($kelasIds !== null) {
                $query->whereIn('kelas_id', $kelasIds);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        $data = $query->paginate(20)->withQueryString();

        $kelasList = in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah'])
            ? Kelas::orderBy('nama_kelas')->get()
            : ($user->role === 'guru_bk' ? $user->kelasBk() : collect());

        return view('bk.kasus.index', compact('data', 'kelasList'));
    }

    public function create(Request $request)
    {
        $jenisList = JenisPelanggaran::where('is_active', true)->orderBy('kategori')->orderBy('nama')->get();
        $rentangKategori = PoinSiswaService::RENTANG_KATEGORI;

        // Untuk pencarian siswa: guru mapel/wali kelas biasanya sudah tahu
        // siswanya, jadi cukup dropdown per kelas yang relevan.
        $kelasIds = $this->bkKelasIdsUntukUser($request->user());
        $siswaList = Siswa::with('kelas')->where('is_active', true)
            ->when($kelasIds !== null && $kelasIds !== [], fn ($q) => $q->whereIn('kelas_id', $kelasIds))
            ->orderBy('nama')->get();

        return view('bk.kasus.create', compact('jenisList', 'rentangKategori', 'siswaList'));
    }

    public function store(Request $request, PoinSiswaService $poinService)
    {
        $tahunAjaran = TahunAjaran::aktif();
        abort_if(!$tahunAjaran, 422, 'Tidak ada tahun ajaran aktif.');

        $validated = $request->validate([
            'siswa_id' => ['required', 'exists:siswas,id'],
            'tanggal_kejadian' => ['required', 'date', 'before_or_equal:today'],
            'jenis_pelanggaran_id' => ['nullable', 'exists:jenis_pelanggarans,id'],
            'nama_pelanggaran' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'in:Ringan,Sedang,Berat,Sangat Berat'],
            'poin' => ['required', 'integer', 'min:1', 'max:100'],
            'kronologi' => ['required', 'string'],
            'bukti_catatan' => ['nullable', 'string'],
        ]);

        if (!$poinService->validasiPoinSesuaiKategori($validated['kategori'], (int) $validated['poin'])) {
            [$min, $max] = PoinSiswaService::RENTANG_KATEGORI[$validated['kategori']];
            return back()->withInput()->withErrors([
                'poin' => "Poin untuk kategori {$validated['kategori']} harus antara {$min}-{$max} (bukan {$validated['poin']}).",
            ]);
        }

        $siswa = Siswa::findOrFail($validated['siswa_id']);

        KasusSiswa::create([
            ...$validated,
            'kelas_id' => $siswa->kelas_id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'guru_pelapor_id' => $request->user()->id,
            'status' => 'Baru',
        ]);

        return redirect()->route('bk.siswa.show', $siswa)
            ->with('success', "Kasus untuk {$siswa->nama} berhasil dicatat ({$validated['poin']} poin).");
    }

    /** Batalkan kasus yang salah input — TIDAK dihapus, hanya ditandai batal (Bagian 21 & 29 spec). */
    public function batalkan(Request $request, KasusSiswa $kasus)
    {
        $validated = $request->validate(['alasan_pembatalan' => ['required', 'string']]);

        abort_if($kasus->dibatalkan_at, 422, 'Kasus ini sudah dibatalkan sebelumnya.');

        DB::transaction(function () use ($kasus, $validated, $request) {
            $kasus->update([
                'dibatalkan_at' => now(),
                'dibatalkan_oleh_id' => $request->user()->id,
                'alasan_pembatalan' => $validated['alasan_pembatalan'],
            ]);
        });

        return back()->with('success', 'Kasus berhasil dibatalkan (riwayat tetap tersimpan, tidak dihapus).');
    }

    public function updateStatus(Request $request, KasusSiswa $kasus)
    {
        $validated = $request->validate(['status' => ['required', 'in:Baru,Diproses,Dalam Pembinaan,Selesai']]);
        $kasus->update($validated);
        return back()->with('success', 'Status kasus diperbarui.');
    }
}
