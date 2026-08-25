<?php

namespace App\Http\Controllers;

use App\Models\JenisSurat;
use App\Models\Siswa;
use App\Models\Surat;
use App\Models\SuratActivity;
use App\Models\TahunAjaran;
use App\Support\NomorSurat;
use App\Support\SuratMerge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Surat — dibuat & dilihat BERSAMA oleh Kesiswaan dan BK (1 arsip yang
 * sama, bukan terpisah per role), sesuai arahan: "kesiswaan dan BK
 * saling bisa membuat dan mencetak surat serta mengetahui surat yang
 * diinput".
 *
 * (2026-08-25) — nomor surat OTOMATIS (lihat App\Support\NomorSurat) dan
 * dukungan tanggal/waktu acara terpisah dari tanggal surat diterbitkan.
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
        // (2026-08-25) — filter arah (masuk/keluar) & status (draft/
        // diarsipkan/dst), dipakai submenu "Surat Masuk", "Surat Keluar",
        // "Draft", "Arsip" di sidebar (lihat resources/views/layouts/app.blade.php).
        if ($request->filled('arah')) {
            $query->where('arah', $request->arah);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $surat = $query->orderByDesc('tanggal')->orderByDesc('id')->paginate(20)->withQueryString();
        $jenisSuratList = JenisSurat::orderBy('nama_jenis')->get();

        // Judul halaman menyesuaikan filter aktif, supaya jelas submenu
        // mana yang sedang dibuka (Surat Masuk/Keluar/Draft/Arsip/Semua).
        $judul = match (true) {
            $request->get('arah') === 'masuk' => 'Surat Masuk',
            $request->get('arah') === 'keluar' => 'Surat Keluar',
            $request->get('status') === 'draft' => 'Draft',
            $request->get('status') === 'diarsipkan' => 'Arsip',
            default => 'Daftar Surat',
        };

        return view('surat.index', compact('surat', 'jenisSuratList', 'judul'));
    }

    /**
     * Form buat surat baru — alur 3 langkah lewat query string (GET,
     * reload halaman tiap langkah, pola sama seperti form Isi Absensi):
     * 1. Pilih Jenis Surat.
     * 2. Cari & pilih Siswa.
     * 3. Tanggal surat + tanggal/waktu acara (opsional) — begitu semua
     *    terisi, Nomor Surat & isi surat otomatis dipratinjaukan
     *    (nomor final baru dikunci saat Simpan, lihat store()).
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
        $tanggalAcara = $request->get('tanggal_acara', '');
        $waktuAcara = $request->get('waktu_acara', '');

        $isiGabungan = null;
        $nomorPreview = null;
        if ($jenisSurat && $siswaTerpilih) {
            $nomorPreview = NomorSurat::berikutnya($jenisSurat, $tanggal)['nomor_surat'];
            $isiGabungan = SuratMerge::isi(
                $jenisSurat->template_isi ?? '', $siswaTerpilih, $tanggal, $nomorPreview, $tanggalAcara, $waktuAcara
            );
        }

        return view('surat.create', compact(
            'jenisSuratList', 'jenisSurat', 'siswaTerpilih', 'hasilCari',
            'tanggal', 'tanggalAcara', 'waktuAcara', 'isiGabungan', 'nomorPreview'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'jenis_surat_id' => ['required', 'exists:jenis_surats,id'],
            'siswa_id' => ['required', 'exists:siswas,id'],
            'tanggal' => ['required', 'date'],
            'tanggal_acara' => ['nullable', 'date'],
            'waktu_acara' => ['nullable', 'date_format:H:i'],
            'isi' => ['required', 'string'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);

        $jenisSurat = JenisSurat::findOrFail($validated['jenis_surat_id']);

        $surat = DB::transaction(function () use ($validated, $jenisSurat, $request) {
            // finalisasi() pakai row lock — aman dari 2 surat dapat nomor
            // sama kalau disimpan nyaris bersamaan. WAJIB di dalam transaksi.
            $nomor = NomorSurat::finalisasi($jenisSurat, $validated['tanggal']);

            $surat = Surat::create([
                ...$validated,
                'nomor_surat' => $nomor['nomor_surat'],
                'nomor_urut' => $nomor['nomor_urut'],
                'dibuat_oleh_id' => $request->user()->id,
                'arah' => 'keluar',
                'status' => 'selesai',
                'tahun_ajaran_id' => TahunAjaran::aktif()?->id,
            ]);
            $surat->siswas()->syncWithoutDetaching([$validated['siswa_id']]);
            SuratActivity::catat($surat, 'Surat dibuat', "Nomor {$nomor['nomor_surat']}, untuk {$surat->siswa->nama}");

            return $surat;
        });

        return redirect()->route('surat.show', $surat)->with('success', "Surat {$surat->nomor_surat} berhasil dibuat.");
    }

    public function show(Surat $surat)
    {
        $surat->load(['jenisSurat', 'siswa.kelas', 'dibuatOleh', 'disposisi.dariUser', 'disposisi.kepadaUser', 'attachments.user', 'activities.user']);
        $calonPenerima = \App\Http\Controllers\DisposisiSuratController::calonPenerima();

        return view('surat.show', compact('surat', 'calonPenerima'));
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
            'tanggal_acara' => ['nullable', 'date'],
            'waktu_acara' => ['nullable', 'date_format:H:i'],
            'isi' => ['required', 'string'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);

        $surat->update($validated);
        SuratActivity::catat($surat, 'Surat diperbarui');

        return redirect()->route('surat.show', $surat)->with('success', 'Surat berhasil diperbarui.');
    }

    public function destroy(Surat $surat)
    {
        $surat->delete();

        return redirect()->route('surat.index')->with('success', 'Surat berhasil dihapus.');
    }
}
