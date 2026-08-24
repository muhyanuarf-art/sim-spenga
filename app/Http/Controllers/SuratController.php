<?php

namespace App\Http\Controllers;

use App\Models\JenisSurat;
use App\Models\Siswa;
use App\Models\Surat;
use App\Support\SuratMerge;
use Illuminate\Http\Request;

/**
 * Surat — dibuat & dilihat BERSAMA oleh Kesiswaan dan BK (1 arsip yang
 * sama, bukan terpisah per role), sesuai arahan: "kesiswaan dan BK
 * saling bisa membuat dan mencetak surat serta mengetahui surat yang
 * diinput".
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

        $surat = $query->orderByDesc('tanggal')->orderByDesc('id')->paginate(20)->withQueryString();
        $jenisSuratList = JenisSurat::orderBy('nama_jenis')->get();

        return view('surat.index', compact('surat', 'jenisSuratList'));
    }

    /**
     * Form buat surat baru — alur 2 langkah lewat query string (GET,
     * reload halaman tiap langkah, pola sama seperti form Isi Absensi):
     * 1. Pilih Jenis Surat.
     * 2. Cari & pilih Siswa.
     * Begitu keduanya terisi, isi surat otomatis digabung dari template
     * (bisa diedit lagi sebelum Simpan).
     */
    public function create(Request $request)
    {
        $jenisSuratList = JenisSurat::orderBy('nama_jenis')->get();
        $jenisSurat = $jenisSuratList->firstWhere('id', (int) $request->get('jenis_surat_id'));

        $siswaTerpilih = null;
        $hasilCari = collect();
        if ($request->filled('siswa_id')) {
            $siswaTerpilih = Siswa::with('kelas')->find($request->get('siswa_id'));
        } elseif ($request->filled('cari')) {
            $hasilCari = Siswa::with('kelas')->where('is_active', true)
                ->where(function ($q) use ($request) {
                    $q->where('nama', 'like', "%{$request->cari}%")
                      ->orWhere('nis', 'like', "%{$request->cari}%");
                })
                ->orderBy('nama')->limit(20)->get();
        }

        $tanggal = $request->get('tanggal', now()->toDateString());
        $isiGabungan = null;
        if ($jenisSurat && $siswaTerpilih) {
            $isiGabungan = SuratMerge::isi($jenisSurat->template_isi ?? '', $siswaTerpilih, $tanggal, $request->get('nomor_surat'));
        }

        return view('surat.create', compact(
            'jenisSuratList', 'jenisSurat', 'siswaTerpilih', 'hasilCari', 'tanggal', 'isiGabungan'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'jenis_surat_id' => ['required', 'exists:jenis_surats,id'],
            'siswa_id' => ['required', 'exists:siswas,id'],
            'nomor_surat' => ['nullable', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'isi' => ['required', 'string'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);
        $validated['dibuat_oleh_id'] = $request->user()->id;

        $surat = Surat::create($validated);

        return redirect()->route('surat.show', $surat)->with('success', 'Surat berhasil dibuat.');
    }

    public function show(Surat $surat)
    {
        $surat->load(['jenisSurat', 'siswa.kelas', 'dibuatOleh']);

        return view('surat.show', compact('surat'));
    }

    public function edit(Surat $surat)
    {
        $surat->load(['jenisSurat', 'siswa.kelas']);

        return view('surat.edit', compact('surat'));
    }

    public function update(Request $request, Surat $surat)
    {
        $validated = $request->validate([
            'nomor_surat' => ['nullable', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'isi' => ['required', 'string'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);

        $surat->update($validated);

        return redirect()->route('surat.show', $surat)->with('success', 'Surat berhasil diperbarui.');
    }

    public function destroy(Surat $surat)
    {
        $surat->delete();

        return redirect()->route('surat.index')->with('success', 'Surat berhasil dihapus.');
    }
}
