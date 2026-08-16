<?php

namespace App\Http\Controllers;

use App\Models\GuruMengajarKelas;
use App\Models\JadwalPelajaran;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TahunAjaranController extends Controller
{
    public function index()
    {
        $tahunAjaran = TahunAjaran::orderByDesc('id')->get();
        return view('tahun-ajaran.index', compact('tahunAjaran'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
        ]);
        TahunAjaran::create($validated);
        return back()->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function update(Request $request, TahunAjaran $tahunAjaran)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
        ]);
        $tahunAjaran->update($validated);
        return back()->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function aktifkan(TahunAjaran $tahunAjaran)
    {
        TahunAjaran::query()->update(['is_active' => false]);
        $tahunAjaran->update(['is_active' => true]);
        return back()->with('success', "Tahun ajaran {$tahunAjaran->nama} {$tahunAjaran->semester} sekarang aktif.");
    }

    public function destroy(TahunAjaran $tahunAjaran)
    {
        return $this->hapusAtauGagalDenganPesan(
            $tahunAjaran,
            'Tahun ajaran berhasil dihapus.',
            'Tahun ajaran ini tidak dapat dihapus karena masih memiliki data terkait (jadwal, mapping guru, atau data lain).'
        );
    }

    /**
     * Kunci periode (Tahap 2). Boleh Kurikulum & Admin (sama seperti akses
     * modul Tahun Ajaran lainnya). Mengunci periode HANYA memblokir aksi
     * tulis di modul yang dilindungi middleware 'periode-aktif', dan hanya
     * berlaku kalau periode yang dikunci adalah yang sedang aktif.
     */
    public function kunci(TahunAjaran $tahunAjaran)
    {
        if ($tahunAjaran->isTerkunci()) {
            return back()->with('error', 'Periode ini sudah terkunci.');
        }

        $tahunAjaran->update([
            'terkunci' => true,
            'terkunci_at' => now(),
            'terkunci_oleh_id' => auth()->id(),
        ]);

        return back()->with('success', "Periode {$tahunAjaran->nama} {$tahunAjaran->semester} berhasil dikunci.");
    }

    /** Buka kunci periode — khusus Admin (Tahap 2). */
    public function bukaKunci(TahunAjaran $tahunAjaran)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Hanya Admin yang dapat membuka kunci periode.');
        }

        $tahunAjaran->update([
            'terkunci' => false,
            'terkunci_at' => null,
            'terkunci_oleh_id' => null,
        ]);

        return back()->with('success', "Kunci periode {$tahunAjaran->nama} {$tahunAjaran->semester} berhasil dibuka.");
    }

    /**
     * Salin mapping Guru Mengajar & Jadwal Pelajaran dari tahun ajaran lama
     * ke tahun ajaran baru ($tahunAjaran = tujuan), supaya Kurikulum tidak
     * perlu input ulang dari nol setiap ganti tahun ajaran (sesuai prinsip
     * "data master dipakai ulang" — hanya kenaikan kelas & pengaturan
     * wali/jadwal per periode yang perlu disesuaikan manual kalau memang
     * berubah).
     *
     * Bersifat ADITIF & aman dijalankan berulang: baris yang kombinasi
     * uniknya sudah ada di tujuan otomatis dilewati (bukan error, bukan
     * duplikat), memakai firstOrCreate.
     */
    public function duplikasiMapping(Request $request, TahunAjaran $tahunAjaran)
    {
        $validated = $request->validate([
            'dari_tahun_ajaran_id' => ['required', 'exists:tahun_ajarans,id'],
        ]);

        if ((int) $validated['dari_tahun_ajaran_id'] === $tahunAjaran->id) {
            return back()->with('error', 'Tahun ajaran sumber tidak boleh sama dengan tahun ajaran tujuan.');
        }

        $sumber = TahunAjaran::findOrFail($validated['dari_tahun_ajaran_id']);

        $mengajarDisalin = 0;
        $mengajarDilewati = 0;
        $jadwalDisalin = 0;
        $jadwalDilewati = 0;

        DB::transaction(function () use ($sumber, $tahunAjaran, &$mengajarDisalin, &$mengajarDilewati, &$jadwalDisalin, &$jadwalDilewati) {
            foreach (GuruMengajarKelas::where('tahun_ajaran_id', $sumber->id)->get() as $mapping) {
                $baru = GuruMengajarKelas::firstOrCreate([
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'guru_id' => $mapping->guru_id,
                    'kelas_id' => $mapping->kelas_id,
                    'mata_pelajaran_id' => $mapping->mata_pelajaran_id,
                ]);
                $baru->wasRecentlyCreated ? $mengajarDisalin++ : $mengajarDilewati++;
            }

            foreach (JadwalPelajaran::where('tahun_ajaran_id', $sumber->id)->get() as $jadwal) {
                $baru = JadwalPelajaran::firstOrCreate([
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'hari' => $jadwal->hari,
                    'kelas_id' => $jadwal->kelas_id,
                    'jam_pelajaran_id' => $jadwal->jam_pelajaran_id,
                ], [
                    'mata_pelajaran_id' => $jadwal->mata_pelajaran_id,
                    'guru_id' => $jadwal->guru_id,
                ]);
                $baru->wasRecentlyCreated ? $jadwalDisalin++ : $jadwalDilewati++;
            }
        });

        $pesan = "Berhasil menyalin {$mengajarDisalin} mapping guru-mengajar dan {$jadwalDisalin} jadwal dari {$sumber->nama} {$sumber->semester} ke {$tahunAjaran->nama} {$tahunAjaran->semester}.";
        if ($mengajarDilewati > 0 || $jadwalDilewati > 0) {
            $pesan .= " ({$mengajarDilewati} mapping & {$jadwalDilewati} jadwal dilewati karena sudah ada di tujuan.)";
        }

        return back()->with('success', $pesan);
    }
}
