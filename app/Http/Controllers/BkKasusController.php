<?php

namespace App\Http\Controllers;

use App\Models\JenisPelanggaran;
use App\Models\KasusSiswa;
use App\Models\Kelas;
use App\Models\PembinaanSiswa;
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
        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal_kejadian', $request->bulan);
        }
        if ($request->filled('tahun')) {
            $query->whereYear('tanggal_kejadian', $request->tahun);
        }

        // Tanpa pagination — supaya tabel yang tampil di layar SAMA PERSIS
        // dengan yang dicetak/PDF-kan (1 tabel, bukan 2 versi terpisah),
        // sesuai konvensi halaman Rekapitulasi.
        $data = $query->get();

        $kelasList = in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah'])
            ? Kelas::orderBy('nama_kelas')->get()
            : ($user->role === 'guru_bk' ? $user->kelasBk() : collect());

        $guruBk = $this->bkGuruBkUntukCetak($user, $request->filled('kelas_id') ? (int) $request->kelas_id : null);

        return view('bk.kasus.index', compact('data', 'kelasList', 'guruBk'));
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
            // Jenis pelanggaran WAJIB dipilih dari master — Kategori & Poin
            // TIDAK diterima dari form sama sekali (lihat di bawah), supaya
            // tidak bisa diakali lewat DevTools/request manual. Keduanya
            // SELALU diambil ulang dari master berdasarkan jenis ini.
            'jenis_pelanggaran_id' => ['required', 'exists:jenis_pelanggarans,id'],
            'nama_pelanggaran' => ['required', 'string', 'max:255'],
            'kronologi' => ['required', 'string', 'min:10'],
            'bukti_catatan' => ['nullable', 'string'],
            'bukti_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'], // maks 5MB
        ]);

        $jenis = JenisPelanggaran::findOrFail($validated['jenis_pelanggaran_id']);
        $siswa = Siswa::findOrFail($validated['siswa_id']);
        $this->bkPastikanSiswaSesuaiCakupan($request->user(), $siswa);

        $buktiFilePath = null;
        if ($request->hasFile('bukti_file')) {
            $buktiFilePath = $request->file('bukti_file')->store('bk/bukti-pelanggaran', 'public');
        }

        KasusSiswa::create([
            'siswa_id' => $validated['siswa_id'],
            'kelas_id' => $siswa->kelas_id,
            'jenis_pelanggaran_id' => $jenis->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'tanggal_kejadian' => $validated['tanggal_kejadian'],
            'nama_pelanggaran' => $validated['nama_pelanggaran'],
            // Kategori & poin diambil LANGSUNG dari master (bukan dari input form).
            'kategori' => $jenis->kategori,
            'poin' => $jenis->poin_default,
            'kronologi' => $validated['kronologi'],
            'bukti_catatan' => $validated['bukti_catatan'] ?? null,
            'bukti_file' => $buktiFilePath,
            'guru_pelapor_id' => $request->user()->id,
            'status' => 'Baru',
        ]);

        return redirect()->route('bk.siswa.show', $siswa)
            ->with('success', "Kasus untuk {$siswa->nama} berhasil dicatat ({$jenis->poin_default} poin).");
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

        DB::transaction(function () use ($kasus, $validated) {
            $kasus->update($validated);

            // Integrasi otomatis (supaya pengguna tidak perlu update status di
            // 2 tempat terpisah): kalau kasus ditandai "Selesai", semua
            // pembinaan terkait yang masih berjalan ikut ditandai "Selesai"
            // juga. Poin siswa TIDAK terpengaruh sama sekali oleh status ini
            // — poin hanya berkurang lewat "Kurangi Poin" atau kalau kasus
            // dibatalkan (lihat PoinSiswaService::totalPelanggaran, yang
            // pakai scope aktif()/dibatalkan_at, bukan kolom status).
            if ($validated['status'] === 'Selesai') {
                PembinaanSiswa::where('kasus_siswa_id', $kasus->id)
                    ->where('status', '!=', 'Selesai')
                    ->update(['status' => 'Selesai']);
            }
        });

        return back()->with('success', 'Status kasus diperbarui.');
    }
}
