<?php

namespace App\Http\Controllers;

use App\Rules\DalamPeriode;
use App\Models\JenisSurat;
use App\Models\Siswa;
use App\Models\Surat;
use App\Models\TahunAjaran;
use App\Support\NomorSuratBk;
use App\Support\SuratMerge;
use Illuminate\Http\Request;

/**
 * (2026-08-26) — ROMBAK TOTAL: modul Surat sekarang KHUSUS untuk
 * keperluan BK (permintaan eksplisit + contoh format terlampir).
 *
 * - Hanya Guru BK yang boleh create/edit/delete (dicek di sini DAN di
 *   middleware routes/web.php — dua lapis, konsisten dengan pola app ini).
 *   Kesiswaan/Kurikulum/Kepala Sekolah cuma index()/show() (baca saja).
 * - Nomor surat format baru: 422/{nomor urut MANUAL}/BK/{bulan romawi}/
 *   {tahun} — lihat App\Support\NomorSuratBk. TIDAK ada lagi
 *   auto-increment; guru BK WAJIB isi nomor urutnya sendiri.
 * - 3 dari 4 jenis surat (lihat JenisSurat::TIPE_*) pakai FORM
 *   TERSTRUKTUR (field tetap sesuai contoh kertas BK yang sudah ada),
 *   bukan template bebas — datanya di kolom `data_formulir` (json).
 *   Surat Panggilan Orang Tua/Wali TETAP pakai template bebas (`isi`).
 */
class SuratController extends Controller
{
    public function index(Request $request)
    {
        $query = Surat::with(['jenisSurat', 'siswa.kelas', 'dibuatOleh']);

        if ($request->filled('cari')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->cari}%")
                  ->orWhere('nis', 'like', "%{$request->cari}%");
            });
        }
        if ($request->filled('jenis_surat_id')) {
            $query->where('jenis_surat_id', $request->jenis_surat_id);
        }
        // Dipakai submenu "Draft"/"Arsip" di sidebar (guru_bk).
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $surat = $query->orderByDesc('tanggal')->orderByDesc('id')->paginate(20)->withQueryString();
        $jenisSuratList = JenisSurat::periodeAktif()->where('is_aktif', true)->orderBy('nama_jenis')->get();

        $judul = match ($request->get('status')) {
            'draft' => 'Draft',
            'diarsipkan' => 'Arsip',
            default => 'Daftar Surat (BK)',
        };

        return view('surat.index', compact('surat', 'jenisSuratList', 'judul'));
    }

    /**
     * Form buat surat baru — 3 langkah lewat query string (GET, reload
     * tiap langkah, pola sama seperti form Isi Absensi):
     * 1. Pilih Jenis Surat (menentukan form yang muncul di langkah 3).
     * 2. Cari & pilih Siswa.
     * 3. Form sesuai jenisnya — 3 jenis BK pakai field tetap, Surat
     *    Panggilan tetap pakai template bebas seperti sebelumnya.
     */
    public function create(Request $request)
    {
        $this->pastikanGuruBk($request);

        $jenisSuratList = JenisSurat::periodeAktif()->where('is_aktif', true)->orderBy('nama_jenis')->get();
        $jenisSurat = $jenisSuratList->firstWhere('id', (int) $request->get('jenis_surat_id'));

        $siswaTerpilih = null;
        $hasilCari = collect();
        if ($request->filled('siswa_id')) {
            $siswaTerpilih = Siswa::with('kelas')->find($request->get('siswa_id'));
        } elseif ($request->filled('cari')) {
            $hasilCari = Siswa::periodeAktif()->with('kelas')->where('is_active', true)
                ->where(function ($q) use ($request) {
                    $q->where('nama', 'like', "%{$request->cari}%")
                      ->orWhere('nis', 'like', "%{$request->cari}%");
                })
                ->orderBy('nama')->limit(20)->get();
        }

        $tanggal = $request->get('tanggal', now()->toDateString());
        $tanggalAcara = $request->get('tanggal_acara', '');
        $waktuAcara = $request->get('waktu_acara', '');
        $nomorPratinjau = NomorSuratBk::pratinjau($tanggal);

        $isiGabungan = null;
        $pelanggaranKeBerikutnya = null;
        if ($jenisSurat && $siswaTerpilih) {
            if ($jenisSurat->tipe_formulir === JenisSurat::TIPE_BEBAS) {
                $isiGabungan = SuratMerge::isi(
                    $jenisSurat->template_isi ?? '', $siswaTerpilih, $tanggal, $nomorPratinjau, $tanggalAcara, $waktuAcara
                );
            } elseif ($jenisSurat->tipe_formulir === JenisSurat::TIPE_PERNYATAAN_PELANGGARAN) {
                // Nomor urut pelanggaran BERIKUTNYA untuk siswa ini —
                // dihitung otomatis (boleh diedit manual di form kalau perlu).
                $pelanggaranKeBerikutnya = Surat::whereHas('jenisSurat', fn ($q) => $q->where('tipe_formulir', JenisSurat::TIPE_PERNYATAAN_PELANGGARAN))
                    ->where('siswa_id', $siswaTerpilih->id)->count() + 1;
            }
        }

        return view('surat.create', compact(
            'jenisSuratList', 'jenisSurat', 'siswaTerpilih', 'hasilCari',
            'tanggal', 'tanggalAcara', 'waktuAcara', 'isiGabungan', 'nomorPratinjau', 'pelanggaranKeBerikutnya'
        ));
    }

    public function store(Request $request)
    {
        $this->pastikanGuruBk($request);

        $validated = $request->validate([
            'jenis_surat_id' => ['required', 'exists:jenis_surats,id'],
            'siswa_id' => ['required', 'exists:siswas,id'],
            'tanggal' => ['required', 'date', new DalamPeriode(sebutan: 'surat')],
            // Kolomnya angka di database. Dulu divalidasi sebagai teks,
            // sehingga nomor seperti "01A" lolos lalu jadi HTTP 500.
            'nomor_urut' => ['required', 'integer', 'min:1', 'max:99999'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);

        $nomorSurat = NomorSuratBk::buat($validated['nomor_urut'], $validated['tanggal']);
        NomorSuratBk::pastikanBelumDipakai($nomorSurat);

        $jenisSurat = JenisSurat::findOrFail($validated['jenis_surat_id']);
        $siswa = Siswa::with('kelas')->findOrFail($validated['siswa_id']);

        $isi = null;
        $dataFormulir = null;

        if ($jenisSurat->tipe_formulir === JenisSurat::TIPE_BEBAS) {
            $tambahan = $request->validate([
                'tanggal_acara' => ['nullable', 'date'],
                'waktu_acara' => ['nullable', 'date_format:H:i'],
                'isi' => ['required', 'string'],
            ]);
            $isi = $tambahan['isi'];
            $validated = [...$validated, ...$tambahan];
        } elseif ($jenisSurat->tipe_formulir === JenisSurat::TIPE_IZIN_MENINGGALKAN_PELAJARAN) {
            $f = $request->validate([
                'alamat' => ['nullable', 'string', 'max:255'],
                'jam_ke' => ['nullable', 'string', 'max:50'],
                'keperluan' => ['required', 'string', 'max:500'],
                'keterangan_lain' => ['nullable', 'string', 'max:500'],
            ]);
            $dataFormulir = ['nama' => $siswa->nama, 'kelas' => $siswa->kelas->nama_kelas ?? '-', ...$f];
        } elseif ($jenisSurat->tipe_formulir === JenisSurat::TIPE_KETERANGAN_TERLAMBAT) {
            $f = $request->validate([
                'alamat' => ['nullable', 'string', 'max:255'],
                'terlambat' => ['required', 'string', 'max:100'],
                'alasan_terlambat' => ['required', 'string', 'max:500'],
            ]);
            $dataFormulir = ['nama' => $siswa->nama, 'kelas' => $siswa->kelas->nama_kelas ?? '-', ...$f];
        } elseif ($jenisSurat->tipe_formulir === JenisSurat::TIPE_PERNYATAAN_PELANGGARAN) {
            $f = $request->validate([
                'pelanggaran_ke' => ['required', 'integer', 'min:1'],
                'pelanggaran' => ['required', 'string', 'max:1000'],
            ]);
            $dataFormulir = ['nama' => $siswa->nama, 'kelas' => $siswa->kelas->nama_kelas ?? '-', ...$f];
        }

        $surat = Surat::create([
            'jenis_surat_id' => $jenisSurat->id,
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => TahunAjaran::aktif()?->id,
            'arah' => 'keluar',
            'status' => 'selesai',
            'nomor_surat' => $nomorSurat,
            'nomor_urut' => $validated['nomor_urut'],
            'tanggal' => $validated['tanggal'],
            'tanggal_acara' => $validated['tanggal_acara'] ?? null,
            'waktu_acara' => $validated['waktu_acara'] ?? null,
            'isi' => $isi,
            'data_formulir' => $dataFormulir,
            'keterangan' => $validated['keterangan'] ?? null,
            'dibuat_oleh_id' => $request->user()->id,
        ]);
        $surat->siswas()->syncWithoutDetaching([$siswa->id]);

        return redirect()->route('surat.show', $surat)->with('success', "Surat {$surat->nomor_surat} berhasil dibuat.");
    }

    public function show(Surat $surat)
    {
        $surat->load(['jenisSurat', 'siswa.kelas', 'dibuatOleh']);

        return view('surat.show', compact('surat'));
    }

    public function edit(Request $request, Surat $surat)
    {
        $this->pastikanGuruBk($request);
        $surat->load(['jenisSurat', 'siswa.kelas']);

        return view('surat.edit', compact('surat'));
    }

    public function update(Request $request, Surat $surat)
    {
        $this->pastikanGuruBk($request);

        $validated = $request->validate([
            'tanggal' => ['required', 'date', new DalamPeriode(sebutan: 'surat')],
            'nomor_urut' => ['required', 'integer', 'min:1', 'max:99999'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);
        $validated['nomor_surat'] = NomorSuratBk::buat($validated['nomor_urut'], $validated['tanggal']);
        // Dikecualikan dari dirinya sendiri — mengubah surat tanpa mengganti
        // nomornya tidak boleh dianggap bentrok.
        NomorSuratBk::pastikanBelumDipakai($validated['nomor_surat'], $surat->id);

        if ($surat->jenisSurat->tipe_formulir === JenisSurat::TIPE_BEBAS) {
            $validated = [...$validated, ...$request->validate([
                'tanggal_acara' => ['nullable', 'date'],
                'waktu_acara' => ['nullable', 'date_format:H:i'],
                'isi' => ['required', 'string'],
            ])];
        } else {
            $skemaPerTipe = [
                JenisSurat::TIPE_IZIN_MENINGGALKAN_PELAJARAN => ['alamat' => 'nullable|string|max:255', 'jam_ke' => 'nullable|string|max:50', 'keperluan' => 'required|string|max:500', 'keterangan_lain' => 'nullable|string|max:500'],
                JenisSurat::TIPE_KETERANGAN_TERLAMBAT => ['alamat' => 'nullable|string|max:255', 'terlambat' => 'required|string|max:100', 'alasan_terlambat' => 'required|string|max:500'],
                JenisSurat::TIPE_PERNYATAAN_PELANGGARAN => ['pelanggaran_ke' => 'required|integer|min:1', 'pelanggaran' => 'required|string|max:1000'],
            ];
            $f = $request->validate($skemaPerTipe[$surat->jenisSurat->tipe_formulir]);
            $validated['data_formulir'] = [
                'nama' => $surat->data_formulir['nama'] ?? $surat->siswa->nama,
                'kelas' => $surat->data_formulir['kelas'] ?? ($surat->siswa->kelas->nama_kelas ?? '-'),
                ...$f,
            ];
        }

        $surat->update($validated);

        return redirect()->route('surat.show', $surat)->with('success', 'Surat berhasil diperbarui.');
    }

    public function destroy(Request $request, Surat $surat)
    {
        $this->pastikanGuruBk($request);

        // Surat yang sudah dipakai sebagai dasar Pemanggilan Orang Tua tidak
        // boleh dihapus (pemanggilan_orangtuas.surat_id NO ACTION) — dulu
        // percobaannya berakhir HTTP 500 tanpa penjelasan apa pun.
        return $this->hapusAtauGagalDenganPesan(
            $surat,
            'Surat berhasil dihapus.',
            'Surat ini tidak dapat dihapus karena sudah dipakai di data lain. Lepaskan dulu keterkaitannya, atau arsipkan suratnya saja'
        );
    }

    /**
     * Lapis kedua (route middleware sudah membatasi juga) — jaga-jaga
     * kalau ada yang memanggil method ini lewat jalur lain. Admin selalu
     * lolos (konsisten dengan App\Http\Middleware\EnsureRole).
     */
    private function pastikanGuruBk(Request $request): void
    {
        $role = $request->user()->role;
        abort_if(!in_array($role, ['guru_bk', 'admin']), 403, 'Hanya Guru BK yang bisa mengelola surat.');
    }
}
