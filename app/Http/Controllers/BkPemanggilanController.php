<?php

namespace App\Http\Controllers;

use App\Models\JenisSurat;
use App\Models\KasusSiswa;
use App\Models\Kelas;
use App\Models\PemanggilanOrangTua;
use App\Models\Siswa;
use App\Models\Surat;
use App\Models\TahunAjaran;
use App\Support\BkAccessScope;
use App\Support\NomorSuratBk;
use App\Support\RentangBulan;
use App\Support\SuratMerge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * (2026-08-26) — 2 tahap TERPISAH WAKTU, sesuai alur sebenarnya:
 *   1. create()/store() — BUAT PEMANGGILAN: surat dikirim, pertemuan
 *      BELUM terjadi. TIDAK ada field hasil pertemuan di sini sama sekali.
 *   2. editHasil()/updateHasil() — ISI HASIL PERTEMUAN: dilakukan
 *      BELAKANGAN (bisa besok, minggu depan) setelah pertemuan BENAR-BENAR
 *      berlangsung — mengisi hadir/tidak, hasil, kesepakatan.
 * Sebelumnya keduanya digabung 1 form yang minta hasil pertemuan padahal
 * pertemuannya sendiri belum terjadi — sudah diperbaiki di sini.
 */
class BkPemanggilanController extends Controller
{
    use BkAccessScope;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = PemanggilanOrangTua::with(['siswa.kelas', 'petugas', 'surat'])->orderByDesc('tanggal');

        $kelasIds = $this->bkKelasIdsUntukUser($user);
        if ($kelasIds !== null) {
            $query->whereHas('siswa', fn ($q) => $q->whereIn('kelas_id', $kelasIds));
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
            if (in_array($request->status, ['Hadir', 'Tidak Hadir'])) {
                $query->where('ortu_hadir', $request->status === 'Hadir' ? 1 : 0);
            } elseif ($request->status === 'Menunggu Pertemuan') {
                $query->where('status', PemanggilanOrangTua::STATUS_MENUNGGU_PERTEMUAN);
            }
        }
        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $request->kelas_id));
        }

        // Tanpa pagination — 1 tabel dipakai untuk tampilan layar sekaligus
        // cetak/PDF, sesuai konvensi halaman Rekapitulasi.
        $data = $query->get();

        $kelasList = in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah'])
            ? Kelas::aktif()->orderBy('nama_kelas')->get()
            : ($user->role === 'guru_bk' ? $user->kelasBk() : collect());

        $guruBk = $this->bkGuruBkUntukCetak($user, $request->filled('kelas_id') ? (int) $request->kelas_id : null);

        return view('bk.pemanggilan.index', compact('data', 'kelasList', 'guruBk'));
    }

    /**
     * TAHAP 1 — Buat Pemanggilan (surat dikirim, pertemuan belum terjadi).
     * 1 halaman: cari & pilih siswa (query string, pola sama dengan Buat
     * Surat) → begitu siswa terpilih, tampil 1 form (data pemanggilan +
     * pilihan surat, TANPA field hasil pertemuan) → Simpan.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $kelasIds = $this->bkKelasIdsUntukUser($user);

        $siswaTerpilih = null;
        $hasilCari = collect();
        if ($request->filled('siswa_id')) {
            $siswaTerpilih = Siswa::with('kelas')->find($request->get('siswa_id'));
            if ($siswaTerpilih) {
                $this->bkPastikanSiswaSesuaiCakupan($user, $siswaTerpilih);
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

        $kasusAktifTerbuka = collect();
        $suratPanggilanList = collect();
        $jenisSuratPanggilan = null;
        $isiSuratPreview = null;
        $tanggal = $request->get('tanggal', now()->toDateString());
        $tanggalAcara = $request->get('tanggal_acara', '');
        $waktuAcara = $request->get('waktu_acara', '');
        $nomorPratinjau = NomorSuratBk::pratinjau($tanggal);

        if ($siswaTerpilih) {
            $kasusAktifTerbuka = KasusSiswa::aktif()->where('siswa_id', $siswaTerpilih->id)
                ->whereNotIn('status', ['Selesai'])->orderByDesc('tanggal_kejadian')->get();

            // Dicocokkan lewat kode_jenis 'SP' ATAU nama yang mengandung
            // "panggilan" — tetap ketemu meski kode_jenis diisi manual beda.
            $jenisSuratPanggilan = JenisSurat::where('kode_jenis', 'SP')
                ->orWhere('nama_jenis', 'like', '%panggilan%')
                ->first();

            if ($jenisSuratPanggilan) {
                $suratPanggilanList = Surat::where('siswa_id', $siswaTerpilih->id)
                    ->where('jenis_surat_id', $jenisSuratPanggilan->id)
                    ->orderByDesc('tanggal')->get();

                $isiSuratPreview = SuratMerge::isi(
                    $jenisSuratPanggilan->template_isi ?? '', $siswaTerpilih, $tanggal, $nomorPratinjau, $tanggalAcara, $waktuAcara
                );
            }
        }

        return view('bk.pemanggilan.create', compact(
            'siswaTerpilih', 'hasilCari', 'kasusAktifTerbuka', 'jenisSuratPanggilan',
            'suratPanggilanList', 'tanggal', 'tanggalAcara', 'waktuAcara', 'isiSuratPreview', 'nomorPratinjau'
        ));
    }

    public function store(Request $request)
    {
        $tahunAjaran = TahunAjaran::aktif();
        abort_if(!$tahunAjaran, 422, 'Tidak ada tahun ajaran aktif.');

        $validated = $request->validate([
            'siswa_id' => ['required', 'exists:siswas,id'],
            'kasus_siswa_id' => ['nullable', 'exists:kasus_siswas,id'],
            'tanggal' => ['required', 'date'],
            // (2026-08-26, revisi) — form "Detail Pemanggilan" dihilangkan.
            // "alasan" cuma diminta manual kalau "Tanpa surat" dipilih
            // (tidak ada teks surat untuk diambil alasannya). Kalau pakai/
            // buat surat, alasan otomatis diambil dari isi surat itu
            // sendiri (lihat di bawah) — tidak perlu diketik dua kali.
            'alasan' => ['nullable', 'string', 'required_if:pilihan_surat,tidak_ada'],
            'pilihan_surat' => ['required', 'in:tidak_ada,pakai_yang_sudah_ada,buat_baru'],
            'surat_id' => ['nullable', 'required_if:pilihan_surat,pakai_yang_sudah_ada', 'exists:surats,id'],
            'jenis_surat_id' => ['nullable', 'required_if:pilihan_surat,buat_baru', 'exists:jenis_surats,id'],
            'nomor_urut' => ['nullable', 'required_if:pilihan_surat,buat_baru', 'string', 'max:50'],
            'tanggal_acara' => ['nullable', 'date'],
            'waktu_acara' => ['nullable', 'date_format:H:i'],
            'isi_surat' => ['nullable', 'string', 'required_if:pilihan_surat,buat_baru'],
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);
        $this->bkPastikanSiswaSesuaiCakupan($request->user(), $siswa);

        $pemanggilan = DB::transaction(function () use ($validated, $siswa, $tahunAjaran, $request) {
            $suratId = null;
            $alasan = $validated['alasan'] ?? null;

            if ($validated['pilihan_surat'] === 'pakai_yang_sudah_ada') {
                $surat = Surat::findOrFail($validated['surat_id']);
                abort_if($surat->siswa_id !== $siswa->id, 422, 'Surat yang dipilih bukan untuk siswa ini.');
                $suratId = $surat->id;
                $alasan = \Illuminate\Support\Str::limit($surat->isi, 500);
            } elseif ($validated['pilihan_surat'] === 'buat_baru') {
                $jenisSurat = JenisSurat::findOrFail($validated['jenis_surat_id']);
                $nomorSurat = NomorSuratBk::buat($validated['nomor_urut'], $validated['tanggal']);

                $surat = Surat::create([
                    'jenis_surat_id' => $jenisSurat->id,
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'arah' => 'keluar',
                    'status' => 'selesai',
                    'nomor_surat' => $nomorSurat,
                    'nomor_urut' => $validated['nomor_urut'],
                    'tanggal' => $validated['tanggal'],
                    'tanggal_acara' => $validated['tanggal_acara'] ?? null,
                    'waktu_acara' => $validated['waktu_acara'] ?? null,
                    'isi' => $validated['isi_surat'],
                    'dibuat_oleh_id' => $request->user()->id,
                ]);
                $surat->siswas()->syncWithoutDetaching([$siswa->id]);
                $suratId = $surat->id;
                $alasan = \Illuminate\Support\Str::limit($validated['isi_surat'], 500);
            }

            return PemanggilanOrangTua::create([
                'siswa_id' => $siswa->id,
                'kasus_siswa_id' => $validated['kasus_siswa_id'] ?? null,
                'surat_id' => $suratId,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'tanggal' => $validated['tanggal'],
                'alasan' => $alasan ?: '-',
                'status' => PemanggilanOrangTua::STATUS_MENUNGGU_PERTEMUAN,
                'petugas_id' => $request->user()->id,
            ]);
        });

        return redirect()->route('bk.siswa.show', $pemanggilan->siswa_id)
            ->with('success', 'Pemanggilan orang tua berhasil dicatat. Isi hasil pertemuan setelah pertemuan berlangsung.');
    }

    /**
     * TAHAP 2 — Isi Hasil Pertemuan (dipanggil BELAKANGAN, setelah
     * pertemuan benar-benar terjadi).
     */
    public function editHasil(Request $request, PemanggilanOrangTua $pemanggilan)
    {
        $this->bkPastikanSiswaSesuaiCakupan($request->user(), $pemanggilan->siswa);
        $pemanggilan->load(['siswa.kelas', 'surat']);

        return view('bk.pemanggilan.hasil', compact('pemanggilan'));
    }

    public function updateHasil(Request $request, PemanggilanOrangTua $pemanggilan)
    {
        $this->bkPastikanSiswaSesuaiCakupan($request->user(), $pemanggilan->siswa);

        $validated = $request->validate([
            'ortu_hadir' => ['required', 'boolean'],
            'hasil_pertemuan' => ['nullable', 'string', 'required_if:ortu_hadir,1'],
            'kesepakatan' => ['nullable', 'string'],
        ]);
        $validated['status'] = PemanggilanOrangTua::STATUS_SELESAI;

        $pemanggilan->update($validated);

        return redirect()->route('bk.siswa.show', $pemanggilan->siswa_id)
            ->with('success', 'Hasil pertemuan berhasil disimpan.');
    }
}
